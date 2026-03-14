<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\TicketAssignmentModel;
use App\Models\TicketModel;
use App\Models\TicketHistoryModel;
use App\Models\UserModel;
use Exception;

class TicketAssignmentService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->enrichWithNames($this->findRecord(new TicketAssignmentModel(), $id, 'Ticket assignment'));
    }

    private function enrichWithNames(array $record): array
    {
        if (isset($record['ticket_id'])) {
            $ticketModel = new TicketModel();
            $ticket = $ticketModel->find($record['ticket_id']);
            $record['ticket_subject'] = $ticket ? $ticket['subject'] : null;
        }
        if (isset($record['assigned_to_id'])) {
            $userModel = new UserModel();
            $user = $userModel->find($record['assigned_to_id']);
            $record['assigned_to_name'] = $user ? $user['name'] : null;
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
        $assignmentModel = new TicketAssignmentModel();
        $ticketModel = new TicketModel();
        $historyModel = new TicketHistoryModel();
        $userModel = new UserModel();

        $rules = $assignmentModel->getValidationRules();
        $this->validate($data, $rules);

        if (!empty($data['ticket_id']) && !$ticketModel->find($data['ticket_id'])) {
            throw new ValidationException(['Ticket not found']);
        }
        if (!empty($data['assigned_to_id']) && !$userModel->find($data['assigned_to_id'])) {
            throw new ValidationException(['User not found']);
        }

        $record = $this->insertRecord($assignmentModel, $data, $userId);
        try {
            $ticketModel->update($data['ticket_id'], [
                'assigned_to_id' => $data['assigned_to_id'],
                'updated_by' => $userId,
            ]);
            $historyModel->insert([
                'ticket_id' => $data['ticket_id'],
                'action' => 'ticket_assigned',
                'user_id' => $userId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        } catch (Exception $e) {
            log_message('error', 'TicketAssignmentService::create - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $this->enrichWithNames($record);
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $existing = $this->findRecord(new TicketAssignmentModel(), $id, 'Ticket assignment');
        $assignmentModel = new TicketAssignmentModel();
        $ticketModel = new TicketModel();
        $userModel = new UserModel();

        $rules = $assignmentModel->updateValidationRules;
        $this->validate($data, $rules);

        if (!empty($data['ticket_id']) && !$ticketModel->find($data['ticket_id'])) {
            throw new ValidationException(['Ticket not found']);
        }
        if (!empty($data['assigned_to_id']) && !$userModel->find($data['assigned_to_id'])) {
            throw new ValidationException(['User not found']);
        }

        $data['updated_by'] = $userId;

        try {
            $original = $assignmentModel->find($id);

            $changedData = [];
            foreach ($data as $key => $value) {
                if (!isset($original[$key]) || $original[$key] != $value) {
                    $changedData[$key] = $value;
                }
            }

            if (!empty($changedData)) {
                $assignmentModel->update($id, $changedData);

                if (isset($changedData['assigned_to_id'])) {
                    $ticketModel->update($existing['ticket_id'], [
                        'assigned_to_id' => $changedData['assigned_to_id'],
                        'updated_by' => $userId,
                    ]);
                }
            }

            $record = $assignmentModel->find($id);
            return $this->enrichWithNames($record);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            log_message('error', 'TicketAssignmentService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findOrFail($id);
        $assignment = $record;

        try {
            $assignmentModel = new TicketAssignmentModel();
            $ticketModel = new TicketModel();
            $assignmentModel->delete($id);

            $ticketModel->update($assignment['ticket_id'], [
                'assigned_to_id' => null,
            ]);
        } catch (Exception $e) {
            log_message('error', 'TicketAssignmentService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }

        return $record;
    }
}
