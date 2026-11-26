<?php
// Local stand-in for cron: runs the fastn Linear check once.
// On a real server FreeScout's scheduler (cron) runs it every minute.
// Usage: podman exec -u www-data freescout-app php docker-local/linear-check.php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$r = \Modules\FastnIntegrations\Services\FastnClient::runLinearCheck();
echo date('c'), ' ', json_encode($r ? ['ok' => $r['ok'], 'error' => $r['error']] : 'skipped'), "\n";
