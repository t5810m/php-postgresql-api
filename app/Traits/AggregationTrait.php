<?php

namespace App\Traits;

trait AggregationTrait
{
    /**
     * Apply aggregation (count, sum, avg, min, max) with optional grouping.
     *
     * Returns false if no ?aggregate param found (normal pagination flow continues).
     * Returns aggregation result array if aggregation is active.
     *
     * Usage in controller:
     *   $result = $this->applyAggregation(['status_id', 'priority_id', 'category_id']);
     *   if ($result !== false) {
     *       return $this->respond(['success' => true, 'data' => $result]);
     *   }
     *   // else continue with normal paginate flow
     */
    protected function applyAggregation(array $allowedGroupBy)
    {
        $aggregateType = $this->request->getGet('aggregate');
        if ($aggregateType === null || $aggregateType === '') {
            return false; // No aggregation requested
        }

        // Validate aggregate type
        $validTypes = ['count', 'sum', 'avg', 'min', 'max'];
        if (!in_array($aggregateType, $validTypes, true)) {
            return false;
        }

        // Get the field to aggregate (required for sum/avg/min/max, ignored for count)
        $aggregateField = $this->request->getGet('aggregate_field');

        // Get the group by column (optional)
        $groupByColumn = $this->request->getGet('group_by');

        // Build the query
        $query = clone $this->model;

        // Apply aggregation based on type
        switch ($aggregateType) {
            case 'count':
                $query->selectCount('id', 'count');
                break;
            case 'sum':
                if ($aggregateField) {
                    $query->selectSum($aggregateField, 'value');
                } else {
                    $query->selectSum('id', 'value');
                }
                break;
            case 'avg':
                if ($aggregateField) {
                    $query->selectAvg($aggregateField, 'value');
                } else {
                    $query->selectAvg('id', 'value');
                }
                break;
            case 'min':
                if ($aggregateField) {
                    $query->selectMin($aggregateField, 'value');
                } else {
                    $query->selectMin('id', 'value');
                }
                break;
            case 'max':
                if ($aggregateField) {
                    $query->selectMax($aggregateField, 'value');
                } else {
                    $query->selectMax('id', 'value');
                }
                break;
        }

        // Apply grouping if requested and valid
        if ($groupByColumn && in_array($groupByColumn, $allowedGroupBy, true)) {
            $query->select($groupByColumn);
            $query->groupBy($groupByColumn);
        }

        // Execute and return results
        $results = $query->get()->getResultArray();

        return $results ?: [];
    }
}
