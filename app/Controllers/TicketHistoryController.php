<?php

namespace App\Controllers;

use App\Models\TicketHistoryModel;
use App\Services\TicketHistoryService;
use App\Exceptions\NotFoundException;
use App\Traits\PaginationTrait;
use App\Traits\SortingTrait;
use App\Traits\FilterTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TicketHistory',
    required: ['id', 'ticket_id', 'action'],
    properties: [
        new OA\Property(property: 'id', type: 'string'),
        new OA\Property(property: 'ticket_id', type: 'string'),
        new OA\Property(property: 'user_id', type: 'string', description: 'ID of the user who performed the action'),
        new OA\Property(property: 'action', type: 'string'),
        new OA\Property(property: 'details', type: 'string'),
        new OA\Property(property: 'ticket_subject', type: 'string'),
        new OA\Property(property: 'user_name', type: 'string'),
        new OA\Property(property: 'created_by', type: 'string'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_by', type: 'string'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class TicketHistoryController extends BaseController
{
    use PaginationTrait;
    use SortingTrait;
    use FilterTrait;

    protected string $format = 'json';
    protected TicketHistoryModel $model;
    protected TicketHistoryService $service;

    public function __construct()
    {
        $this->model   = new TicketHistoryModel();
        $this->service = new TicketHistoryService();
    }

    #[OA\Get(
        path: '/ticket-history',
        description: 'Ticket history is an immutable audit log written by the system. Read-only.',
        summary: 'List ticket history',
        tags: ['TicketHistory'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'search', description: 'Search in action or details', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ticket_id', description: 'Filter by ticket ID', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'user_id', description: 'Filter by user ID', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['id', 'ticket_id', 'user_id', 'created_at', 'updated_at'])),
            new OA\Parameter(name: 'order', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket history retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TicketHistory')),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(): ResponseInterface
    {
        try {
            $this->applyExactFilters([
                'ticket_id' => 'ticket_id',
                'user_id'   => 'user_id',
            ]);
            $this->applySearch(['action', 'details']);
            $this->applySort(['id', 'ticket_id', 'user_id', 'created_at', 'updated_at']);
            $history = $this->model->paginate($this->getPerPage(), 'default', $this->getPage());

            $history = $this->service->enrichList($history);

            return $this->respond([
                'success' => true,
                'data'    => $history,
                'message' => 'Ticket history retrieved successfully',
                'meta'    => $this->buildMeta(),
            ], ResponseInterface::HTTP_OK);
        } catch (Exception $e) {
            log_message('error', 'TicketHistoryController::index - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Failed to retrieve ticket history',
                'error'   => $e->getMessage(),
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/ticket-history/{id}',
        description: 'Retrieve a single ticket history entry by ID.',
        summary: 'Get ticket history entry',
        tags: ['TicketHistory'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ticket history entry retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TicketHistory'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid ID supplied', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ]
    )]
    public function show($id = null): ResponseInterface
    {
        $validation = $this->validateId($id);
        if ($validation) return $validation;

        try {
            $record = $this->service->findOrFail($id);
            return $this->respond([
                'success' => true,
                'data'    => $record,
                'message' => 'Ticket history entry retrieved successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            log_message('error', 'TicketHistoryController::show - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
