<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\RoleModel;
use Exception;

class RoleService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->findRecord(new RoleModel(), $id, 'Role');
    }

    /**
     * @throws Exception
     */
    public function create(array $data, int $userId): array
    {
        $roleModel = new RoleModel();
        $rules = $roleModel->getValidationRules();
        $this->validate($data, $rules);
        return $this->insertRecord($roleModel, $data, $userId);
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $this->findRecord(new RoleModel(), $id, 'Role');
        $roleModel = new RoleModel();

        $rules = $roleModel->updateValidationRules;
        $this->validate($data, $rules);
        $data['updated_by'] = $userId;

        try {
            return $this->updateOnlyChanged($roleModel, $id, $data);
        } catch (Exception $e) {
            log_message('error', 'RoleService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findRecord(new RoleModel(), $id, 'Role');
        try {
            $roleModel = new RoleModel();
            $roleModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'RoleService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
