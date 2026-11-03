<?php

// Runs the failing-logger scenario in its own process, against a private copy of the logger whose
// log directory lives in a temporary project root, and prints the result as JSON.
// Usage: php logging_failure_runner.php <temporary-project-root> <credentials-file> <logger-file-in-package>

use Maatify\GoogleSheet\Tests\Support\FailingValues;
use Maatify\GoogleSheet\Tests\Support\TestSheetHandler;

$arguments = $_SERVER['argv'] ?? [];
if (!is_array($arguments) || count($arguments) !== 4 || !is_string($arguments[1]) || !is_string($arguments[2]) || !is_string($arguments[3])) {
    fwrite(STDERR, "Usage: php logging_failure_runner.php <temporary-project-root> <credentials-file> <logger-file-in-package>\n");
    exit(2);
}
[$script, $root, $credentials, $loggerFileInPackage] = $arguments;

require __DIR__ . '/../../vendor/autoload.php';
// The private copy of the logger is loaded before anything references the class, so the autoloader
// never loads the original: the logger resolves its log directory from its own location, which is
// now the temporary project root.
require $root . '/vendor/maatify/logger/' . $loggerFileInPackage;

$loggerThrows = false;
try {
    Maatify\Logger\Logger::RecordLog(message: 'probe', logFile: 'probe');
} catch (Throwable) {
    $loggerThrows = true;
}

$writeRow = 'not-run';
if ($loggerThrows) {
    $handler = new TestSheetHandler($credentials, 'sheet-id');
    $service = new Google_Service_Sheets(new Google_Client());
    $service->spreadsheets_values = new FailingValues('write failed');
    $handler->injectService($service);
    try {
        $writeRow = $handler->WriteRow(['a', 'b']) ? 'true' : 'false';
    } catch (Throwable $e) {
        $writeRow = 'threw ' . get_class($e);
    }
}

echo json_encode(['loggerThrows' => $loggerThrows, 'writeRow' => $writeRow], JSON_THROW_ON_ERROR);
