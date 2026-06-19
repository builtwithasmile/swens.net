<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    /** @var array<string,string> */
    private array $params = [];
    private ?array $jsonBody = null;

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : '/';
    }

    public function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return (string) ($_SERVER[$key] ?? '');
    }

    public function wantsJson(): bool
    {
        return str_starts_with($this->path(), '/api/')
            || str_contains($this->header('Accept'), 'application/json');
    }

    /** @param array<string,string> $params */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    private function jsonBody(): array
    {
        if ($this->jsonBody === null) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            $this->jsonBody = is_array($decoded) ? $decoded : [];
        }
        return $this->jsonBody;
    }

    /** Read a value from POST first, then a JSON body. */
    public function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }
        return $this->jsonBody()[$key] ?? $default;
    }

    /** All input (JSON body merged under POST). */
    public function all(): array
    {
        return array_merge($this->jsonBody(), $_POST);
    }

    public function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }
}
