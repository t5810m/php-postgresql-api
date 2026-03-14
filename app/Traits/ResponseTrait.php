<?php

namespace App\Traits;

use CodeIgniter\HTTP\ResponseInterface;

trait ResponseTrait
{
    protected function getUserId(): int
    {
        if (ENVIRONMENT === 'testing') {
            return 1;
        }
        return \App\Helpers\JwtPayload::getUserId();
    }

    protected function respond(array $data, int $statusCode = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setBody(json_encode($data));
    }
}
