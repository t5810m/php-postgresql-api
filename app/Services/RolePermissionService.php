<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\RolePermissionModel;
use App\Models\RoleModel;
use App\Models\PermissionModel;
use Exception;

class RolePermissionService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->enrichWithNames($this->findRecord(new RolePermissionModel(), $id, 'Role permission'));
    }

    private function enrichWithNames(array $record): array
    {
        if (isset($record['role_id'])) {
            $roleModel = new RoleModel();
            $role = $roleModel->find($record['role_id']);
            $record['role_name'] = $role ? $role['name'] : null;
        }
        if (isset($record['permission_id'])) {
            $permissionModel = new PermissionModel();
            $permission = $permissionModel->find($record['permission_id']);
            $record['permission_name'] = $permission ? $permission['name'] : null;
        }
        return $record;
    }

    public function enrichList(array $records): array
    {
        foreach ($records as &$record) {
            $record = $this->enrichWithNames($record);
        }
        return $records;
    }

    /**
     * @throws Exception
     */
    public function create(array $data, int $userId): array
    {
        $rolePermissionModel = new RolePermissionModel();
        $roleModel = new RoleModel();
        $permissionModel = new PermissionModel();

        $rules = $rolePermissionModel->getValidationRules();
        $this->validate($data, $rules);

        if (!empty($data['role_id']) && !$roleModel->find($data['role_id'])) {
            throw new ValidationException(['Role not found']);
        }
        if (!empty($data['permission_id']) && !$permissionModel->find($data['permission_id'])) {
            throw new ValidationException(['Permission not found']);
        }

        return $this->enrichWithNames($this->insertRecord($rolePermissionModel, $data, $userId));
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $this->findRecord(new RolePermissionModel(), $id, 'Role permission');
        $rolePermissionModel = new RolePermissionModel();

        $rules = $rolePermissionModel->updateValidationRules;
        $this->validate($data, $rules);
        $data['updated_by'] = $userId;

        try {
            $record = $this->updateOnlyChanged($rolePermissionModel, $id, $data);
            return $this->enrichWithNames($record);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            log_message('error', 'RolePermissionService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findOrFail($id);
        try {
            $rolePermissionModel = new RolePermissionModel();
            $rolePermissionModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'RolePermissionService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
