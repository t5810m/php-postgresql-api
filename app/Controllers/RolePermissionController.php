<?php

namespace App\Controllers;

use App\Models\RolePermissionModel;
use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Services\RolePermissionService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Traits\PaginationTrait;
use App\Traits\SortingTrait;
use App\Traits\FilterTrait;
use App\Traits\RelationTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RolePermission',
    required: ['id', 'role_id', 'permission_id'],
    properties: [
        new OA\Property(property: 'id', type: 'string'),
        new OA\Property(property: 'role_id', type: 'string'),
        new OA\Property(property: 'permission_id', type: 'string'),
    ]
)]
class RolePermissionController extends BaseController
{
    use PaginationTrait;
    use SortingTrait;
    use FilterTrait;
    use RelationTrait;

    protected string $format = 'json';
    protected RolePermissionModel $model;
    protected RolePermissionService $service;

    protected array $relations = [
        'role'       => ['type' => 'belongs_to', 'model' => RoleModel::class,       'fk' => 'role_id',       'key' => 'id'],
        'permission' => ['type' => 'belongs_to', 'model' => PermissionModel::class, 'fk' => 'permission_id', 'key' => 'id'],
    ];

    public function __construct()
    {
        $this->model   = new RolePermissionModel();
        $this->service = new RolePermissionService();
    }

    #[OA\Get(
        path: '/role-permissions',
        summary: 'List role permissions',
        tags: ['RolePermissions'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['id', 'role_id', 'permission_id', 'created_at', 'updated_at'])),
            new OA\Parameter(name: 'order', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
        ]
    )]
    public function index(): ResponseInterface
    {
        try {
            $this->applySort(['id', 'role_id', 'permission_id', 'created_at', 'updated_at']);
            $records = $this->model->paginate($this->getPerPage(), 'default', $this->getPage());

            $records = $this->service->enrichList($records);
            $this->loadIncludes($records, $this->relations);

            return $this->respond([
                'success' => true,
                'data' => $records,
                'message' => 'Role permissions retrieved successfully',
                'meta' => $this->buildMeta(),
            ], ResponseInterface::HTTP_OK);
        } catch (Exception $e) {
            log_message('error', 'RolePermissionController::index - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Failed to retrieve role permissions',
                'error'   => $e->getMessage(),
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/role-permissions/{id}',
        summary: 'Get role permission',
        tags: ['RolePermissions'],
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
                'message' => 'Role permission retrieved successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            log_message('error', 'RolePermissionController::show - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/role-permissions',
        summary: 'Create role permission',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role_id', 'permission_id'],
                properties: [
                    new OA\Property(property: 'role_id', type: 'string'),
                    new OA\Property(property: 'permission_id', type: 'string'),
                ]
            )
        ),
        tags: ['RolePermissions'],
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
                'message' => 'Role permission created successfully',
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
            log_message('error', 'RolePermissionController::create - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Put(
        path: '/role-permissions/{id}',
        summary: 'Update role permission',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'role_id', type: 'string'),
                    new OA\Property(property: 'permission_id', type: 'string'),
                ]
            )
        ),
        tags: ['RolePermissions'],
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
                'message' => 'Role permission updated successfully',
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
            log_message('error', 'RolePermissionController::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        path: '/role-permissions/{id}',
        summary: 'Delete role permission',
        tags: ['RolePermissions'],
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
                'message' => 'Role permission deleted successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            log_message('error', 'RolePermissionController::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
