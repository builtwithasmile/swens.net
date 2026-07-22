<?php
declare(strict_types=1);

use App\Controllers\Web\SiteController;
use App\Controllers\Web\GateController;
use App\Controllers\Web\KeyController;
use App\Controllers\Web\InsideController;
use App\Controllers\Web\CryptoWatchController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\PostsController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\MembersController;
use App\Controllers\Admin\AuditController;
use App\Middleware\OwnerOnly;
use App\Middleware\KeyedOnly;

// --- Public ---
$app->router->get('/',       [SiteController::class, 'home']);
$app->router->get('/office', [SiteController::class, 'office']);
$app->router->get('/gate',   [GateController::class, 'show']);
$app->router->post('/gate',  [GateController::class, 'submit']);
$app->router->get('/mycryptowatch', [CryptoWatchController::class, 'index']);

// --- Keyed visitor key (no middleware: this IS the door) ---
// One segment, registered before the two-segment permalink catch-all below.
$app->router->get('/key/{token}', [KeyController::class, 'consume']);

// --- Keyed side (KeyedOnly: keyed visitor or owner) ---
$app->router->group(['middleware' => [KeyedOnly::class]], function ($r) {
    $r->get('/inside',          [InsideController::class, 'index']);
    $r->post('/inside/checkin', [InsideController::class, 'checkin']);
});

// --- Admin: login/auth (no OwnerOnly) ---
$app->router->get('/admin/login',        [AuthController::class, 'show']);
$app->router->post('/admin/login',       [AuthController::class, 'send']);
$app->router->get('/admin/auth/{token}', [AuthController::class, 'consume']);

// --- Admin: owner-only (OwnerOnly middleware) ---
$app->router->group(['middleware' => [OwnerOnly::class]], function ($r) {
    $r->get('/admin',                    [PostsController::class, 'index']);
    $r->get('/admin/posts/new',          [PostsController::class, 'create']);
    $r->post('/admin/posts',             [PostsController::class, 'store']);
    $r->get('/admin/posts/{id}/edit',    [PostsController::class, 'edit']);
    $r->post('/admin/posts/{id}',        [PostsController::class, 'update']);
    $r->post('/admin/posts/{id}/delete', [PostsController::class, 'delete']);
    $r->post('/admin/media',             [MediaController::class, 'store']);

    // Members — issue / revoke / approve / rotate keys, read the board
    $r->get('/admin/members',            [MembersController::class, 'index']);
    $r->post('/admin/members',           [MembersController::class, 'store']);
    $r->post('/admin/members/{id}/revoke',  [MembersController::class, 'revoke']);
    $r->post('/admin/members/{id}/approve', [MembersController::class, 'approve']);
    $r->post('/admin/members/{id}/rotate',  [MembersController::class, 'rotate']);

    // Audit trail — read-only
    $r->get('/admin/audit',              [AuditController::class, 'index']);

    $r->post('/admin/logout',            [AuthController::class, 'logout']);
});
