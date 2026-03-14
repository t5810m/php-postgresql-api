<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketHistoryModel extends Model
{
    protected $table = 'ticket_history';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['ticket_id', 'action', 'user_id', 'details', 'created_by', 'updated_by'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = null;

    protected $validationRules = [
        'ticket_id' => 'required|integer',
        'action'    => 'required|string|max_length[100]',
        'user_id'   => 'permit_empty|integer',
        'details'   => 'permit_empty|string',
    ];

    protected array $updateValidationRules = [
        'ticket_id' => 'permit_empty|integer',
        'action'    => 'permit_empty|string|max_length[100]',
        'user_id'   => 'permit_empty|integer',
        'details'   => 'permit_empty|string',
    ];
}
