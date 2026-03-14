<?php

namespace App\Traits;

use Config\Database;

trait FilterTrait
{
    // Exact integer/string matches
    // $filters = ['queryParam' => 'db_column']
    protected function applyExactFilters(array $filters): void
    {
        foreach ($filters as $param => $column) {
            $value = $this->request->getGet($param);
            if ($value !== null && $value !== '') {
                $this->model->where($column, $value);
            }
        }
    }

    // LIKE search across multiple columns (OR between them) - case-sensitive by default
    protected function applySearch(array $columns, string $param = 'search'): void
    {
        $search = $this->request->getGet($param);
        if ($search === null || $search === '') {
            return;
        }
        $this->model->groupStart();
        foreach ($columns as $column) {
            $this->model->orLike($column, $search);
        }
        $this->model->groupEnd();
    }

    // Date range on a single column (?date_from=YYYY-MM-DD&date_to=YYYY-MM-DD)
    protected function applyDateRange(
        string $column,
        string $fromParam = 'date_from',
        string $toParam   = 'date_to'
    ): void {
        $from = $this->request->getGet($fromParam);
        $to   = $this->request->getGet($toParam);

        if ($from !== null && $from !== '') {
            $this->model->where("$column >=", $from);
        }
        if ($to !== null && $to !== '') {
            $this->model->where("$column <=", $to . ' 23:59:59');
        }
    }

    // Role filter via WHERE IN subquery on user_roles pivot
    protected function applyRoleFilter(): void
    {
        $roleId = (int) $this->request->getGet('role_id');
        if ($roleId <= 0) {
            return;
        }
        $subQuery = Database::connect()
            ->table('user_roles')
            ->select('user_id')
            ->where('role_id', $roleId)
            ->getCompiledSelect();

        $this->model->where("id IN ($subQuery)");
    }
}
