<?php

namespace App\Controllers;

use App\Models\TicketPriorityModel;
use App\Services\TicketPriorityService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Traits\PaginationTrait;
use App\Traits\SortingTrait;
use App\Traits\FilterTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TicketPriority',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id', type: 'string'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'created_by', type: 'string'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_by', type: 'string'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class TicketPriorityController extends BaseController
{
    use PaginationTrait;
    use SortingTrait;
    use FilterTrait;

    protected string $format = 'json';
    protected TicketPriorityModel $model;
    protected TicketPriorityService $service;

    public function __construct()
    {
        $this->model   = new TicketPriorityModel();
        $this->service = new TicketPriorityService();
    }

    #[OA\Get(
        path: '/ticket-priorities',
        summary: 'List ticket priorities',
        tags: ['TicketPriorities'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'search', description: 'Search in name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'created_at', 'updated_at'])),
            new OA\Parameter(name: 'order', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
        ]
    )]
    public function index(): ResponseInterface
    {
        try {
            $this->applySearch(['name']);
            $this->applySort(['id', 'name', 'created_at', 'updated_at']);
            $priorities = $this->model->paginate($this->getPerPage(), 'default', $this->getPage());

            return $this->respond([
                'success' => true,
                'data' => $priorities,
                'message' => 'Ticket priorities retrieved successfully',
                'meta' => $this->buildMeta(),
            ], ResponseInterface::HTTP_OK);
        } catch (Exception $e) {
            log_message('error', 'TicketPriorityController::index - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Failed to retrieve ticket priorities',
                'error'   => $e->getMessage(),
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/ticket-priorities/{id}',
        summary: 'Get ticket priority',
        tags: ['TicketPriorities'],
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
            return $this->respond([
                'success' => true,
                'data' => $record,
                'message' => 'Ticket priority retrieved successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            log_message('error', 'TicketPriorityController::show - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/ticket-priorities',
        summary: 'Create ticket priority',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                ]
            )
        ),
        tags: ['TicketPriorities'],
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
            return $this->respond([
                'success' => true,
                'data' => $record,
                'message' => 'Ticket priority created successfully',
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
            log_message('error', 'TicketPriorityController::create - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Put(
        path: '/ticket-priorities/{id}',
        summary: 'Update ticket priority',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                ]
            )
        ),
        tags: ['TicketPriorities'],
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
            return $this->respond([
                'success' => true,
                'data' => $record,
                'message' => 'Ticket priority updated successfully',
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
            log_message('error', 'TicketPriorityController::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        path: '/ticket-priorities/{id}',
        summary: 'Delete ticket priority',
        tags: ['TicketPriorities'],
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
                'message' => 'Ticket priority deleted successfully',
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
            log_message('error', 'TicketPriorityController::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
