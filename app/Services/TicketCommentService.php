<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\TicketCommentModel;
use App\Models\TicketModel;
use App\Models\UserModel;
use Exception;

class TicketCommentService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->enrichWithNames($this->findRecord(new TicketCommentModel(), $id, 'Ticket comment'));
    }

    private function enrichWithNames(array $record): array
    {
        if (isset($record['ticket_id'])) {
            $ticketModel = new TicketModel();
            $ticket = $ticketModel->find($record['ticket_id']);
            $record['ticket_subject'] = $ticket ? $ticket['subject'] : null;
        }
        if (isset($record['user_id'])) {
            $userModel = new UserModel();
            $user = $userModel->find($record['user_id']);
            $record['user_name'] = $user ? $user['name'] : null;
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
        $commentModel = new TicketCommentModel();
        $ticketModel = new TicketModel();

        $data['user_id'] = $userId;

        $rules = $commentModel->getValidationRules();
        $this->validate($data, $rules);

        if (!empty($data['ticket_id']) && !$ticketModel->find($data['ticket_id'])) {
            throw new ValidationException(['Ticket not found']);
        }

        return $this->enrichWithNames($this->insertRecord($commentModel, $data, $userId));
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $this->findRecord(new TicketCommentModel(), $id, 'Ticket comment');
        $commentModel = new TicketCommentModel();
        $ticketModel = new TicketModel();

        $rules = $commentModel->updateValidationRules;
        $this->validate($data, $rules);

        if (!empty($data['ticket_id']) && !$ticketModel->find($data['ticket_id'])) {
            throw new ValidationException(['Ticket not found']);
        }

        $data['updated_by'] = $userId;

        try {
            $record = $this->updateOnlyChanged($commentModel, $id, $data);
            return $this->enrichWithNames($record);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            log_message('error', 'TicketCommentService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findOrFail($id);
        try {
            $commentModel = new TicketCommentModel();
            $commentModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'TicketCommentService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
