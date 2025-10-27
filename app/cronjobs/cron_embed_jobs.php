<?php

declare(strict_types=1);

$projectDir = __DIR__ . '/../';
$venvActivate = '/opt/venv/bin/activate';
$console = $projectDir . 'bin/console';
$logFile = $projectDir . 'var/log/cron_embed_jobs.log';

if (!file_exists($venvActivate)) {
    echo "ERROR: venv activate not found: $venvActivate\n";
    exit(1);
}

if (!file_exists($console)) {
    echo "ERROR: Symfony console not found: $console\n";
    exit(1);
}

$consoleCommand = sprintf(
    '/bin/bash -c "source %s && exec php %s app:embed-jobs"',
    escapeshellarg($venvActivate),
    escapeshellarg($console),
);

$command = sprintf('%s >> %s 2>&1', $consoleCommand, escapeshellarg($logFile));

echo "Running: $command\n";
$output = [];
$pid = exec($command, $output);

echo "Started PID: $pid\n";
echo "Output:\n" . implode("\n", $output) . "\n";
