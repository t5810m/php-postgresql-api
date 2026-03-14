<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\DepartmentModel;
use Exception;

class DepartmentService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->findRecord(new DepartmentModel(), $id, 'Department');
    }

    /**
     * @throws Exception
     */
    public function create(array $data, int $userId): array
    {
        $departmentModel = new DepartmentModel();
        $rules = $departmentModel->getValidationRules();
        $this->validate($data, $rules);
        return $this->insertRecord($departmentModel, $data, $userId);
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $this->findRecord(new DepartmentModel(), $id, 'Department');
        $departmentModel = new DepartmentModel();

        $rules = $departmentModel->updateValidationRules;
        $this->validate($data, $rules);
        $data['updated_by'] = $userId;

        try {
            return $this->updateOnlyChanged($departmentModel, $id, $data);
        } catch (Exception $e) {
            log_message('error', 'DepartmentService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findRecord(new DepartmentModel(), $id, 'Department');
        try {
            $departmentModel = new DepartmentModel();
            $departmentModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'DepartmentService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
