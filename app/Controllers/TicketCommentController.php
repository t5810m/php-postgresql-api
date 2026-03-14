<?php

namespace App\Controllers;

use App\Models\TicketCommentModel;
use App\Models\TicketModel;
use App\Models\UserModel;
use App\Services\TicketCommentService;
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
    schema: 'TicketComment',
    required: ['id', 'ticket_id', 'user_id', 'comment'],
    properties: [
        new OA\Property(property: 'id', type: 'string'),
        new OA\Property(property: 'ticket_id', type: 'string'),
        new OA\Property(property: 'user_id', type: 'string'),
        new OA\Property(property: 'comment', type: 'string'),
        new OA\Property(property: 'ticket_subject', type: 'string'),
        new OA\Property(property: 'user_name', type: 'string'),
        new OA\Property(property: 'created_by', type: 'string'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_by', type: 'string'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class TicketCommentController extends BaseController
{
    use PaginationTrait;
    use SortingTrait;
    use FilterTrait;
    use RelationTrait;

    protected string $format = 'json';
    protected TicketCommentModel $model;
    protected TicketCommentService $service;

    protected array $relations = [
        'ticket' => ['type' => 'belongs_to', 'model' => TicketModel::class, 'fk' => 'ticket_id', 'key' => 'id'],
        'user'   => ['type' => 'belongs_to', 'model' => UserModel::class,   'fk' => 'user_id',   'key' => 'id'],
    ];

    public function __construct()
    {
        $this->model   = new TicketCommentModel();
        $this->service = new TicketCommentService();
    }

    #[OA\Get(
        path: '/ticket-comments',
        summary: 'List ticket comments',
        tags: ['TicketComments'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10)),
            new OA\Parameter(name: 'search', description: 'Search in comment', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort_by', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['id', 'ticket_id', 'user_id', 'created_at', 'updated_at'])),
            new OA\Parameter(name: 'order', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
        ]
    )]
    public function index(): ResponseInterface
    {
        try {
            $this->applySearch(['comment']);
            $this->applySort(['id', 'ticket_id', 'user_id', 'created_at', 'updated_at']);
            $comments = $this->model->paginate($this->getPerPage(), 'default', $this->getPage());

            $comments = $this->service->enrichList($comments);
            $this->loadIncludes($comments, $this->relations);

            return $this->respond([
                'success' => true,
                'data'    => $comments,
                'message' => 'Ticket comments retrieved successfully',
                'meta'    => $this->buildMeta(),
            ], ResponseInterface::HTTP_OK);
        } catch (Exception $e) {
            log_message('error', 'TicketCommentController::index - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Failed to retrieve ticket comments',
                'error'   => $e->getMessage(),
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Get(
        path: '/ticket-comments/{id}',
        summary: 'Get ticket comment',
        tags: ['TicketComments'],
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
                'message' => 'Ticket comment retrieved successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            log_message('error', 'TicketCommentController::show - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Post(
        path: '/ticket-comments',
        summary: 'Create ticket comment',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ticket_id', 'comment'],
                properties: [
                    new OA\Property(property: 'ticket_id', type: 'string'),
                    new OA\Property(property: 'comment', type: 'string'),
                ]
            )
        ),
        tags: ['TicketComments'],
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
                'message' => 'Ticket comment created successfully',
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
            log_message('error', 'TicketCommentController::create - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Put(
        path: '/ticket-comments/{id}',
        summary: 'Update ticket comment',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'comment', type: 'string'),
                ]
            )
        ),
        tags: ['TicketComments'],
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
                'message' => 'Ticket comment updated successfully',
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
            log_message('error', 'TicketCommentController::update - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Delete(
        path: '/ticket-comments/{id}',
        summary: 'Delete ticket comment',
        tags: ['TicketComments'],
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
                'message' => 'Ticket comment deleted successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (NotFoundException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            log_message('error', 'TicketCommentController::delete - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
