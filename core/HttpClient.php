<?php
declare(strict_types=1);

namespace App\Core;

/**
 * curl-based HTTP client (the only allowed outbound mechanism on the server —
 * curl extension is available; exec/shell are disabled). Used for scraper
 * fetches and Anthropic API calls.
 */
class HttpClient
{
    /** @return array{status:int,body:string,headers:array<string,string>} */
    public static function get(string $url, array $headers = [], int $timeout = 15): array
    {
        return self::request('GET', $url, null, $headers, $timeout);
    }

    public static function post(string $url, string $body, array $headers = [], int $timeout = 30): array
    {
        return self::request('POST', $url, $body, $headers, $timeout);
    }

    public static function postJson(string $url, array $data, array $headers = [], int $timeout = 30): array
    {
        $headers[] = 'Content-Type: application/json';
        return self::request('POST', $url, json_encode($data), $headers, $timeout);
    }

    private static function request(string $method, string $url, ?string $body, array $headers, int $timeout): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING       => '',
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $respHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$respHeaders) {
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return strlen($header);
        });

        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            logger("HttpClient error: $method $url -> $error", 'ERROR');
            return ['status' => 0, 'body' => $error, 'headers' => []];
        }

        return ['status' => $status, 'body' => (string) $result, 'headers' => $respHeaders];
    }

    public static function jsonBody(array $response): ?array
    {
        $decoded = json_decode($response['body'] ?? '', true);
        return is_array($decoded) ? $decoded : null;
    }
}
