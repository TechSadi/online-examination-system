<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Repositories\QuestionRepository;

/**
 * Exam grading and score interpretation.
 *
 * The answer key is read from the database and the denominator is the number
 * of questions stored for the exam, so neither can be influenced by the
 * browser. Grading now also returns a per-question breakdown, which the
 * caller persists: a stored score that cannot be checked against the answers
 * behind it is a number nobody can audit after the fact.
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
     * The key, the set of questions that count, and the denominator all come
     * from the database. The request contributes exactly one thing per
     * question: which of the four options was ticked.
     *
     * Anything else in the POST body is ignored rather than trusted, so a
     * crafted submission cannot introduce a question that belongs to another
     * exam, answer the same question twice, claim an option outside 1-4, or
     * assert its own score. A question with no valid answer is recorded as
     * unanswered and counts against the total.
     *
     * @param  array<string,mixed> $submitted raw request input
     * @return array{score:int,total:int,answers:list<array{question_id:int,selected:int|null,is_correct:bool}>}
     */
    public function grade(int $examId, array $submitted): array
    {
        $key     = $this->questions->answerKey($examId);
        $score   = 0;
        $answers = [];

        foreach ($key as $question) {
            $questionId = (int) $question['question_id'];
            $selected   = self::selectedOption($submitted['q_' . $questionId] ?? null);
            $isCorrect  = $selected !== null && $selected === (int) $question['correct_answer'];

            if ($isCorrect) {
                $score++;
            }

            $answers[] = [
                'question_id' => $questionId,
                'selected'    => $selected,
                'is_correct'  => $isCorrect,
            ];
        }

        return ['score' => $score, 'total' => count($key), 'answers' => $answers];
    }

    /**
     * Normalise one submitted option to 1-4, or null when it is absent or
     * not one of the four real choices.
     *
     * The strict digit test matters: a loose (int) cast would turn "3abc",
     * " 3" and "3.9" into a valid answer, and an array into 1.
     */
    private static function selectedOption(mixed $value): ?int
    {
        if (!is_scalar($value)) {
            return null;
        }

        $raw = trim((string) $value);

        if (preg_match('/^[1-4]$/', $raw) !== 1) {
            return null;
        }

        return (int) $raw;
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
