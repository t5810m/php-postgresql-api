<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketAssignmentModel extends Model
{
    protected $table = 'ticket_assignments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['ticket_id', 'assigned_to_id', 'assigned_at', 'created_by', 'updated_by'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = null;

    protected $validationRules = [
        'ticket_id'      => 'required|integer',
        'assigned_to_id' => 'required|integer',
        'assigned_at'    => 'permit_empty|valid_date',
    ];

    protected array $updateValidationRules = [
        'ticket_id'      => 'permit_empty|integer',
        'assigned_to_id' => 'permit_empty|integer',
        'assigned_at'    => 'permit_empty|valid_date',
    ];
}
