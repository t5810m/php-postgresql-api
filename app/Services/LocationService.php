<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\LocationModel;
use Exception;

class LocationService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->findRecord(new LocationModel(), $id, 'Location');
    }

    /**
     * @throws Exception
     */
    public function create(array $data, int $userId): array
    {
        $locationModel = new LocationModel();
        $rules = $locationModel->getValidationRules();
        $this->validate($data, $rules);
        return $this->insertRecord($locationModel, $data, $userId);
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $this->findRecord(new LocationModel(), $id, 'Location');
        $locationModel = new LocationModel();

        $rules = $locationModel->updateValidationRules;
        $this->validate($data, $rules);
        $data['updated_by'] = $userId;

        try {
            return $this->updateOnlyChanged($locationModel, $id, $data);
        } catch (Exception $e) {
            log_message('error', 'LocationService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findRecord(new LocationModel(), $id, 'Location');
        try {
            $locationModel = new LocationModel();
            $locationModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'LocationService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
