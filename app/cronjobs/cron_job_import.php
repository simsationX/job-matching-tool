<?php

declare(strict_types=1);

$projectDir = __DIR__ . '/../';
$console = $projectDir . 'bin/console';
$logFile = $projectDir . 'var/log/cron_job_import.log';

if (!file_exists($console)) {
    echo "ERROR: Symfony console not found: $console\n";
    exit(1);
}

$consoleCommand = sprintf(
    '/bin/bash -c "exec php %s app:jobs-import"',
    escapeshellarg($console)
);

$command = sprintf('%s >> %s 2>&1', $consoleCommand, escapeshellarg($logFile));

echo "Running: $command\n";
$output = [];
$pid = exec($command, $output);

echo "Started PID: $pid\n";
echo "Output:\n" . implode("\n", $output) . "\n";
