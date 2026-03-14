<?php

namespace App\Filters;

use App\Helpers\JwtPayload;
use Config\Jwt as JwtConfig;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use UnexpectedValueException;

class JwtFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (ENVIRONMENT === 'testing') {
            return;
        }

        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Missing or malformed Authorization header');
        }

        $token  = substr($authHeader, 7);
        $config = new JwtConfig();

        try {
            $payload = JWT::decode($token, new Key($config->secret, $config->algorithm));
        } catch (ExpiredException $e) {
            return $this->unauthorized('Token has expired');
        } catch (SignatureInvalidException $e) {
            return $this->unauthorized('Invalid token signature');
        } catch (UnexpectedValueException $e) {
            return $this->unauthorized('Invalid token');
        }

        if (($payload->type ?? '') !== 'access') {
            return $this->unauthorized('Invalid token type');
        }

        JwtPayload::set($payload);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nothing
    }

    private function unauthorized(string $message): ResponseInterface
    {
        $response = service('response');
        $response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED);
        $response->setContentType('application/json');
        $response->setBody(json_encode([
            'success' => false,
            'message' => $message,
        ]));

        return $response;
    }
}
