<?php

namespace App\Controllers;

use App\Services\AuthService;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Enter your JWT access token. Obtain it from POST /auth/login'
)]
class AuthController extends BaseController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

    /**
     * Authenticate user and return JWT tokens.
     * POST /api/v1/auth/login
     */
    #[OA\Post(
        path: '/auth/login',
        description: 'Authenticate with email and password. Returns access token, refresh token, user info, roles, and permissions.',
        summary: 'Login',
        security: [],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'token', type: 'string'),
                                new OA\Property(property: 'refresh_token', type: 'string'),
                                new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                                new OA\Property(property: 'user', type: 'object'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                                new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string')),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function login(): ResponseInterface
    {
        $data     = $this->request->getJSON(true) ?? [];
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Email and password are required',
            ], ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $this->service->login($email, $password);

            return $this->respond([
                'success' => true,
                'data'    => $result,
                'message' => 'Login successful',
            ], ResponseInterface::HTTP_OK);
        } catch (\RuntimeException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        } catch (Exception $e) {
            log_message('error', 'AuthController::login - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Issue a new access token from a valid refresh token.
     * POST /api/v1/auth/refresh
     */
    #[OA\Post(
        path: '/auth/refresh',
        description: 'Exchange a valid refresh token for a new access token.',
        summary: 'Refresh token',
        security: [],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refresh_token'],
                properties: [
                    new OA\Property(property: 'refresh_token', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token refreshed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'token', type: 'string'),
                                new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                            ]
                        ),
                        new OA\Property(property: 'message', type: 'string', example: 'Token refreshed successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid or expired refresh token',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
        ]
    )]
    public function refresh(): ResponseInterface
    {
        $data         = $this->request->getJSON(true) ?? [];
        $refreshToken = $data['refresh_token'] ?? '';

        if ($refreshToken === '') {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'refresh_token is required',
            ], ResponseInterface::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $this->service->refresh($refreshToken);

            return $this->respond([
                'success' => true,
                'data'    => $result,
                'message' => 'Token refreshed successfully',
            ], ResponseInterface::HTTP_OK);
        } catch (\RuntimeException $e) {
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => $e->getMessage(),
            ], ResponseInterface::HTTP_UNAUTHORIZED);
        } catch (Exception $e) {
            log_message('error', 'AuthController::refresh - ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'success' => false,
                'data'    => null,
                'message' => 'Server error',
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
