<?php

namespace App\Controllers;

use App\Models\DepartmentModel;
use App\Services\DepartmentService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Traits\PaginationTrait;
use App\Traits\SortingTrait;
use App\Traits\FilterTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: '1.0.0',
        description: 'Enterprise HelpDesk API built with PHP 8.5.1 and CodeIgniter 4.7.0',
        title: 'HelpDesk API'
    ),
    servers: [
        new OA\Server(
            url: 'http://localhost:8080/api/v1',
            description: 'Development server'
        ),
    ],
    security: [['bearerAuth' => []]]
)]
#[OA\Schema(
    schema: 'Department',
    required: ['id', 'name'],
    properties: [
        new OA\Property(property: 'id', description: 'Department ID', type: 'string'),
        new OA\Property(property: 'name', description: 'Department name', type: 'string'),
        new OA\Property(property: 'description', description: 'Department description', type: 'string'),
        new OA\Property(property: 'created_by', description: 'Created by user', type: 'string'),
        new OA\Property(property: 'created_at', description: 'Creation timestamp', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_by', description: 'Updated by user', type: 'string'),
        new OA\Property(property: 'updated_at', description: 'Last update timestamp', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', description: 'Success flag', type: 'boolean'),
        new OA\Property(property: 'message', description: 'Error message', type: 'string'),
        new OA\Property(property: 'error', description: 'Detailed error', type: 'string'),
    ]
)]
class DepartmentController extends BaseController
{
    use PaginationTrait;
    use SortingTrait;
    use FilterTrait;

    protected string $format = 'json';
    protected DepartmentModel $model;
    protected DepartmentService $service;

    public function __construct()
    {
        $this->model   = new DepartmentModel();
        $this->service = new DepartmentService();
    }

    /**
     * List all departments with pagination, filtering, and sorting.
     * GET /api/v1/departments
     */
    #[OA\Get(
        path: '/departments',
        description: 'Retrieve a paginated list of departments with filtering and sorting options',
        summary: 'List all departments',
        tags: ['Departments'],
        parameters: [
            new OA\Parameter(name: 'page', description: 'Page number', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', description: 'Records per page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'search', description: 'Search by name or description', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort_by', description: 'Sort field', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'created_at', 'updated_at'])),
            new OA\Parameter(name: 'order', description: 'Sort order', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Departments retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Department')),
                        new OA\Property(property: 'message', type: 'string', example: 'Departments retrieved successfully'),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(): ResponseInterface
    {
        try {
            $this->applySearch(['name', 'description']);
            // Query params: sort_by (id, name, created_at), order (asc, desc) - default: created_at, desc
            $this->applySort(['id', 'name', 'created_at', 'updated_at']);
            $departments = $this->model->paginate($this->getPerPage(), 'default', $this->getPage());

            return $this->respond([
                'success' => true,
                'data' => $departments,
                'message' => 'Departments retrieved successfully',
                'meta' => $this->buildMeta(),
            ], ResponseInterface::HTTP_OK);
        } catch (Exception $e) {
            log_message('error', 'DepartmentController::index - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data' => null,
                'message' => 'Failed to retrieve departments',
                'error' => $e->getMessage(),
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Return the properties of a resource object.
     * GET /departments/{id}
     */
    #[OA\Get(
        path: '/departments/{id}',
        description: 'Retrieve a single department by its ID',
        summary: 'Get a department by ID',
        tags: ['Departments'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'Department ID', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Department retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Department'),
                        new OA\Property(property: 'message', type: 'string', example: 'Department retrieved successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid ID supplied',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Department not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
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
                'message' => 'Department retrieved successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            log_message('error', 'DepartmentController::show - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new department.
     * POST /api/v1/departments
     */
    #[OA\Post(
        path: '/departments',
        description: 'Create a new department with the provided information',
        summary: 'Create a new department',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', description: 'Department name', type: 'string'),
                    new OA\Property(property: 'description', description: 'Department description', type: 'string'),
                ]
            )
        ),
        tags: ['Departments'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Department created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Department'),
                        new OA\Property(property: 'message', type: 'string', example: 'Department created successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
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
                'message' => 'Department created successfully',
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
            log_message('error', 'DepartmentController::create - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the properties of a resource object.
     * PUT/PATCH /departments/{id}
     */
    #[OA\Put(
        path: '/departments/{id}',
        description: 'Update an existing department with new information',
        summary: 'Update a department',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', description: 'Department name', type: 'string'),
                    new OA\Property(property: 'description', description: 'Department description', type: 'string'),
                ]
            )
        ),
        tags: ['Departments'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'Department ID', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Department updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Department'),
                        new OA\Property(property: 'message', type: 'string', example: 'Department updated successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid ID supplied',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Department not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function update($id = null): ResponseInterface
    {
        $validation = $this->validateId($id);
        if ($validation) return $validation;

        $data   = $this->request->getJSON(true) ?? [];
        $userId = $this->getUserId();

        try {
            $record = $this->service->update($id, $data, $userId);
            return $this->respond([
                'success' => true,
                'data' => $record,
                'message' => 'Department updated successfully',
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
            log_message('error', 'DepartmentController::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete the designated resource object.
     * DELETE /departments/{id}
     */
    #[OA\Delete(
        path: '/departments/{id}',
        description: 'Delete an existing department by its ID',
        summary: 'Delete a department',
        tags: ['Departments'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'Department ID', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Department deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Department deleted successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid ID supplied',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Department not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
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
                'message' => 'Department deleted successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            log_message('error', 'DepartmentController::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
