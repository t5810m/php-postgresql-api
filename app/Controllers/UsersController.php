<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\DepartmentModel;
use App\Models\LocationModel;
use App\Services\UserService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Traits\PaginationTrait;
use App\Traits\FilterTrait;
use App\Traits\SortingTrait;
use App\Traits\AggregationTrait;
use App\Traits\RelationTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    required: ['id', 'name', 'email'],
    properties: [
        new OA\Property(property: 'id', description: 'User ID', type: 'string'),
        new OA\Property(property: 'name', description: 'User name', type: 'string'),
        new OA\Property(property: 'email', description: 'User email', type: 'string', format: 'email'),
        new OA\Property(property: 'phone', description: 'User phone', type: 'string'),
        new OA\Property(property: 'department_id', description: 'Department ID', type: 'string'),
        new OA\Property(property: 'location_id', description: 'Location ID', type: 'string'),
        new OA\Property(property: 'is_active', description: 'Active status', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class UsersController extends BaseController
{
    use PaginationTrait;
    use FilterTrait;
    use SortingTrait;
    use AggregationTrait;
    use RelationTrait;

    protected string $format = 'json';
    protected UserModel $model;
    protected UserService $service;

    protected array $relations = [
        'department' => ['type' => 'belongs_to', 'model' => DepartmentModel::class, 'fk' => 'department_id', 'key' => 'id'],
        'location'   => ['type' => 'belongs_to', 'model' => LocationModel::class,   'fk' => 'location_id',   'key' => 'id'],
    ];

    public function __construct()
    {
        $this->model   = new UserModel();
        $this->service = new UserService();
    }

    #[OA\Get(
        path: '/users',
        summary: 'List all users',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'search', description: 'Search in name, email, or phone', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'department_id', description: 'Filter by department', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'is_active', description: 'Filter by active status', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'role_id', description: 'Filter by role', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date_from', description: 'Filter from created date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', description: 'Filter to created date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'email', 'department_id', 'created_at', 'updated_at'])),
            new OA\Parameter(name: 'order', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Users retrieved successfully', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/User'))])),
        ]
    )]
    public function index(): ResponseInterface
    {
        try {
            $this->applyExactFilters([
                'department_id' => 'department_id',
                'is_active'     => 'is_active',
            ]);
            $this->applyRoleFilter();
            $this->applySearch(['name', 'email', 'phone']);
            $this->applyDateRange('created_at');
            $this->applySort(['id', 'name', 'email', 'department_id', 'created_at', 'updated_at']);

            $perPage = $this->getPerPage();
            $page    = $this->getPage();

            $users = $this->model->paginate($perPage, 'default', $page);

            $users = $this->service->enrichList($users);
            $this->loadIncludes($users, $this->relations);

            return $this->respond([
                'success' => true,
                'data'    => $users,
                'message' => 'Users retrieved successfully',
                'meta'    => $this->buildMeta(),
            ], ResponseInterface::HTTP_OK);
        } catch (Exception $e) {
            log_message('error', 'UsersController::index - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Failed to retrieve users',
                'error'   => $e->getMessage(),
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/users/{id}',
        summary: 'Get a user',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User retrieved', content: new OA\JsonContent(ref: '#/components/schemas/User')),
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
                'message' => 'User retrieved successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            log_message('error', 'UsersController::show - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/users',
        summary: 'Create a user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
                    new OA\Property(property: 'phone', type: 'string'),
                    new OA\Property(property: 'department_id', type: 'string'),
                    new OA\Property(property: 'location_id', type: 'string'),
                ]
            )
        ),
        tags: ['Users'],
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
                'message' => 'User created successfully',
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
            log_message('error', 'UsersController::create - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Put(
        path: '/users/{id}',
        summary: 'Update a user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
                    new OA\Property(property: 'phone', type: 'string'),
                    new OA\Property(property: 'department_id', type: 'string'),
                    new OA\Property(property: 'location_id', type: 'string'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        tags: ['Users'],
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
                'message' => 'User updated successfully',
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
            log_message('error', 'UsersController::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        path: '/users/{id}',
        summary: 'Delete a user',
        tags: ['Users'],
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
                'message' => 'User deleted successfully',
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
            log_message('error', 'UsersController::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
