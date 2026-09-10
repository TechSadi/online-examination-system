<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

/**
 * A sort chosen by the request, resolved against an allowlist.
 *
 * A sortable column header is a query string the user controls, and the
 * obvious implementation - dropping ?sort= into an ORDER BY - is a SQL
 * injection with extra steps. Nothing the request sends ever reaches the
 * query here: the request picks a *key*, and the key selects a fragment
 * this class was constructed with.
 *
 * An unknown key falls back to the default rather than failing, because a
 * stale bookmark or a hand-edited URL should still show the list.
 */
final class Sorter
{
    /**
     * @param array<string,string> $columns key => SQL fragment, e.g.
     *                                      ['name' => 's.name']
     */
    public function __construct(
        private readonly array $columns,
        public readonly string $key,
        public readonly string $direction
    ) {
        if (!isset($this->columns[$this->key])) {
            throw new InvalidArgumentException(sprintf('Unknown sort key "%s".', $this->key));
        }
    }

    /**
     * Resolve ?sort= and ?dir= against the allowlist.
     *
     * @param array<string,string> $columns
     */
    public static function fromRequest(array $columns, string $default, string $defaultDirection = 'asc'): self
    {
        $key = Request::query('sort');

        if (!isset($columns[$key])) {
            $key       = $default;
            $direction = $defaultDirection;
        } else {
            $direction = strtolower(Request::query('dir')) === 'desc' ? 'desc' : 'asc';
        }

        return new self($columns, $key, $direction);
    }

    /**
     * The ORDER BY clause.
     *
     * Both halves are literals from this object's own construction: the
     * fragment comes from the allowlist, and the direction is one of two
     * hardcoded words.
     */
    public function orderBy(): string
    {
        return $this->columns[$this->key] . ' ' . ($this->direction === 'desc' ? 'DESC' : 'ASC');
    }

    /** The direction a header link should request to toggle this column. */
    public function directionFor(string $key): string
    {
        return $this->key === $key && $this->direction === 'asc' ? 'desc' : 'asc';
    }

    /** The aria-sort value for a header, or null when it is not the sorted one. */
    public function ariaSortFor(string $key): ?string
    {
        if ($this->key !== $key) {
            return null;
        }

        return $this->direction === 'desc' ? 'descending' : 'ascending';
    }
}
