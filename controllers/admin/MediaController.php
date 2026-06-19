<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Services\MediaService;
use App\Services\PublicCache;

class MediaController
{
    public function store(Request $request, Response $response): void
    {
        boot_session();
        if (!\App\Core\Csrf::check((string) $request->input('_csrf', ''))) {
            abort(419, 'Invalid CSRF token.');
        }

        $postId = (int) $request->input('post_id', 0);
        if ($postId < 1) {
            abort(400, 'post_id required.');
        }

        $upload = $_FILES['file'] ?? null;
        if (!$upload) {
            abort(400, 'No file uploaded.');
        }

        try {
            $id = MediaService::store($upload, $postId);
        } catch (\RuntimeException $e) {
            abort(422, $e->getMessage());
        }

        PublicCache::purgeAll();

        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'media_id' => $id]);
        exit;
    }
}
