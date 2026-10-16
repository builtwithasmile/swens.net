<?php
declare(strict_types=1);

// LocalTime: a UTC stamp is shown in America/Vancouver (fleet time audit 2026-10-16).
use App\Services\LocalTime;

t_eq(LocalTime::show('2026-10-17 01:30:00', 'Y-m-d H:i'), '2026-10-16 18:30', 'local time: UTC evening stamp shows the local day and time');
t_eq(LocalTime::show(null, 'H:i', 'none'), 'none', 'local time: null gives the empty text');
t_eq(LocalTime::show('0000-00-00 00:00:00', 'H:i', '-'), '-', 'local time: zero date gives the empty text');
t_ok(str_contains((string) file_get_contents(dirname(__DIR__) . '/templates/pages/admin/audit.php'), 'LocalTime::show'), 'local time: templates/pages/admin/audit.php routes its time through LocalTime');
