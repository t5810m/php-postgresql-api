<?php

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use Config\Services;
use Exception;

abstract class BaseService
{
    protected function validate(array $data, array $rules): void
    {
        $validator = Services::validation();
        $validator->reset();
        $validator->setRules($rules);
        if (!$validator->run($data)) {
            throw new ValidationException($validator->getErrors());
        }
    }

    protected function handleDatabaseException(Exception $e): string
    {
        $message = $e->getMessage();

        // Unique constraint violation
        if (str_contains($message, 'duplicate key')) {
            if (str_contains($message, 'email')) {
                return 'Email address already exists in the system';
            }
            if (str_contains($message, 'name')) {
                return 'Name already exists in the system';
            }
            if (str_contains($message, 'role_id_permission_id')) {
                return 'This role-permission combination already exists';
            }
            if (str_contains($message, 'user_id_role_id')) {
                return 'This user-role combination already exists';
            }
            return 'This record already exists in the system';
        }

        // Foreign key constraint violation - delete blocked (record is referenced by children)
        if (str_contains($message, 'foreign key') && str_contains($message, 'RESTRICT')) {
            if (str_contains($message, 'ticket_history')) {
                return 'Cannot delete ticket: it has history entries';
            }
            if (str_contains($message, 'ticket_comments')) {
                return 'Cannot delete ticket: it has comments';
            }
            if (str_contains($message, 'ticket_attachments')) {
                return 'Cannot delete ticket: it has attachments';
            }
            if (str_contains($message, 'ticket_assignments')) {
                return 'Cannot delete ticket: it has assignments';
            }
            return 'Cannot delete record: it is referenced by other records';
        }

        // Foreign key constraint violation - insert/update (referenced record does not exist)
        if (str_contains($message, 'foreign key')) {
            if (str_contains($message, 'user_id')) {
                return 'Referenced user does not exist';
            }
            if (str_contains($message, 'role_id')) {
                return 'Referenced role does not exist';
            }
            if (str_contains($message, 'permission_id')) {
                return 'Referenced permission does not exist';
            }
            if (str_contains($message, 'category_id')) {
                return 'Referenced category does not exist';
            }
            if (str_contains($message, 'priority_id')) {
                return 'Referenced priority does not exist';
            }
            if (str_contains($message, 'status_id')) {
                return 'Referenced status does not exist';
            }
            if (str_contains($message, 'department_id')) {
                return 'Referenced department does not exist';
            }
            if (str_contains($message, 'location_id')) {
                return 'Referenced location does not exist';
            }
            if (str_contains($message, 'ticket_id')) {
                return 'Referenced ticket does not exist';
            }
            return 'Referenced record does not exist or has been deleted';
        }

        // NOT NULL constraint violation
        if (str_contains($message, 'null value') || str_contains($message, 'NOT NULL')) {
            return 'Required field is missing or empty';
        }

        // Default to original message with context
        return "Failed to save record: {$message}";
    }

    protected function findRecord($model, int $id, string $entityName = 'Record'): array
    {
        $record = $model->find($id);
        if (!$record) {
            throw new NotFoundException("{$entityName} not found");
        }
        return $record;
    }

    protected function insertRecord($model, array $data, int $userId): array
    {
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        try {
            $id = $model->insert($data);
            if (!$id) {
                throw new ValidationException(['Failed to create record']);
            }
            $record = $model->find($id);
            if (!$record) {
                throw new ValidationException(['Record was not created in database']);
            }
            return $record;
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    protected function updateOnlyChanged($model, int $id, array $data)
    {
        $original = $model->find($id);

        $changedData = [];
        foreach ($data as $key => $value) {
            if (!isset($original[$key]) || $original[$key] != $value) {
                $changedData[$key] = $value;
            }
        }

        if (!empty($changedData)) {
            $model->update($id, $changedData);
        }

        return $model->find($id);
    }
}
