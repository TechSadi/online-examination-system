<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Page arithmetic for a listing.
 *
 * The admin tables used to select every row and render all of them. That is
 * fine with the three students a demo has and unusable with the three
 * thousand a real intake has: the query gets slower, the HTML gets larger,
 * and the page becomes a wall nobody can find anything in.
 *
 * This holds only the arithmetic. The SQL limit and offset come from here,
 * the rendering comes from partials/pagination.php, and neither has to
 * recompute what the other decided.
 */
final class Paginator
{
    public const DEFAULT_PER_PAGE = 15;

    /** How many numbered links sit either side of the current page. */
    private const WINDOW = 2;

    public function __construct(
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage = self::DEFAULT_PER_PAGE
    ) {
    }

    /**
     * Build from the request's ?page= parameter.
     *
     * A page beyond the end is clamped rather than rejected: a bookmark to
     * page 9 of a list that has shrunk to four pages should show the last
     * page, not an error and not an empty table.
     */
    public static function fromRequest(int $total, int $perPage = self::DEFAULT_PER_PAGE): self
    {
        $requested = Request::int('page', 1);
        $pages     = max(1, (int) ceil($total / max(1, $perPage)));

        return new self($total, min(max(1, $requested), $pages), $perPage);
    }

    public function pages(): int
    {
        return max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /** 1-based index of the first row on this page, or 0 when there are none. */
    public function from(): int
    {
        return $this->total === 0 ? 0 : $this->offset() + 1;
    }

    /** 1-based index of the last row on this page. */
    public function to(): int
    {
        return min($this->total, $this->offset() + $this->perPage);
    }

    public function hasPages(): bool
    {
        return $this->pages() > 1;
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->pages();
    }

    /**
     * The page numbers to render, with null standing for an elided run.
     *
     * First and last are always present so the ends of the list stay one
     * click away however long it is: [1, null, 6, 7, 8, null, 42].
     *
     * @return list<int|null>
     */
    public function window(): array
    {
        $pages = $this->pages();

        if ($pages <= 7) {
            return range(1, $pages);
        }

        $numbers = [1];

        $start = max(2, $this->page - self::WINDOW);
        $end   = min($pages - 1, $this->page + self::WINDOW);

        if ($start > 2) {
            $numbers[] = null;
        }

        for ($i = $start; $i <= $end; $i++) {
            $numbers[] = $i;
        }

        if ($end < $pages - 1) {
            $numbers[] = null;
        }

        $numbers[] = $pages;

        return $numbers;
    }
}
