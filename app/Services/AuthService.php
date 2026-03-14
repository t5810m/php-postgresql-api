<?php

namespace App\Services;

use App\Models\UserModel;
use Config\Jwt as JwtConfig;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use UnexpectedValueException;

class AuthService
{
    private JwtConfig $config;
    private UserModel $userModel;

    public function __construct()
    {
        $this->config    = new JwtConfig();
        $this->userModel = new UserModel();
    }

    /**
     * Authenticate user and return tokens + user info.
     *
     * @throws RuntimeException on invalid credentials or inactive account
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userModel
            ->where('email', $email)
            ->first();

        if (!$user) {
            throw new RuntimeException('Invalid credentials');
        }

        if (!(bool) $user['is_active']) {
            throw new RuntimeException('Account is inactive');
        }

        if (!password_verify($password, $user['password'])) {
            throw new RuntimeException('Invalid credentials');
        }

        $roles       = $this->fetchUserRoles((int) $user['id']);
        $permissions = $this->fetchUserPermissions(array_column($roles, 'id'));

        $token        = $this->generateAccessToken((int) $user['id']);
        $refreshToken = $this->generateRefreshToken((int) $user['id']);

        $departmentName = $this->fetchName('departments', (int) ($user['department_id'] ?? 0));
        $locationName   = $this->fetchName('locations', (int) ($user['location_id'] ?? 0));

        return [
            'token'         => $token,
            'refresh_token' => $refreshToken,
            'expires_in'    => $this->config->expiration,
            'user'          => [
                'id'              => (int) $user['id'],
                'name'            => $user['name'],
                'email'           => $user['email'],
                'department_name' => $departmentName,
                'location_name'   => $locationName,
            ],
            'roles'       => array_column($roles, 'name'),
            'permissions' => array_column($permissions, 'name'),
        ];
    }

    /**
     * Issue a new access token from a valid refresh token.
     *
     * @throws RuntimeException on invalid or expired refresh token
     */
    public function refresh(string $refreshToken): array
    {
        try {
            $payload = JWT::decode($refreshToken, new Key($this->config->secret, $this->config->algorithm));
        } catch (UnexpectedValueException $e) {
            throw new RuntimeException('Invalid or expired refresh token');
        }

        if (($payload->type ?? '') !== 'refresh') {
            throw new RuntimeException('Invalid token type');
        }

        $userId = (int) ($payload->sub ?? 0);
        $user   = $this->userModel->find($userId);

        if (!$user || !(bool) $user['is_active']) {
            throw new RuntimeException('User not found or inactive');
        }

        $token = $this->generateAccessToken($userId);

        return [
            'token'      => $token,
            'expires_in' => $this->config->expiration,
        ];
    }

    private function generateAccessToken(int $userId): string
    {
        $now     = time();
        $payload = [
            'iss'  => $this->config->issuer,
            'sub'  => $userId,
            'iat'  => $now,
            'exp'  => $now + $this->config->expiration,
            'type' => 'access',
        ];

        return JWT::encode($payload, $this->config->secret, $this->config->algorithm);
    }

    private function generateRefreshToken(int $userId): string
    {
        $now     = time();
        $payload = [
            'iss'  => $this->config->issuer,
            'sub'  => $userId,
            'iat'  => $now,
            'exp'  => $now + $this->config->refreshExpiration,
            'type' => 'refresh',
        ];

        return JWT::encode($payload, $this->config->secret, $this->config->algorithm);
    }

    private function fetchUserRoles(int $userId): array
    {
        $db = \Config\Database::connect();

        return $db->query(
            'SELECT r.id, r.name FROM user_roles ur JOIN roles r ON ur.role_id = r.id WHERE ur.user_id = ?',
            [$userId]
        )->getResultArray();
    }

    private function fetchUserPermissions(array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }

        $db          = \Config\Database::connect();
        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));

        return $db->query(
            "SELECT DISTINCT p.name FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id IN ({$placeholders})",
            $roleIds
        )->getResultArray();
    }

    private function fetchName(string $table, int $id): ?string
    {
        if ($id <= 0) {
            return null;
        }

        $db     = \Config\Database::connect();
        $record = $db->query("SELECT name FROM {$table} WHERE id = ?", [$id])->getRowArray();

        return $record['name'] ?? null;
    }
}
