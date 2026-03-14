<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\TicketPriorityModel;
use Exception;

class TicketPriorityService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->findRecord(new TicketPriorityModel(), $id, 'Ticket priority');
    }

    /**
     * @throws Exception
     */
    public function create(array $data, int $userId): array
    {
        $priorityModel = new TicketPriorityModel();
        $rules = $priorityModel->getValidationRules();
        $this->validate($data, $rules);
        return $this->insertRecord($priorityModel, $data, $userId);
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $this->findRecord(new TicketPriorityModel(), $id, 'Ticket priority');
        $priorityModel = new TicketPriorityModel();

        $rules = $priorityModel->updateValidationRules;
        $this->validate($data, $rules);
        $data['updated_by'] = $userId;

        try {
            return $this->updateOnlyChanged($priorityModel, $id, $data);
        } catch (Exception $e) {
            log_message('error', 'TicketPriorityService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findRecord(new TicketPriorityModel(), $id, 'Ticket priority');
        try {
            $priorityModel = new TicketPriorityModel();
            $priorityModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'TicketPriorityService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
