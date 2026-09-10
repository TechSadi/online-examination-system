<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Repositories\AttemptRepository;
use App\Repositories\QuestionRepository;
use App\Repositories\ResultRepository;
use DateTimeImmutable;
use DateTimeZone;

/**
 * The exam engine: starting a sitting, enforcing its deadline, grading it.
 *
 * Everything that decides an outcome happens here, on the server. The browser
 * is told how much time is left only so it can draw a countdown; the countdown
 * itself has no authority. The server, not the client, decides:
 *
 *   - whether a student may sit this exam at all
 *   - when the attempt started and when it must end
 *   - whether a submission arrived in time
 *   - which questions count, and what the correct answers are
 *   - the score
 *   - whether this is the first submission or a duplicate
 *
 * A submission with no attempt behind it is refused outright, so posting
 * straight to submit_exam.php without ever opening the exam does nothing.
 */
final class ExamService
{
    /* Outcomes of opening an exam. */
    public const STARTED   = 'started';
    public const RESUMED   = 'resumed';
    public const COMPLETED = 'completed';

    /* Outcomes of submitting one. */
    public const SUBMITTED    = 'submitted';
    public const EXPIRED      = 'expired';
    public const DUPLICATE    = 'duplicate';
    public const NO_ATTEMPT   = 'no_attempt';
    public const NO_QUESTIONS = 'no_questions';

    public function __construct(
        private readonly AttemptRepository $attempts = new AttemptRepository(),
        private readonly QuestionRepository $questions = new QuestionRepository(),
        private readonly ResultRepository $results = new ResultRepository(),
        private readonly GradingService $grading = new GradingService()
    ) {
    }

    /**
     * How long a submission may arrive after the deadline and still count.
     *
     * The client auto-submits at zero, so the request is already in flight as
     * the deadline passes. Without a small allowance, a slow connection would
     * cost a student their entire paper for a delay they did not cause.
     */
    private function grace(): int
    {
        return max(0, (int) Config::get('exam.submit_grace', 60));
    }

    private function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    private static function format(DateTimeImmutable $moment): string
    {
        return $moment->format('Y-m-d H:i:s');
    }

    private function parse(string $stored): DateTimeImmutable
    {
        return new DateTimeImmutable($stored, new DateTimeZone('UTC'));
    }

    /**
     * Open an exam: resume the attempt in progress, or begin one.
     *
     * expires_at is computed once, here, from the duration as it stands when
     * the student starts, then stored. Pinning it means an administrator
     * editing the exam mid-sitting cannot shorten or extend an attempt
     * already under way, and a student who reloads does not get a new clock.
     *
     * @param  array<string,mixed> $exam
     * @return array{state:string,attempt:array<string,mixed>|null,seconds_remaining:int}
     */
    public function open(int $studentId, array $exam): array
    {
        $examId  = (int) $exam['exam_id'];
        $attempt = $this->attempts->findForStudentAndExam($studentId, $examId);

        if ($attempt !== null) {
            return $this->reopen($attempt, $examId);
        }

        $startedAt = $this->now();
        $expiresAt = $startedAt->modify(sprintf('+%d minutes', max(1, (int) $exam['duration'])));

        $attemptId = $this->attempts->start(
            $studentId,
            $examId,
            self::format($startedAt),
            self::format($expiresAt)
        );

        if ($attemptId === null) {
            // Another request created the attempt between the lookup and the
            // insert. The unique key held; continue with the row that won.
            $attempt = $this->attempts->findForStudentAndExam($studentId, $examId);

            return $attempt === null
                ? ['state' => self::COMPLETED, 'attempt' => null, 'seconds_remaining' => 0]
                : $this->reopen($attempt, $examId);
        }

        return [
            'state'             => self::STARTED,
            'attempt'           => $this->attempts->findForStudentAndExam($studentId, $examId),
            'seconds_remaining' => $this->secondsRemaining($expiresAt),
        ];
    }

    /**
     * Decide what to do with an attempt that already exists.
     *
     * @param  array<string,mixed> $attempt
     * @return array{state:string,attempt:array<string,mixed>|null,seconds_remaining:int}
     */
    private function reopen(array $attempt, int $examId): array
    {
        if ($attempt['status'] !== AttemptRepository::STATUS_IN_PROGRESS) {
            return ['state' => self::COMPLETED, 'attempt' => $attempt, 'seconds_remaining' => 0];
        }

        $expiresAt = $this->parse((string) $attempt['expires_at']);

        // Walking away and returning later must not hand back a live exam.
        if ($this->now() > $expiresAt->modify(sprintf('+%d seconds', $this->grace()))) {
            $this->abandon((int) $attempt['attempt_id'], (int) $attempt['student_id'], $examId);

            return ['state' => self::COMPLETED, 'attempt' => $attempt, 'seconds_remaining' => 0];
        }

        return [
            'state'             => self::RESUMED,
            'attempt'           => $attempt,
            'seconds_remaining' => $this->secondsRemaining($expiresAt),
        ];
    }

    private function secondsRemaining(DateTimeImmutable $expiresAt): int
    {
        return max(0, $expiresAt->getTimestamp() - $this->now()->getTimestamp());
    }

    /**
     * Close an attempt the student never handed in.
     *
     * Recorded with a score of zero: the deadline passed and the server holds
     * no evidence that any answer was given in time. A result row is written
     * too, so the exam shows as taken and cannot be started fresh.
     */
    private function abandon(int $attemptId, int $studentId, int $examId): void
    {
        $total = $this->questions->countForExam($examId);
        $now   = self::format($this->now());

        Database::transaction(function () use ($attemptId, $studentId, $examId, $total, $now): void {
            if (!$this->attempts->close($attemptId, AttemptRepository::STATUS_EXPIRED, 0, $total, $now)) {
                return; // Another request closed it first.
            }

            $this->results->record($attemptId, $studentId, $examId, 0, $total);
        });
    }

    /**
     * Grade and store a submission.
     *
     * The whole write is one transaction: the attempt is closed, the answers
     * are stored and the result is created together, or none of it happens. A
     * partial commit here would leave either a closed attempt with no result,
     * or a result with no answers behind it.
     *
     * @param array<string,mixed> $input raw request input
     */
    public function submit(int $studentId, int $examId, array $input): string
    {
        $attempt = $this->attempts->findForStudentAndExam($studentId, $examId);

        if ($attempt === null) {
            return self::NO_ATTEMPT;
        }

        if ($attempt['status'] !== AttemptRepository::STATUS_IN_PROGRESS) {
            return self::DUPLICATE;
        }

        $attemptId = (int) $attempt['attempt_id'];
        $deadline  = $this->parse((string) $attempt['expires_at'])
            ->modify(sprintf('+%d seconds', $this->grace()));

        $late   = $this->now() > $deadline;
        $graded = $this->grading->grade($examId, $input);

        if ($graded['total'] === 0) {
            return self::NO_QUESTIONS;
        }

        // A late paper is recorded, and its answers kept for audit, but it
        // scores nothing: the deadline is the whole point of a timed exam.
        $score  = $late ? 0 : $graded['score'];
        $status = $late ? AttemptRepository::STATUS_EXPIRED : AttemptRepository::STATUS_SUBMITTED;
        $now    = self::format($this->now());

        return Database::transaction(function () use (
            $attemptId,
            $studentId,
            $examId,
            $graded,
            $score,
            $status,
            $now,
            $late
        ): string {
            // close() matches only a row that is still in progress, so of any
            // number of racing submissions exactly one gets past this line.
            if (!$this->attempts->close($attemptId, $status, $score, $graded['total'], $now)) {
                return self::DUPLICATE;
            }

            $this->attempts->saveAnswers($attemptId, $graded['answers']);
            $this->results->record($attemptId, $studentId, $examId, $score, $graded['total']);

            return $late ? self::EXPIRED : self::SUBMITTED;
        });
    }
}
