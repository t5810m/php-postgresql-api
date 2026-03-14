<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\UserModel;
use App\Models\DepartmentModel;
use App\Models\LocationModel;
use Exception;

class UserService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->enrichWithNames($this->findRecord(new UserModel(), $id, 'User'));
    }

    private function enrichWithNames(array $record): array
    {
        if (isset($record['department_id']) && $record['department_id']) {
            $departmentModel = new DepartmentModel();
            $department = $departmentModel->find($record['department_id']);
            $record['department_name'] = $department ? $department['name'] : null;
        }
        if (isset($record['location_id']) && $record['location_id']) {
            $locationModel = new LocationModel();
            $location = $locationModel->find($record['location_id']);
            $record['location_name'] = $location ? $location['name'] : null;
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
        $userModel = new UserModel();
        $departmentModel = new DepartmentModel();
        $locationModel = new LocationModel();

        $rules = $userModel->getValidationRules();
        $this->validate($data, $rules);

        if (!empty($data['department_id']) && !$departmentModel->find($data['department_id'])) {
            throw new ValidationException(['Department not found']);
        }
        if (!empty($data['location_id']) && !$locationModel->find($data['location_id'])) {
            throw new ValidationException(['Location not found']);
        }

        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

        return $this->enrichWithNames($this->insertRecord($userModel, $data, $userId));
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $this->findRecord(new UserModel(), $id, 'User');
        $userModel = new UserModel();
        $departmentModel = new DepartmentModel();
        $locationModel = new LocationModel();

        $rules = $userModel->updateValidationRules;
        $this->validate($data, $rules);

        if (isset($data['password'])) {
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
            } else {
                unset($data['password']);
            }
        }

        $data['updated_by'] = $userId;

        try {
            $record = $this->updateOnlyChanged($userModel, $id, $data);
            return $this->enrichWithNames($record);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            log_message('error', 'UserService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findOrFail($id);
        try {
            $userModel = new UserModel();
            $userModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'UserService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
