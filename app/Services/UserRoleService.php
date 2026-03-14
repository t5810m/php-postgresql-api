<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\UserRoleModel;
use App\Models\UserModel;
use App\Models\RoleModel;
use Exception;

class UserRoleService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->enrichWithNames($this->findRecord(new UserRoleModel(), $id, 'User role'));
    }

    private function enrichWithNames(array $record): array
    {
        if (isset($record['user_id'])) {
            $userModel = new UserModel();
            $user = $userModel->find($record['user_id']);
            $record['user_name'] = $user ? $user['name'] : null;
        }
        if (isset($record['role_id'])) {
            $roleModel = new RoleModel();
            $role = $roleModel->find($record['role_id']);
            $record['role_name'] = $role ? $role['name'] : null;
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
        $userRoleModel = new UserRoleModel();
        $userModel = new UserModel();
        $roleModel = new RoleModel();

        $rules = $userRoleModel->getValidationRules();
        $this->validate($data, $rules);

        if (!empty($data['user_id']) && !$userModel->find($data['user_id'])) {
            throw new ValidationException(['User not found']);
        }
        if (!empty($data['role_id']) && !$roleModel->find($data['role_id'])) {
            throw new ValidationException(['Role not found']);
        }

        return $this->enrichWithNames($this->insertRecord($userRoleModel, $data, $userId));
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $this->findRecord(new UserRoleModel(), $id, 'User role');
        $userRoleModel = new UserRoleModel();
        $userModel = new UserModel();
        $roleModel = new RoleModel();

        $rules = $userRoleModel->updateValidationRules;
        $this->validate($data, $rules);

        if (!empty($data['user_id']) && !$userModel->find($data['user_id'])) {
            throw new ValidationException(['User not found']);
        }
        if (!empty($data['role_id']) && !$roleModel->find($data['role_id'])) {
            throw new ValidationException(['Role not found']);
        }

        $data['updated_by'] = $userId;

        try {
            $record = $this->updateOnlyChanged($userRoleModel, $id, $data);
            return $this->enrichWithNames($record);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            log_message('error', 'UserRoleService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findOrFail($id);
        try {
            $userRoleModel = new UserRoleModel();
            $userRoleModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'UserRoleService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
