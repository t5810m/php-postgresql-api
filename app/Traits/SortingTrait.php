<?php

namespace App\Traits;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Model;

/**
 * SortingTrait
 *
 * Provides sorting support for API controllers using CodeIgniter 4's built-in orderBy($column, $direction) method.
 *
 * @property-read IncomingRequest $request
 * @property-read Model $model
 */
trait SortingTrait
{
    /**
     * Apply sorting to the model query
     *
     * @param array $allowedColumns List of columns that can be sorted
     * @param string $defaultColumn Default column to sort by
     * @param string $defaultOrder Default sort order (asc or desc)
     */
    protected function applySort(array $allowedColumns, string $defaultColumn = 'created_at', string $defaultOrder = 'desc'): void
    {
        $sortBy = $this->request->getGet('sort_by');
        $order = $this->request->getGet('order');

        // Determine which column to sort by (validate sort_by or use default)
        $column = ($sortBy && in_array($sortBy, $allowedColumns)) ? $sortBy : $defaultColumn;

        // Determine sort order (respect order parameter, fall back to default)
        $sortOrder = ($order === 'asc') ? 'asc' : $defaultOrder;

        // Apply sorting
        $this->model->orderBy($column, $sortOrder);
    }
}