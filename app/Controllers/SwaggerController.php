<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class SwaggerController extends BaseController
{
    public function docs(): ResponseInterface
    {
        try {
            $specFile = FCPATH . 'openapi.json';

            if (!file_exists($specFile)) {
                return $this->response
                    ->setHeader('Content-Type', 'application/json')
                    ->setStatusCode(404)
                    ->setBody(json_encode([
                        'error' => 'OpenAPI spec not found',
                        'message' => 'openapi.json file does not exist',
                    ]));
            }

            $spec = json_decode(file_get_contents($specFile), true);

            return $this->response
                ->setHeader('Content-Type', 'application/json')
                ->setBody(json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            log_message('error', "OpenAPI Error: {$e->getMessage()}");
            return $this->response
                ->setHeader('Content-Type', 'application/json')
                ->setStatusCode(500)
                ->setBody(json_encode([
                    'error' => 'Failed to load OpenAPI spec',
                    'message' => $e->getMessage(),
                ]));
        }
    }

    public function ui(): string
    {
        return view('swagger_ui');
    }
}
