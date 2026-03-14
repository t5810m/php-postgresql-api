<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketCategoryModel extends Model
{
    protected $table = 'ticket_categories';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['name', 'description', 'created_by', 'updated_by'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = null;

    protected $validationRules = [
        'name'        => 'required|string|max_length[100]|is_unique[ticket_categories.name]',
        'description' => 'permit_empty|string',
    ];

    protected array $updateValidationRules = [
        'name'        => 'permit_empty|string|max_length[100]',
        'description' => 'permit_empty|string',
    ];
}
