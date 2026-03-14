<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\TicketStatusModel;
use Exception;

class TicketStatusService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->findRecord(new TicketStatusModel(), $id, 'Ticket status');
    }

    /**
     * @throws Exception
     */
    public function create(array $data, int $userId): array
    {
        $statusModel = new TicketStatusModel();
        $rules = $statusModel->getValidationRules();
        $this->validate($data, $rules);
        return $this->insertRecord($statusModel, $data, $userId);
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $this->findRecord(new TicketStatusModel(), $id, 'Ticket status');
        $statusModel = new TicketStatusModel();

        $rules = $statusModel->updateValidationRules;
        $this->validate($data, $rules);
        $data['updated_by'] = $userId;

        try {
            return $this->updateOnlyChanged($statusModel, $id, $data);
        } catch (Exception $e) {
            log_message('error', 'TicketStatusService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findRecord(new TicketStatusModel(), $id, 'Ticket status');
        try {
            $statusModel = new TicketStatusModel();
            $statusModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'TicketStatusService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
