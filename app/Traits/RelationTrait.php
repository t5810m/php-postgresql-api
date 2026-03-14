<?php

namespace App\Traits;

trait RelationTrait
{
    /**
     * Eager load related data (no N+1 queries).
     *
     * Usage in controller:
     *   $tickets = $this->model->paginate(...);
     *   $this->loadIncludes($tickets, $this->relations);
     *
     * Or for single record:
     *   $ticket = $this->model->find($id);
     *   $singleRecord = [$ticket];
     *   $this->loadIncludes($singleRecord, $this->relations, true);
     *   $ticket = $singleRecord[0];
     *
     * @param array &$records       Records to load relations into (passed by reference)
     * @param array $relationMap    Full relations map from controller
     * @param bool  $isSingle       Whether this is a single record (not used in bulk, but kept for clarity)
     */
    protected function loadIncludes(array &$records, array $relationMap, bool $isSingle = false): void
    {
        if (empty($records)) {
            return;
        }

        // Get requested includes from query param
        $includeParam = $this->request->getGet('include');
        if ($includeParam === null || $includeParam === '') {
            return; // No includes requested
        }

        // Parse comma-separated includes
        $requestedIncludes = array_filter(
            array_map('trim', explode(',', $includeParam))
        );
        if (empty($requestedIncludes)) {
            return;
        }

        // Filter to only allowed relations
        $validIncludes = array_intersect_key($relationMap, array_flip($requestedIncludes));

        // Load each relation
        foreach ($validIncludes as $relationName => $relationConfig) {
            $this->loadRelation($records, $relationName, $relationConfig);
        }
    }

    /**
     * Load a single relation into records.
     *
     * @param array &$records          Records to load into
     * @param string $relationName      Name of the relation
     * @param array $relationConfig     Relation config from relations map
     */
    private function loadRelation(array &$records, string $relationName, array $relationConfig): void
    {
        $type = $relationConfig['type'] ?? null;

        if ($type === 'belongs_to') {
            $this->loadBelongsTo($records, $relationName, $relationConfig);
        } elseif ($type === 'has_many') {
            $this->loadHasMany($records, $relationName, $relationConfig);
        }
    }

    /**
     * Load a belongs-to relationship.
     * Foreign key is on THIS table, pointing to RELATED table.
     *
     * Config:
     *   'user' => ['type' => 'belongs_to', 'model' => UserModel::class, 'fk' => 'user_id', 'key' => 'id']
     */
    private function loadBelongsTo(array &$records, string $relationName, array $relationConfig): void
    {
        $modelClass = $relationConfig['model'];
        $fkColumn = $relationConfig['fk'];      // Foreign key on THIS table
        $pkColumn = $relationConfig['key'];     // Primary key on RELATED table

        // Collect all foreign key values from records
        $fkValues = [];
        foreach ($records as $record) {
            if (isset($record[$fkColumn]) && $record[$fkColumn] !== null) {
                $fkValues[] = $record[$fkColumn];
            }
        }

        if (empty($fkValues)) {
            return;
        }

        // Fetch related records in bulk
        $model = new $modelClass();
        $relatedRecords = $model->whereIn($pkColumn, array_unique($fkValues))->findAll();

        // Index related records by primary key for fast lookup
        $relatedByPk = [];
        foreach ($relatedRecords as $related) {
            $relatedByPk[$related[$pkColumn]] = $related;
        }

        // Attach related data to records
        foreach ($records as &$record) {
            if (isset($record[$fkColumn]) && isset($relatedByPk[$record[$fkColumn]])) {
                $record[$relationName] = $relatedByPk[$record[$fkColumn]];
            } else {
                $record[$relationName] = null;
            }
        }
    }

    /**
     * Load a has-many relationship.
     * Foreign key is on RELATED table, pointing back to THIS table.
     *
     * Config:
     *   'comments' => ['type' => 'has_many', 'model' => TicketCommentModel::class, 'fk' => 'ticket_id', 'key' => 'id']
     */
    private function loadHasMany(array &$records, string $relationName, array $relationConfig): void
    {
        $modelClass = $relationConfig['model'];
        $fkColumn = $relationConfig['fk'];      // Foreign key on RELATED table
        $pkColumn = $relationConfig['key'];     // Primary key on THIS table

        // Collect all primary key values from records
        $pkValues = [];
        foreach ($records as $record) {
            if (isset($record[$pkColumn])) {
                $pkValues[] = $record[$pkColumn];
            }
        }

        if (empty($pkValues)) {
            return;
        }

        // Fetch related records in bulk
        $model = new $modelClass();
        $relatedRecords = $model->whereIn($fkColumn, array_unique($pkValues))->findAll();

        // Group related records by foreign key
        $relatedByFk = [];
        foreach ($relatedRecords as $related) {
            $fkValue = $related[$fkColumn];
            if (!isset($relatedByFk[$fkValue])) {
                $relatedByFk[$fkValue] = [];
            }
            $relatedByFk[$fkValue][] = $related;
        }

        // Attach related data to records
        foreach ($records as &$record) {
            $pkValue = $record[$pkColumn];
            if (isset($relatedByFk[$pkValue])) {
                $record[$relationName] = $relatedByFk[$pkValue];
            } else {
                $record[$relationName] = [];
            }
        }
    }
}
