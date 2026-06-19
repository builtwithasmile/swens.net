<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\Template;
use App\Core\Database;

class SiteController
{
    public function home(Request $request, Response $response): void
    {
        $html = Template::render('pages/home', [
            'title'     => 'Swens · Josh Swenson',
            'meta_desc' => 'I\'m Josh. Online I go by Swens. I\'ve had a few lives, and this is where they live.',
            'active'    => 'map',
        ], 'site');

        $response->html($html);
    }

    public function office(Request $request, Response $response): void
    {
        $officePosts = defined('DB_HOST')
            ? Database::fetchAll(
                "SELECT id, title, slug, created_at FROM posts
                 WHERE building = 'office' AND tier = 'public'
                 ORDER BY created_at DESC LIMIT 5",
                []
              )
            : [];

        $html = Template::render('pages/office', [
            'title'       => 'The Office — swens.net',
            'meta_desc'   => 'The companies that fund the place, what has shipped, and where things stand.', // TODO-lyra
            'active'      => 'office',
            'officePosts' => $officePosts,
        ], 'site');

        $response->html($html);
    }
}
