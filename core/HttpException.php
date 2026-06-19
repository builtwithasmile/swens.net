<?php
declare(strict_types=1);

namespace App\Core;

/** Thrown via abort($code) and turned into an HTTP response by App::run(). */
class HttpException extends \RuntimeException
{
    public function __construct(public int $statusCode, string $message = '')
    {
        parent::__construct($message !== '' ? $message : ('HTTP ' . $statusCode));
    }
}
