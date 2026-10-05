<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Response helpers. Each method is terminal (echo + exit) so that a middleware
 * can short-circuit a request simply by calling $response->json(..., 401).
 */
class Response
{
    public function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function html(string $html, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    public function text(string $body, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=utf-8');
        echo $body;
        exit;
    }

    public function xml(string $body, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/xml; charset=utf-8');
        echo $body;
        exit;
    }

    public function redirect(string $url, int $status = 302): never
    {
        header('Location: ' . $url, true, $status);
        exit;
    }

    public function noContent(int $status = 204): never
    {
        http_response_code($status);
        exit;
    }
}
