<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\AuditLog;
use App\Core\Request;
use App\Core\Response;
use App\Core\Template;
use App\Core\Database;
use App\Services\Buildings;
use App\Services\PublicCache;

class PostsController
{
    public function index(Request $request, Response $response): void
    {
        $posts = Database::fetchAll(
            'SELECT id, building, tier, kind, title, slug, created_at FROM posts ORDER BY created_at DESC LIMIT 200'
        );
        $html = Template::render('pages/admin/index', [
            'title' => 'Posts — Admin',
            'posts' => $posts,
            'csrf'  => csrf_field(),
        ], 'admin');
        $response->html($html);
    }

    public function create(Request $request, Response $response): void
    {
        $html = Template::render('pages/admin/post-form', [
            'title'     => 'New Post — Admin',
            'post'      => null,
            'buildings' => Buildings::postableAll(),
            'csrf'      => csrf_field(),
            'errors'    => [],
        ], 'admin');
        $response->html($html);
    }

    public function store(Request $request, Response $response): void
    {
        $this->verifyCsrf($request);
        [$data, $errors] = $this->validate($request);
        if ($errors) {
            $html = Template::render('pages/admin/post-form', [
                'title'     => 'New Post — Admin',
                'post'      => $data,
                'buildings' => Buildings::postableAll(),
                'csrf'      => csrf_field(),
                'errors'    => $errors,
            ], 'admin');
            $response->html($html);
            return;
        }
        Database::insert('posts', $data);
        AuditLog::record('post.create', $data['slug']);
        PublicCache::purgeAll();
        redirect('/admin');
    }

    public function edit(Request $request, Response $response): void
    {
        $post = $this->requirePost((int) $request->param('id', 0));
        $html = Template::render('pages/admin/post-form', [
            'title'     => 'Edit Post — Admin',
            'post'      => $post,
            'buildings' => Buildings::postableAll(),
            'csrf'      => csrf_field(),
            'errors'    => [],
        ], 'admin');
        $response->html($html);
    }

    public function update(Request $request, Response $response): void
    {
        $this->verifyCsrf($request);
        $id = (int) $request->param('id', 0);
        $this->requirePost($id);
        [$data, $errors] = $this->validate($request);
        if ($errors) {
            $html = Template::render('pages/admin/post-form', [
                'title'     => 'Edit Post — Admin',
                'post'      => array_merge($data, ['id' => $id]),
                'buildings' => Buildings::postableAll(),
                'csrf'      => csrf_field(),
                'errors'    => $errors,
            ], 'admin');
            $response->html($html);
            return;
        }
        Database::update('posts', $data, 'id = :id', ['id' => $id]);
        AuditLog::record('post.update', $data['slug']);
        PublicCache::purgeAll();
        redirect('/admin');
    }

    public function delete(Request $request, Response $response): void
    {
        $this->verifyCsrf($request);
        $id = (int) $request->param('id', 0);
        $post = $this->requirePost($id);
        Database::delete('posts', 'id = ?', [$id]);
        AuditLog::record('post.delete', $post['slug']);
        PublicCache::purgeAll();
        redirect('/admin');
    }

    // -------------------------------------------------------------------------

    private function requirePost(int $id): array
    {
        $post = Database::fetch('SELECT * FROM posts WHERE id = ?', [$id]);
        if (!$post) {
            abort(404, 'Post not found.');
        }
        return $post;
    }

    private function verifyCsrf(Request $request): void
    {
        boot_session();
        if (!\App\Core\Csrf::check((string) $request->input('_csrf', ''))) {
            abort(419, 'Invalid CSRF token.');
        }
    }

    private function validate(Request $request): array
    {
        $building = (string) $request->input('building', '');
        $tier     = (string) $request->input('tier', 'public');
        // A keyed post ALWAYS lands in the keyed bucket, whatever building was
        // picked — otherwise it shows on neither /inside nor any public page and
        // is silently lost. Force it server-side so the dropdown can't misfile.
        if ($tier === 'keyed') {
            $building = array_key_first(Buildings::KEYED_SECTIONS);
        }
        $kind     = (string) $request->input('kind', 'welcome');
        $title    = trim((string) $request->input('title', ''));
        $slug     = trim((string) $request->input('slug', ''));
        $body_md  = (string) $request->input('body_md', '');
        $tags     = trim((string) $request->input('tags', ''));

        $errors = [];
        $allowed = Buildings::postableAll();
        if (!in_array($building, $allowed, true)) {
            $errors['building'] = 'Invalid building.';
        }
        if (!in_array($tier, ['public', 'keyed'], true)) {
            $errors['tier'] = 'Tier must be public or keyed.';
        }
        if (!in_array($kind, ['welcome', 'about', 'board', 'now', 'story'], true)) {
            $errors['kind'] = 'Invalid kind.';
        }
        if ($title === '' || mb_strlen($title) > 160) {
            $errors['title'] = 'Title required (max 160).';
        }
        if ($slug === '' || mb_strlen($slug) > 160 || !preg_match('/^[a-z0-9\-]+$/', $slug)) {
            $errors['slug'] = 'Slug required, lowercase a-z 0-9 hyphens only (max 160).';
        }
        if (mb_strlen($tags) > 255) {
            $errors['tags'] = 'Tags too long (max 255).';
        }

        $data = compact('building', 'tier', 'kind', 'title', 'slug', 'body_md', 'tags');
        return [$data, $errors];
    }
}
