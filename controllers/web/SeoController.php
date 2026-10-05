<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;

/**
 * robots.txt and sitemap.xml, both derived from the route table (Router::publicPaths)
 * so they cannot drift from routes.php.
 */
class SeoController
{
    /** Path prefixes crawlers are kept out of; also never listed in the sitemap. */
    public const DISALLOW = ['/admin', '/gate', '/inside', '/office'];

    public static function robotsBody(): string
    {
        $out = "User-agent: *\nAllow: /\n";
        foreach (self::DISALLOW as $p) {
            $out .= "Disallow: $p\n";
        }
        return $out . "\nSitemap: " . site_url() . "/sitemap.xml\n";
    }

    public static function sitemapBody(array $paths): string
    {
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($paths as $p) {
            $out .= '  <url><loc>' . htmlspecialchars(site_url() . $p, ENT_XML1) . "</loc></url>\n";
        }
        return $out . "</urlset>\n";
    }

    public function robots(Request $request, Response $response): void
    {
        $response->text(self::robotsBody());
    }

    public function sitemap(Request $request, Response $response): void
    {
        global $app;
        $response->xml(self::sitemapBody(self::sitemapPaths($app->router)));
    }

    /** Public pages for the sitemap: the route table's public paths minus these two files. */
    public static function sitemapPaths(\App\Core\Router $router): array
    {
        return array_values(array_filter(
            $router->publicPaths(self::DISALLOW),
            fn(string $p) => !in_array($p, ['/robots.txt', '/sitemap.xml'], true)
        ));
    }
}
