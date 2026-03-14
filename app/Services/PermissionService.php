<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\PermissionModel;
use Exception;

class PermissionService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->findRecord(new PermissionModel(), $id, 'Permission');
    }

    /**
     * @throws Exception
     */
    public function create(array $data, int $userId): array
    {
        $permissionModel = new PermissionModel();
        $rules = $permissionModel->getValidationRules();
        $this->validate($data, $rules);
        return $this->insertRecord($permissionModel, $data, $userId);
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $this->findRecord(new PermissionModel(), $id, 'Permission');
        $permissionModel = new PermissionModel();

        $rules = $permissionModel->updateValidationRules;
        $this->validate($data, $rules);
        $data['updated_by'] = $userId;

        try {
            return $this->updateOnlyChanged($permissionModel, $id, $data);
        } catch (Exception $e) {
            log_message('error', 'PermissionService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findRecord(new PermissionModel(), $id, 'Permission');
        try {
            $permissionModel = new PermissionModel();
            $permissionModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'PermissionService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
