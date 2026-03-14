<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartmentModel extends Model
{
    protected $table = 'departments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['name', 'description', 'created_by', 'updated_by'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = false;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = null;

    protected $validationRules = [
        'name' => 'required|string|max_length[100]|is_unique[departments.name]',
    ];

    protected array $updateValidationRules = [
        'name'        => 'permit_empty|string|max_length[100]',
        'description' => 'permit_empty|string',
    ];
}