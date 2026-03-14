<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\TicketModel;
use App\Models\TicketHistoryModel;
use App\Models\UserModel;
use App\Models\TicketCategoryModel;
use App\Models\TicketPriorityModel;
use App\Models\TicketStatusModel;
use App\Models\DepartmentModel;
use App\Models\LocationModel;
use Exception;

class TicketService extends BaseService
{
    public function findOrFail(int $id): array
    {
        return $this->enrichWithNames($this->findRecord(new TicketModel(), $id, 'Ticket'));
    }

    private function enrichWithNames(array $record): array
    {
        if (isset($record['category_id'])) {
            $categoryModel = new TicketCategoryModel();
            $category = $categoryModel->find($record['category_id']);
            $record['category_name'] = $category ? $category['name'] : null;
        }
        if (isset($record['priority_id'])) {
            $priorityModel = new TicketPriorityModel();
            $priority = $priorityModel->find($record['priority_id']);
            $record['priority_name'] = $priority ? $priority['name'] : null;
        }
        if (isset($record['status_id'])) {
            $statusModel = new TicketStatusModel();
            $status = $statusModel->find($record['status_id']);
            $record['status_name'] = $status ? $status['name'] : null;
        }
        if (isset($record['submitted_by'])) {
            $userModel = new UserModel();
            $user = $userModel->find($record['submitted_by']);
            $record['submitted_by_name'] = $user ? $user['name'] : null;
        }
        if (isset($record['assigned_to_id']) && $record['assigned_to_id']) {
            $userModel = new UserModel();
            $assignee = $userModel->find($record['assigned_to_id']);
            $record['assigned_to_name'] = $assignee ? $assignee['name'] : null;
        }
        if (isset($record['department_id']) && $record['department_id']) {
            $departmentModel = new DepartmentModel();
            $department = $departmentModel->find($record['department_id']);
            $record['department_name'] = $department ? $department['name'] : null;
        }
        if (isset($record['location_id']) && $record['location_id']) {
            $locationModel = new LocationModel();
            $location = $locationModel->find($record['location_id']);
            $record['location_name'] = $location ? $location['name'] : null;
        }
        return $record;
    }

    public function enrichList(array $records): array
    {
        foreach ($records as &$record) {
            $record = $this->enrichWithNames($record);
        }
        return $records;
    }

    /**
     * @throws Exception
     */
    public function create(array $data, int $userId): array
    {
        $ticketModel = new TicketModel();
        $historyModel = new TicketHistoryModel();
        $userModel = new UserModel();
        $categoryModel = new TicketCategoryModel();
        $priorityModel = new TicketPriorityModel();
        $statusModel = new TicketStatusModel();
        $departmentModel = new DepartmentModel();
        $locationModel = new LocationModel();

        $data['submitted_by'] = $userId;

        $rules = $ticketModel->getValidationRules();
        $this->validate($data, $rules);

        if (!empty($data['category_id']) && !$categoryModel->find($data['category_id'])) {
            throw new ValidationException(['Category not found']);
        }
        if (!empty($data['priority_id']) && !$priorityModel->find($data['priority_id'])) {
            throw new ValidationException(['Priority not found']);
        }
        if (!empty($data['status_id']) && !$statusModel->find($data['status_id'])) {
            throw new ValidationException(['Status not found']);
        }
        if (!empty($data['assigned_to_id']) && !$userModel->find($data['assigned_to_id'])) {
            throw new ValidationException(['Assigned user not found']);
        }
        if (!empty($data['department_id']) && !$departmentModel->find($data['department_id'])) {
            throw new ValidationException(['Department not found']);
        }
        if (!empty($data['location_id']) && !$locationModel->find($data['location_id'])) {
            throw new ValidationException(['Location not found']);
        }

        $record = $this->insertRecord($ticketModel, $data, $userId);
        try {
            $historyModel->insert([
                'ticket_id' => $record['id'],
                'action' => 'ticket_created',
                'user_id' => $userId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        } catch (Exception $e) {
            log_message('error', 'TicketService::create - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $this->enrichWithNames($record);
    }

    /**
     * @throws Exception
     */
    public function update(int $id, array $data, int $userId): array
    {
        $existing = $this->findRecord(new TicketModel(), $id, 'Ticket');
        $ticketModel = new TicketModel();
        $historyModel = new TicketHistoryModel();
        $userModel = new UserModel();
        $categoryModel = new TicketCategoryModel();
        $priorityModel = new TicketPriorityModel();
        $statusModel = new TicketStatusModel();
        $departmentModel = new DepartmentModel();
        $locationModel = new LocationModel();

        $rules = [
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
        $this->validate($data, $rules);

        if (!empty($data['category_id']) && !$categoryModel->find($data['category_id'])) {
            throw new ValidationException(['Category not found']);
        }
        if (!empty($data['priority_id']) && !$priorityModel->find($data['priority_id'])) {
            throw new ValidationException(['Priority not found']);
        }
        if (!empty($data['status_id']) && !$statusModel->find($data['status_id'])) {
            throw new ValidationException(['Status not found']);
        }
        if (!empty($data['assigned_to_id']) && !$userModel->find($data['assigned_to_id'])) {
            throw new ValidationException(['Assigned user not found']);
        }
        if (!empty($data['department_id']) && !$departmentModel->find($data['department_id'])) {
            throw new ValidationException(['Department not found']);
        }
        if (!empty($data['location_id']) && !$locationModel->find($data['location_id'])) {
            throw new ValidationException(['Location not found']);
        }

        $data['updated_by'] = $userId;

        try {
            $original = $ticketModel->find($id);

            $changedData = [];
            foreach ($data as $key => $value) {
                if (!isset($original[$key]) || $original[$key] != $value) {
                    $changedData[$key] = $value;
                }
            }

            if (!empty($changedData)) {
                $ticketModel->update($id, $changedData);

                if (isset($changedData['status_id']) && (int) $existing['status_id'] !== (int) $changedData['status_id']) {
                    $historyModel->insert([
                        'ticket_id' => $id,
                        'action' => 'status_changed',
                        'user_id' => $userId,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }

                if (isset($changedData['assigned_to_id']) && (int) ($existing['assigned_to_id'] ?? null) !== (int) ($changedData['assigned_to_id'] ?? null)) {
                    $historyModel->insert([
                        'ticket_id' => $id,
                        'action' => 'reassigned',
                        'user_id' => $userId,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }
            }

            $record = $ticketModel->find($id);
            return $this->enrichWithNames($record);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            log_message('error', 'TicketService::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
    }

    public function delete(int $id): array
    {
        $record = $this->findOrFail($id);
        try {
            $ticketModel = new TicketModel();
            $ticketModel->delete($id);
        } catch (Exception $e) {
            log_message('error', 'TicketService::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            throw new ValidationException([$this->handleDatabaseException($e)]);
        }
        return $record;
    }
}
