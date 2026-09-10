<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Repositories\QuestionRepository;

/**
 * Exam grading and score interpretation.
 *
 * Grading is server-authoritative and always has been: the answer key is read
 * from the database and the denominator is the number of questions stored for
 * the exam. Nothing submitted by the browser influences either. That property
 * is preserved here exactly; it is simply no longer inlined in a page file.
 *
 * The pass mark used to be the literal 60 repeated in six templates. It now
 * comes from configuration and is applied through isPass().
 */
final class GradingService
{
    public function __construct(
        private readonly QuestionRepository $questions = new QuestionRepository()
    ) {
    }

    /**
     * Grade a submission against the stored answer key.
     *
     * @param  array<string,mixed> $submitted raw request input
     * @return array{score:int,total:int}
     */
    public function grade(int $examId, array $submitted): array
    {
        $key   = $this->questions->answerKey($examId);
        $score = 0;

        foreach ($key as $question) {
            $field  = 'q_' . $question['question_id'];
            $answer = $submitted[$field] ?? null;

            if ($answer === null || !is_scalar($answer) || !is_numeric((string) $answer)) {
                continue;
            }

            if ((int) $answer === (int) $question['correct_answer']) {
                $score++;
            }
        }

        return ['score' => $score, 'total' => count($key)];
    }

    /** Score as a whole-number percentage, guarding against division by zero. */
    public static function percentage(int $score, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($score / $total) * 100);
    }

    public static function passMark(): int
    {
        return (int) Config::get('exam.pass_mark', 60);
    }

    public static function isPass(int $percentage): bool
    {
        return $percentage >= self::passMark();
    }
}
