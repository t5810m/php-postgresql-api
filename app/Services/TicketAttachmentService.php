<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\TicketAttachmentModel;
use App\Models\TicketModel;
use Exception;

class TicketAttachmentService extends BaseService
{
    protected TicketAttachmentModel $attachmentModel;
    protected TicketModel $ticketModel;

    protected function getAttachmentModel(): TicketAttachmentModel
    {
        if (!isset($this->attachmentModel)) {
            $this->attachmentModel = new TicketAttachmentModel();
        }
        return $this->attachmentModel;
    }

    protected function getTicketModel(): TicketModel
    {
        if (!isset($this->ticketModel)) {
            $this->ticketModel = new TicketModel();
        }
        return $this->ticketModel;
    }

    private function enrichWithNames(array $record): array
    {
        if (isset($record['ticket_id'])) {
            $ticket = $this->getTicketModel()->find($record['ticket_id']);
            $record['ticket_subject'] = $ticket ? $ticket['subject'] : null;
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

    public function findOrFail(int $id): array
    {
        return $this->enrichWithNames($this->findRecord(new TicketAttachmentModel(), $id, 'Ticket attachment'));
    }

    /**
     * @throws Exception
     */
    public function create(array $data, int $userId): array
    {
        $attachmentModel = $this->getAttachmentModel();
        $ticketModel = $this->getTicketModel();

        $rules = $attachmentModel->getValidationRules();
        $this->validate($data, $rules);

        if (!empty($data['ticket_id']) && !$ticketModel->find($data['ticket_id'])) {
            throw new ValidationException(['Ticket not found']);
        }

        return $this->enrichWithNames($this->insertRecord($attachmentModel, $data, $userId));
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $this->findOrFail($id);
        $freshModel = new TicketAttachmentModel();
        $ticketModel = new TicketModel();

        $rules = $freshModel->updateValidationRules;
        $this->validate($data, $rules);

        if (!empty($data['ticket_id']) && !$ticketModel->find($data['ticket_id'])) {
            throw new ValidationException(['Ticket not found']);
        }

        $data['updated_by'] = $userId;

        try {
            $original = $freshModel->find($id);

            $changedData = [];
            foreach ($data as $key => $value) {
                if (!isset($original[$key]) || $original[$key] != $value) {
                    $changedData[$key] = $value;
                }
            }

            if (!empty($changedData)) {
                $freshModel->update($id, $changedData);
            }

            $record = $freshModel->find($id);
            return $this->enrichWithNames($record);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            log_message('error', 'TicketAttachmentService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findOrFail($id);
        try {
            $freshModel = new TicketAttachmentModel();
            $freshModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'TicketAttachmentService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
