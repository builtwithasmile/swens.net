<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Application bootstrap: PSR-style autoloader, request/response wiring, DB
 * connection, boot-time idempotent migrations, and the top-level error handler.
 *
 * Mirrors the Selvatec house framework idiom (single front controller, static
 * Database, Migrator on boot, Template partials) but is standalone — no shared
 * dependency on any other repo.
 */
class App
{
    public Router $router;
    public Request $request;
    public Response $response;

    public function __construct(string $root)
    {
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', $root);
        }
        date_default_timezone_set(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'UTC');

        // Autoloader: App\Core\Router -> core/Router.php,
        // App\Controllers\Web\DashboardController -> controllers/web/DashboardController.php.
        // Namespace segments lowercase to dir names; the final class keeps its case.
        spl_autoload_register(function (string $class) use ($root) {
            $prefix = 'App\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $segments = explode('\\', $relative);
            $file = $root . '/';
            for ($i = 0; $i < count($segments) - 1; $i++) {
                $file .= lcfirst($segments[$i]) . '/';
            }
            $file .= end($segments) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        });

        $this->router = new Router();
        $this->request = new Request();
        $this->response = new Response();

        if (defined('DB_HOST')) {
            try {
                Database::connect(DB_HOST, (int) DB_PORT, DB_NAME, DB_USER, DB_PASS);
                // Apply any pending migrations on every boot. Already-applied files are
                // recorded in `_schema_migrations` and skipped; steady-state cost is one SELECT.
                Migrator::run($root . '/migrations');
            } catch (\Throwable $e) {
                logger('DB connect failed: ' . $e->getMessage(), 'ERROR');
                // In production, a DB failure on a public page should not crash the request.
                // admin routes that require DB will fail naturally; public cache will still serve.
            }
        }

        Template::setRoot($root);
    }

    public function run(): void
    {
        try {
            $this->router->dispatch($this->request, $this->response);
        } catch (HttpException $e) {
            $this->fail($e->statusCode, $e->getMessage());
        } catch (\Throwable $e) {
            logger($e->getMessage() . "\n" . $e->getTraceAsString(), 'ERROR');
            $this->fail(500, 'Internal server error');
        }
    }

    private function fail(int $code, string $message): void
    {
        http_response_code($code);
        if ($this->request->wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message !== '' ? $message : 'Error']);
            return;
        }
        if (Template::resolve('pages/error') !== null) {
            echo Template::render('pages/error', ['code' => $code, 'message' => $message], 'bare');
            return;
        }
        echo '<!doctype html><meta charset="utf-8"><title>' . $code . '</title>'
            . '<body style="font-family:system-ui;background:#0a0e14;color:#e8edf4;display:flex;'
            . 'min-height:100vh;align-items:center;justify-content:center;margin:0">'
            . '<div style="text-align:center"><h1 style="font-size:2.5rem;margin:.2em">' . $code . '</h1>'
            . '<p style="color:#94a3b8">' . e($message !== '' ? $message : 'Error') . '</p></div>';
    }
}
