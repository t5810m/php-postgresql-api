<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\TicketCategoryModel;
use Exception;

class TicketCategoryService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->findRecord(new TicketCategoryModel(), $id, 'Ticket category');
    }

    /**
     * @throws Exception
     */
    public function create(array $data, int $userId): array
    {
        $categoryModel = new TicketCategoryModel();
        $rules = $categoryModel->getValidationRules();
        $this->validate($data, $rules);
        return $this->insertRecord($categoryModel, $data, $userId);
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $this->findRecord(new TicketCategoryModel(), $id, 'Ticket category');
        $categoryModel = new TicketCategoryModel();

        $rules = $categoryModel->updateValidationRules;
        $this->validate($data, $rules);
        $data['updated_by'] = $userId;

        try {
            return $this->updateOnlyChanged($categoryModel, $id, $data);
        } catch (Exception $e) {
            log_message('error', 'TicketCategoryService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findRecord(new TicketCategoryModel(), $id, 'Ticket category');
        try {
            $categoryModel = new TicketCategoryModel();
            $categoryModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'TicketCategoryService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
