<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Plain-PHP template renderer with a layout wrapper. Templates live under
 * <root>/templates/. render() wraps a page in layouts/<layout>.php; partial()
 * renders a fragment with no wrapping.
 */
class Template
{
    private static string $root = '';

    public static function setRoot(string $root): void
    {
        self::$root = rtrim($root, '/\\');
    }

    /**
     * Render a page inside a layout.
     * @param string $page   e.g. 'pages/dashboard'
     * @param string $layout e.g. 'app' loads 'layouts/app.php'
     */
    public static function render(string $page, array $data = [], string $layout = 'app'): string
    {
        $data['content'] = self::partial($page, $data);
        return self::partial("layouts/$layout", $data);
    }

    public static function partial(string $name, array $data = []): string
    {
        $file = self::resolve($name);
        if ($file === null) {
            throw new \RuntimeException("Template not found: $name (looked in " . self::$root . "/templates/$name.php)");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    public static function resolve(string $name): ?string
    {
        $candidate = self::$root . "/templates/$name.php";
        return is_file($candidate) ? $candidate : null;
    }
}
