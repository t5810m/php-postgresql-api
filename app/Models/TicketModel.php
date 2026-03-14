<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketModel extends Model
{
    protected $table = 'tickets';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'subject',
        'description',
        'submitted_by',
        'category_id',
        'priority_id',
        'status_id',
        'assigned_to_id',
        'department_id',
        'location_id',
        'resolved_at',
        'closed_at',
        'created_by',
        'updated_by',
    ];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = null;

    protected $validationRules = [
        'subject'      => 'required|string|max_length[255]',
        'description'  => 'required|string',
        'category_id'  => 'required|integer',
        'priority_id'  => 'required|integer',
        'status_id'    => 'required|integer',
        'assigned_to_id' => 'permit_empty|integer',
        'department_id'  => 'permit_empty|integer',
        'location_id'    => 'permit_empty|integer',
        'resolved_at'    => 'permit_empty|valid_date',
        'closed_at'      => 'permit_empty|valid_date',
    ];

    protected array $updateValidationRules = [
        'subject'      => 'permit_empty|string|max_length[255]',
        'description'  => 'permit_empty|string',
        'category_id'  => 'permit_empty|integer',
        'priority_id'  => 'permit_empty|integer',
        'status_id'    => 'permit_empty|integer',
        'assigned_to_id' => 'permit_empty|integer',
        'department_id'  => 'permit_empty|integer',
        'location_id'    => 'permit_empty|integer',
        'resolved_at'    => 'permit_empty|valid_date',
        'closed_at'      => 'permit_empty|valid_date',
    ];
}
