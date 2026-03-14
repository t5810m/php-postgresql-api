<?php

namespace App\Models;

use CodeIgniter\Model;

class LocationModel extends Model
{
    protected $table = 'locations';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['name', 'country', 'created_by', 'updated_by'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = null;

    protected $validationRules = [
        'name'    => 'required|string|max_length[100]',
        'country' => 'required|string|max_length[100]',
    ];

    protected array $updateValidationRules = [
        'name'    => 'permit_empty|string|max_length[100]',
        'country' => 'permit_empty|string|max_length[100]',
    ];
}
