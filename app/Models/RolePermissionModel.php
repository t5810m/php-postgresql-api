<?php

namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table = 'role_permissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['role_id', 'permission_id', 'created_by', 'updated_by'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = null;

    protected $validationRules = [
        'role_id'       => 'required|integer',
        'permission_id' => 'required|integer',
    ];

    protected array $updateValidationRules = [
        'role_id'       => 'permit_empty|integer',
        'permission_id' => 'permit_empty|integer',
    ];
}
