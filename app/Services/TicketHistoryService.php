<?php

namespace App\Services;

use App\Models\TicketHistoryModel;
use App\Models\TicketModel;
use App\Models\UserModel;

class TicketHistoryService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->enrichWithNames($this->findRecord(new TicketHistoryModel(), $id, 'Ticket history'));
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
}
