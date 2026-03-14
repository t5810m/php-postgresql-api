<?php

namespace App\Traits;

/**
 * PaginationTrait
 *
 * Provides pagination support for API controllers using CodeIgniter 4's built-in paginate() method.
 * Handles query parameter validation and metadata extraction.
 */
trait PaginationTrait
{
    /**
     * Get the per-page limit from query parameters with validation
     *
     * @param int $default Default per-page value (default: 10)
     * @param int $max Maximum allowed per-page value (default: 100)
     * @return int Validated per-page value
     */
    protected function getPerPage(int $default = 10, int $max = 100): int
    {
        $perPage = (int) $this->request->getGet('per_page');

        if ($perPage <= 0) {
            return $default;
        }

        return min($perPage, $max);
    }

    /**
     * Get the current page number from query parameters with validation
     *
     * @param int $default Default page number (default: 1)
     * @return int Validated page number
     */
    protected function getPage(int $default = 1): int
    {
        $page = (int) $this->request->getGet('page');

        if ($page < 1) {
            return $default;
        }

        return $page;
    }

    /**
     * Build pagination metadata array from the model's pager
     *
     * @return array Pagination metadata
     */
    protected function buildMeta(): array
    {
        $details = $this->model->pager->getDetails();

        return [
            'total'           => $details['total'],
            'per_page'        => $details['perPage'],
            'current_page'    => $details['currentPage'],
            'last_page'       => $details['pageCount'],
            'next_page_url'   => $details['next'] ?? null,
            'prev_page_url'   => $details['previous'] ?? null,
        ];
    }
}
