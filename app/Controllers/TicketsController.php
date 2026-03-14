<?php

namespace App\Controllers;

use App\Models\TicketModel;
use App\Models\UserModel;
use App\Models\TicketStatusModel;
use App\Models\TicketPriorityModel;
use App\Models\TicketCategoryModel;
use App\Models\DepartmentModel;
use App\Models\TicketCommentModel;
use App\Models\TicketAttachmentModel;
use App\Models\TicketHistoryModel;
use App\Services\TicketService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Traits\PaginationTrait;
use App\Traits\SortingTrait;
use App\Traits\FilterTrait;
use App\Traits\AggregationTrait;
use App\Traits\RelationTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Ticket',
    required: ['id', 'subject', 'submitted_by', 'status_id', 'priority_id', 'category_id'],
    properties: [
        new OA\Property(property: 'id', type: 'string'),
        new OA\Property(property: 'subject', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'submitted_by', type: 'string'),
        new OA\Property(property: 'assigned_to_id', type: 'string'),
        new OA\Property(property: 'status_id', type: 'string'),
        new OA\Property(property: 'priority_id', type: 'string'),
        new OA\Property(property: 'category_id', type: 'string'),
        new OA\Property(property: 'department_id', type: 'string'),
        new OA\Property(property: 'location_id', type: 'string'),
        new OA\Property(property: 'submitted_by_name', type: 'string'),
        new OA\Property(property: 'assigned_to_name', type: 'string'),
        new OA\Property(property: 'status_name', type: 'string'),
        new OA\Property(property: 'priority_name', type: 'string'),
        new OA\Property(property: 'category_name', type: 'string'),
        new OA\Property(property: 'department_name', type: 'string'),
        new OA\Property(property: 'location_name', type: 'string'),
        new OA\Property(property: 'created_by', type: 'string'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_by', type: 'string'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class TicketsController extends BaseController
{
    use PaginationTrait;
    use FilterTrait;
    use SortingTrait;
    use AggregationTrait;
    use RelationTrait;

    protected string $format = 'json';
    protected TicketModel $model;
    protected TicketService $service;

    protected array $relations = [
        'user'        => ['type' => 'belongs_to', 'model' => UserModel::class,           'fk' => 'submitted_by',   'key' => 'id'],
        'assignee'    => ['type' => 'belongs_to', 'model' => UserModel::class,           'fk' => 'assigned_to_id', 'key' => 'id'],
        'status'      => ['type' => 'belongs_to', 'model' => TicketStatusModel::class,   'fk' => 'status_id',      'key' => 'id'],
        'priority'    => ['type' => 'belongs_to', 'model' => TicketPriorityModel::class, 'fk' => 'priority_id',    'key' => 'id'],
        'category'    => ['type' => 'belongs_to', 'model' => TicketCategoryModel::class, 'fk' => 'category_id',    'key' => 'id'],
        'department'  => ['type' => 'belongs_to', 'model' => DepartmentModel::class,     'fk' => 'department_id',  'key' => 'id'],
        'comments'    => ['type' => 'has_many',   'model' => TicketCommentModel::class,  'fk' => 'ticket_id',      'key' => 'id'],
        'attachments' => ['type' => 'has_many',   'model' => TicketAttachmentModel::class, 'fk' => 'ticket_id',   'key' => 'id'],
        'history'     => ['type' => 'has_many',   'model' => TicketHistoryModel::class,  'fk' => 'ticket_id',      'key' => 'id'],
    ];

    public function __construct()
    {
        $this->model   = new TicketModel();
        $this->service = new TicketService();
    }

    #[OA\Get(
        path: '/tickets',
        summary: 'List tickets',
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status_id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'priority_id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'assigned_to_id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'department_id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['id', 'subject', 'status_id', 'priority_id', 'category_id', 'submitted_by', 'department_id', 'created_at', 'updated_at'])),
            new OA\Parameter(name: 'order', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
        ]
    )]
    public function index(): ResponseInterface
    {
        try {
            $this->applyExactFilters([
                'status_id'      => 'status_id',
                'priority_id'    => 'priority_id',
                'category_id'    => 'category_id',
                'assigned_to_id' => 'assigned_to_id',
                'department_id'  => 'department_id',
            ]);
            $this->applySearch(['subject', 'description']);
            $this->applyDateRange('created_at');
            $this->applySort(['id', 'subject', 'status_id', 'priority_id', 'category_id', 'submitted_by', 'department_id', 'created_at', 'updated_at']);

            $perPage = $this->getPerPage();
            $page    = $this->getPage();

            $tickets = $this->model->paginate($perPage, 'default', $page);

            $tickets = $this->service->enrichList($tickets);
            $this->loadIncludes($tickets, $this->relations);

            return $this->respond([
                'success' => true,
                'data'    => $tickets,
                'message' => 'Tickets retrieved successfully',
                'meta'    => $this->buildMeta(),
            ], ResponseInterface::HTTP_OK);
        } catch (Exception $e) {
            log_message('error', 'TicketsController::index - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Failed to retrieve tickets',
                'error'   => $e->getMessage(),
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/tickets/{id}',
        summary: 'Get ticket',
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 400, description: 'Invalid ID supplied', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show($id = null): ResponseInterface
    {
        $validation = $this->validateId($id);
        if ($validation) return $validation;

        try {
            $record = $this->service->findOrFail($id);
            $singleRecord = [$record];
            $this->loadIncludes($singleRecord, $this->relations, true);
            return $this->respond([
                'success' => true,
                'data' => $singleRecord[0],
                'message' => 'Ticket retrieved successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            log_message('error', 'TicketsController::show - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/tickets',
        summary: 'Create ticket',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['subject', 'status_id', 'priority_id', 'category_id'],
                properties: [
                    new OA\Property(property: 'subject', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'assigned_to_id', type: 'string'),
                    new OA\Property(property: 'status_id', type: 'string'),
                    new OA\Property(property: 'priority_id', type: 'string'),
                    new OA\Property(property: 'category_id', type: 'string'),
                    new OA\Property(property: 'department_id', type: 'string'),
                    new OA\Property(property: 'location_id', type: 'string'),
                ]
            )
        ),
        tags: ['Tickets'],
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 400, description: 'Validation failed'),
        ]
    )]
    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];
        try {
            $record = $this->service->create($data, $this->getUserId());
            $singleRecord = [$record];
            $this->loadIncludes($singleRecord, $this->relations, true);
            return $this->respond([
                'success' => true,
                'data' => $singleRecord[0],
                'message' => 'Ticket created successfully',
            ], ResponseInterface::HTTP_CREATED);
        } catch (ValidationException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'errors' => $e->getErrors(),
                'message' => 'Validation failed',
            ], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            log_message('error', 'TicketsController::create - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Put(
        path: '/tickets/{id}',
        summary: 'Update ticket',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'subject', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'status_id', type: 'string'),
                    new OA\Property(property: 'priority_id', type: 'string'),
                    new OA\Property(property: 'category_id', type: 'string'),
                    new OA\Property(property: 'assigned_to_id', type: 'string'),
                    new OA\Property(property: 'department_id', type: 'string'),
                    new OA\Property(property: 'location_id', type: 'string'),
                ]
            )
        ),
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated'),
            new OA\Response(response: 400, description: 'Invalid ID supplied', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function update($id = null): ResponseInterface
    {
        $validation = $this->validateId($id);
        if ($validation) return $validation;

        $data = $this->request->getJSON(true) ?? [];
        try {
            $record = $this->service->update($id, $data, $this->getUserId());
            $singleRecord = [$record];
            $this->loadIncludes($singleRecord, $this->relations, true);
            return $this->respond([
                'success' => true,
                'data' => $singleRecord[0],
                'message' => 'Ticket updated successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (ValidationException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'errors' => $e->getErrors(),
                'message' => 'Validation failed',
            ], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            log_message('error', 'TicketsController::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        path: '/tickets/{id}',
        summary: 'Delete ticket',
        tags: ['Tickets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 400, description: 'Invalid ID supplied', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function delete($id = null): ResponseInterface
    {
        $validation = $this->validateId($id);
        if ($validation) return $validation;

        try {
            $record = $this->service->delete($id);
            return $this->respond([
                'success' => true,
                'data' => $record,
                'message' => 'Ticket deleted successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (ValidationException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getErrors()[0] ?? 'Delete failed',
            ], ResponseInterface::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            log_message('error', 'TicketsController::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
