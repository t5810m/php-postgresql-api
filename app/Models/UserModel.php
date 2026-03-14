<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['name', 'email', 'password', 'phone', 'department_id', 'location_id', 'is_active', 'created_by', 'updated_by'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = null;

    protected $validationRules = [
        'name'           => 'required|string|max_length[150]',
        'email'          => 'required|valid_email|is_unique[users.email]',
        'password'       => 'required|string|min_length[8]',
        'phone'          => 'permit_empty|string|max_length[20]',
        'department_id'  => 'permit_empty|integer',
        'location_id'    => 'permit_empty|integer',
        'is_active'      => 'permit_empty|in_list[0,1]',
    ];

    protected array $updateValidationRules = [
        'name'           => 'permit_empty|string|max_length[150]',
        'email'          => 'permit_empty|valid_email',
        'password'       => 'permit_empty|string|min_length[8]',
        'phone'          => 'permit_empty|string|max_length[20]',
        'department_id'  => 'permit_empty|integer',
        'location_id'    => 'permit_empty|integer',
        'is_active'      => 'permit_empty|in_list[0,1]',
    ];

}
