<?php
declare(strict_types=1);

namespace App\Core;

/** Base class for route middleware. Instantiated with no args by the Router. */
abstract class Middleware
{
    abstract public function handle(Request $request, Response $response): void;
}
