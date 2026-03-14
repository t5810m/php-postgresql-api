<?php

namespace App\Models;

use CodeIgniter\Model;

class TicketAttachmentModel extends Model
{
    protected $table = 'ticket_attachments';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = ['ticket_id', 'file_name', 'file_path', 'created_by', 'updated_by'];
    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = null;

    protected $validationRules = [
        'ticket_id'  => 'required|integer',
        'file_name'  => 'required|string|max_length[255]',
        'file_path'  => 'required|string|max_length[500]',
    ];

    protected array $updateValidationRules = [
        'file_name'  => 'permit_empty|string|max_length[255]',
        'file_path'  => 'permit_empty|string|max_length[500]',
    ];
}
