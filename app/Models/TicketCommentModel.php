<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketCommentModel extends Model
{
    protected $table = 'ticket_comments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['ticket_id', 'user_id', 'comment', 'created_by', 'updated_by'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = null;

    protected $validationRules = [
        'ticket_id' => 'required|integer',
        'comment'   => 'required|string',
    ];

    protected array $updateValidationRules = [
        'ticket_id' => 'permit_empty|integer',
        'comment'   => 'permit_empty|string',
    ];
}
