<?php
declare(strict_types=1);

/**
 * Selvatec Frozen Installer — Joomla-style wizard. FROZEN COMPONENT: do not edit
 * per project. It is data-driven — it reads config.example.php to learn this
 * project's define() names and renders fields automatically. Propagate fixes
 * with /sync-core, never by hand-editing a copy.
 *
 * Behaviour (locked):
 *   - 5 steps: Pre-flight -> Database -> Site config -> Install (SSE) -> Finalize.
 *   - NEVER runs CREATE DATABASE. Josh creates the DB + user in cPanel and gives
 *     credentials; this connects to the existing DB. If it's missing, test-db
 *     tells the user to create it first.
 *   - Idempotent migrations via a _migrations table.
 *   - Writes config.php (define() format) with a generated SESSION_SECRET.
 *   - Self-locks: writes storage/installer.locked and renames itself out of the
 *     web path on finalize.
 *
 * Project-specific escape hatch (Pip's): drop an installer.config.php next to
 * this file returning ['hidden'=>[...], 'labels'=>[...]] to tweak fields without
 * editing this frozen file.
 */

$ROOT        = __DIR__;                       // project root (config.php lives here)
$EXAMPLE     = $ROOT . '/config.example.php';
$CONFIG      = $ROOT . '/config.php';
$MIGRATIONS  = is_dir($ROOT . '/migrations') ? $ROOT . '/migrations' : null;
$STORAGE     = $ROOT . '/storage';
$LOCK        = $STORAGE . '/installer.locked';
$OVERRIDE    = is_file($ROOT . '/installer.config.php') ? (require $ROOT . '/installer.config.php') : [];

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ---- Locked? refuse everything ----
if (is_file($LOCK)) {
    if ($method === 'POST') { http_response_code(403); json(['ok' => false, 'error' => 'installer locked']); }
    renderLocked(); exit;
}
// ---- Already installed? ----
if ($action === '' && is_file($CONFIG)) {
    // config exists but not locked — offer migrate-only / lock on the page.
}

if ($method === 'POST' && $action === 'check')    { json(runChecks($EXAMPLE, $STORAGE, $MIGRATIONS)); }
if ($method === 'POST' && $action === 'test-db')  { json(testDb(body())); }
if ($method === 'POST' && $action === 'install')  { runInstall(body()); exit; }
if ($method === 'POST' && $action === 'lock')     { json(lockSelf()); }

renderWizard(discoverFields($EXAMPLE, $OVERRIDE), $MIGRATIONS !== null);
exit;

// ───────────────────────── helpers ─────────────────────────
function body(): array { return json_decode(file_get_contents('php://input'), true) ?: []; }
function json(array $d): void { header('Content-Type: application/json'); echo json_encode($d); exit; }

/** Parse config.example.php for define('NAME', default) — returns [name => default]. */
function parseDefines(string $exampleFile): array {
    $out = [];
    if (!is_file($exampleFile)) return $out;
    foreach (file($exampleFile, FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match("/define\\(\\s*'([A-Z0-9_]+)'\\s*,\\s*(.+?)\\)\\s*;/", $line, $m)) {
            $raw = trim($m[2]);
            if ((str_starts_with($raw, "'") && str_ends_with($raw, "'")) ||
                (str_starts_with($raw, '"') && str_ends_with($raw, '"'))) {
                $raw = substr($raw, 1, -1);
            }
            $out[$m[1]] = $raw;
        }
    }
    return $out;
}

/** Group defines into wizard sections. Secrets render as password + blank. */
function discoverFields(string $exampleFile, array $override): array {
    $defines = parseDefines($exampleFile);
    $hidden  = $override['hidden'] ?? ['SESSION_SECRET']; // always auto-generated
    $labels  = $override['labels'] ?? [];
    $db = $app = $keys = [];
    foreach ($defines as $name => $default) {
        if (in_array($name, $hidden, true)) continue;
        $field = [
            'name'    => $name,
            'default' => $default,
            'label'   => $labels[$name] ?? ucwords(strtolower(str_replace('_', ' ', $name))),
            'secret'  => (bool) preg_match('/KEY|SECRET|PASS|TOKEN|SID/', $name),
        ];
        if (str_starts_with($name, 'DB_'))                          $db[]  = $field;
        elseif (preg_match('/KEY|SECRET|PASS|TOKEN|SID|SMTP/', $name)) $keys[] = $field;
        else                                                        $app[] = $field;
    }
    return ['db' => $db, 'app' => $app, 'keys' => $keys, 'all' => array_values($defines)];
}

function runChecks(string $exampleFile, string $storage, ?string $migDir): array {
    $checks = [];
    $checks[] = ['label' => 'PHP >= 8.3', 'ok' => PHP_VERSION_ID >= 80300, 'detail' => PHP_VERSION];
    foreach (['pdo_mysql', 'curl', 'zip', 'mbstring'] as $ext) {
        $checks[] = ['label' => "ext: $ext", 'ok' => extension_loaded($ext), 'detail' => ''];
    }
    @mkdir($storage, 0775, true);
    $checks[] = ['label' => 'storage/ writable', 'ok' => is_dir($storage) && is_writable($storage), 'detail' => $storage];
    $checks[] = ['label' => 'config.example.php present', 'ok' => is_file($exampleFile), 'detail' => ''];
    $checks[] = ['label' => 'migrations/ present', 'ok' => $migDir !== null, 'detail' => $migDir ?? 'none — schema step will be skipped'];
    $ok = array_reduce($checks, fn($c, $x) => $c && ($x['ok'] || str_contains($x['detail'], 'skipped')), true);
    // migrations missing is a warning, not fatal:
    $fatal = array_reduce($checks, fn($c, $x) => $c && ($x['ok'] || $x['label'] === 'migrations/ present'), true);
    return ['ok' => $fatal, 'checks' => $checks];
}

/** Connect to an EXISTING database. Never CREATE DATABASE. */
function pdoConnect(array $b): PDO {
    $host = $b['DB_HOST'] ?? 'localhost';
    $port = (int) ($b['DB_PORT'] ?? 3306);
    $name = $b['DB_NAME'] ?? '';
    $dsn  = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
    return new PDO($dsn, $b['DB_USER'] ?? '', $b['DB_PASS'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
}

function testDb(array $b): array {
    try {
        $pdo = pdoConnect($b);
        $v = $pdo->query('SELECT VERSION()')->fetchColumn();
        return ['ok' => true, 'detail' => "Connected. MariaDB/MySQL $v"];
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'Unknown database') !== false) {
            return ['ok' => false, 'detail' => "Database '" . ($b['DB_NAME'] ?? '') . "' does not exist. Create it (and the DB user) in cPanel first, then re-enter the credentials. This installer never creates databases."];
        }
        return ['ok' => false, 'detail' => 'Connection failed: ' . $msg];
    }
}

/** Idempotent migration runner. Tracks applied files in _migrations.filename. */
function runMigrations(PDO $pdo, string $migDir): array {
    $pdo->exec("CREATE TABLE IF NOT EXISTS _migrations (id INT AUTO_INCREMENT PRIMARY KEY, filename VARCHAR(255) NOT NULL UNIQUE, ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $applied = []; $skipped = [];
    $check  = $pdo->prepare("SELECT 1 FROM _migrations WHERE filename = ?");
    $insert = $pdo->prepare("INSERT INTO _migrations (filename) VALUES (?)");
    $files = glob($migDir . '/*.sql') ?: [];
    sort($files);
    foreach ($files as $file) {
        $fn = basename($file);
        $check->execute([$fn]);
        if ($check->fetchColumn()) { $skipped[] = $fn; continue; }
        foreach (splitSql(file_get_contents($file)) as $stmt) {
            if (trim($stmt) !== '') $pdo->exec($stmt);
        }
        $insert->execute([$fn]);
        $applied[] = $fn;
    }
    return ['applied' => $applied, 'skipped' => $skipped];
}

/** Split SQL on semicolons, respecting quotes/comments. */
function splitSql(string $sql): array {
    $out = []; $buf = ''; $q = null; $n = strlen($sql);
    for ($i = 0; $i < $n; $i++) {
        $c = $sql[$i];
        if ($q !== null) {
            $buf .= $c;
            if ($c === $q && ($sql[$i-1] ?? '') !== '\\') $q = null;
            continue;
        }
        if ($c === "'" || $c === '"' || $c === '`') { $q = $c; $buf .= $c; continue; }
        if ($c === '-' && ($sql[$i+1] ?? '') === '-') { while ($i < $n && $sql[$i] !== "\n") $i++; continue; }
        if ($c === ';') { $out[] = $buf; $buf = ''; continue; }
        $buf .= $c;
    }
    if (trim($buf) !== '') $out[] = $buf;
    return $out;
}

function runInstall(array $cfg): void {
    sseHeaders();
    global $EXAMPLE, $CONFIG, $MIGRATIONS;
    $total = ($MIGRATIONS !== null) ? 3 : 2; $step = 0;

    // 1. Connect (existing DB only)
    try {
        $pdo = pdoConnect($cfg);
        sse(['step' => ++$step, 'total' => $total, 'msg' => 'Connect to database', 'ok' => true]);
    } catch (Throwable $e) {
        sse(['step' => ++$step, 'total' => $total, 'msg' => 'Connect to database', 'ok' => false, 'detail' => $e->getMessage()]);
        sse(['done' => true, 'ok' => false]); exit;
    }

    // 2. Migrations (if present)
    if ($MIGRATIONS !== null) {
        try {
            $r = runMigrations($pdo, $MIGRATIONS);
            sse(['step' => ++$step, 'total' => $total, 'msg' => 'Run migrations (' . count($r['applied']) . ' applied, ' . count($r['skipped']) . ' skipped)', 'ok' => true]);
        } catch (Throwable $e) {
            sse(['step' => ++$step, 'total' => $total, 'msg' => 'Run migrations', 'ok' => false, 'detail' => $e->getMessage()]);
            sse(['done' => true, 'ok' => false]); exit;
        }
    }

    // 3. Write config.php from config.example.php, substituting provided values + SESSION_SECRET
    try {
        $written = writeConfig($EXAMPLE, $CONFIG, $cfg);
        sse(['step' => ++$step, 'total' => $total, 'msg' => 'Write config.php', 'ok' => $written]);
        sse(['done' => true, 'ok' => $written]);
    } catch (Throwable $e) {
        sse(['step' => ++$step, 'total' => $total, 'msg' => 'Write config.php', 'ok' => false, 'detail' => $e->getMessage()]);
        sse(['done' => true, 'ok' => false]);
    }
    exit;
}

/** Copy config.example.php structure, replacing define defaults with submitted values. */
function writeConfig(string $exampleFile, string $configFile, array $vals): bool {
    $lines = file($exampleFile, FILE_IGNORE_NEW_LINES);
    $out = [];
    foreach ($lines as $line) {
        if (preg_match("/define\\(\\s*'([A-Z0-9_]+)'\\s*,/", $line, $m)) {
            $name = $m[1];
            if ($name === 'SESSION_SECRET') {
                $out[] = "define('SESSION_SECRET', '" . bin2hex(random_bytes(32)) . "');";
                continue;
            }
            if (array_key_exists($name, $vals) && $vals[$name] !== '') {
                $v = $vals[$name];
                $esc = str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $v);
                $out[] = is_numeric($v) && !str_contains((string)$v, ' ')
                    ? "define('$name', $v);"
                    : "define('$name', '$esc');";
                continue;
            }
        }
        $out[] = $line;
    }
    return file_put_contents($configFile, implode("\n", $out) . "\n") !== false;
}

function lockSelf(): array {
    global $STORAGE, $LOCK, $ROOT;
    @mkdir($STORAGE, 0775, true);
    file_put_contents($LOCK, gmdate('c'));
    @rename($ROOT . '/install.php', $ROOT . '/install.php.locked-' . date('Ymd-His'));
    return ['ok' => true];
}

function sseHeaders(): void { header('Content-Type: text/event-stream'); header('Cache-Control: no-cache'); header('X-Accel-Buffering: no'); @ob_end_flush(); }
function sse(array $d): void { echo 'data: ' . json_encode($d) . "\n\n"; @ob_flush(); @flush(); }

function renderLocked(): void {
    echo '<!doctype html><meta charset=utf-8><title>Installer locked</title><body style="font-family:system-ui;max-width:40rem;margin:4rem auto;padding:1rem">';
    echo '<h1>Installer is locked</h1><p>This site is already installed. To reinstall, delete <code>storage/installer.locked</code> and rename <code>install.php.locked-*</code> back to <code>install.php</code>.</p></body>';
}

function renderWizard(array $fields, bool $hasMig): void {
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES);
    ?><!doctype html><html><head><meta charset=utf-8><meta name=viewport content="width=device-width,initial-scale=1">
<title>Install</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-100 text-slate-800">
<div class="max-w-2xl mx-auto my-10 bg-white rounded-xl shadow p-6">
  <h1 class="text-xl font-semibold mb-1">Install</h1>
  <p class="text-sm text-slate-500 mb-5">Frozen Selvatec installer. Create your database in cPanel first; this connects to it.</p>
  <div id="steps" class="flex gap-2 text-xs mb-6">
    <span data-i=1 class="px-2 py-1 rounded bg-emerald-600 text-white">1 Pre-flight</span>
    <span data-i=2 class="px-2 py-1 rounded bg-slate-200">2 Database</span>
    <span data-i=3 class="px-2 py-1 rounded bg-slate-200">3 Site</span>
    <span data-i=4 class="px-2 py-1 rounded bg-slate-200">4 Install</span>
    <span data-i=5 class="px-2 py-1 rounded bg-slate-200">5 Finalize</span>
  </div>

  <section id=s1><div id=checks class="space-y-1 text-sm font-mono"></div>
    <button onclick="go(2)" id=b1 disabled class="mt-4 bg-emerald-600 text-white px-4 py-2 rounded disabled:opacity-40">Next</button></section>

  <section id=s2 class="hidden space-y-3">
    <?php foreach ($fields['db'] as $f): ?>
      <label class="block text-sm"><?= $h($f['label']) ?>
        <input name="<?= $h($f['name']) ?>" value="<?= $f['secret'] ? '' : $h($f['default']) ?>" type="<?= $f['secret'] ? 'password' : 'text' ?>" class="mt-1 w-full border rounded px-2 py-1"></label>
    <?php endforeach; ?>
    <div id=dbresult class="text-sm"></div>
    <button onclick="testDb()" class="bg-slate-700 text-white px-4 py-2 rounded">Test connection</button>
    <button onclick="go(3)" id=b2 disabled class="bg-emerald-600 text-white px-4 py-2 rounded disabled:opacity-40">Next</button>
  </section>

  <section id=s3 class="hidden space-y-3">
    <?php foreach (array_merge($fields['app'], $fields['keys']) as $f): ?>
      <label class="block text-sm"><?= $h($f['label']) ?><?= $f['secret'] ? ' <span class="text-slate-400">(secret)</span>' : '' ?>
        <input name="<?= $h($f['name']) ?>" value="<?= $f['secret'] ? '' : $h($f['default']) ?>" type="<?= $f['secret'] ? 'password' : 'text' ?>" class="mt-1 w-full border rounded px-2 py-1"></label>
    <?php endforeach; ?>
    <button onclick="go(4);install()" class="bg-emerald-600 text-white px-4 py-2 rounded">Install</button>
  </section>

  <section id=s4 class="hidden"><div id=progress class="space-y-1 text-sm font-mono"></div></section>

  <section id=s5 class="hidden space-y-3">
    <p class="text-sm">Installation complete. Lock the installer now — it removes <code>install.php</code> from the public path.</p>
    <button onclick="lockIt()" class="bg-amber-600 text-white px-4 py-2 rounded">Lock installer</button>
    <div id=locked class="text-sm"></div>
  </section>
</div>
<script>
const $=s=>document.querySelector(s), all=s=>[...document.querySelectorAll(s)];
function go(n){all('section').forEach(x=>x.classList.add('hidden'));$('#s'+n).classList.remove('hidden');
  all('#steps span').forEach(s=>{const i=+s.dataset.i;s.className='px-2 py-1 rounded '+(i<n?'bg-emerald-300':i===n?'bg-emerald-600 text-white':'bg-slate-200')})}
function vals(sec){const o={};all(sec+' input').forEach(i=>o[i.name]=i.value);return o}
async function post(a,b){const r=await fetch('?action='+a,{method:'POST',body:JSON.stringify(b||{})});return r.json()}
async function check(){const r=await post('check');$('#checks').innerHTML=r.checks.map(c=>
  (c.ok?'<span class=text-emerald-600>✔</span> ':'<span class=text-rose-600>✗</span> ')+c.label+(c.detail?' <span class=text-slate-400>'+c.detail+'</span>':'')).join('<br>');
  $('#b1').disabled=!r.ok}
async function testDb(){$('#dbresult').textContent='Testing…';const r=await post('test-db',vals('#s2'));
  $('#dbresult').innerHTML=(r.ok?'<span class=text-emerald-600>':'<span class=text-rose-600>')+r.detail+'</span>';$('#b2').disabled=!r.ok}
function install(){const body=Object.assign(vals('#s2'),vals('#s3'));
  fetch('?action=install',{method:'POST',body:JSON.stringify(body)}).then(async res=>{
    const rd=res.body.getReader(),dec=new TextDecoder();let buf='';
    while(true){const{done,value}=await rd.read();if(done)break;buf+=dec.decode(value);
      let p;while((p=buf.indexOf('\n\n'))>=0){const line=buf.slice(0,p).replace(/^data: /,'');buf=buf.slice(p+2);
        const d=JSON.parse(line);if(d.msg)$('#progress').innerHTML+=(d.ok?'✔ ':'✗ ')+d.msg+(d.detail?' — '+d.detail:'')+'<br>';
        if(d.done)d.ok?go(5):$('#progress').innerHTML+='<br><b class=text-rose-600>Failed. Fix and retry.</b>'}}})}
async function lockIt(){const r=await post('lock');$('#locked').innerHTML=r.ok?'<b class=text-emerald-600>Locked.</b> You can delete install.php.locked-* once confirmed.':'Lock failed.'}
check();
</script>
</body></html><?php
}
