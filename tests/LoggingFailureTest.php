<?php

namespace Maatify\GoogleSheet\Tests;

use FilesystemIterator;
use Maatify\Logger\Logger;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * A failure of the logger itself must not replace the outcome of the operation that failed: when
 * the real maatify/logger cannot write (its log directory is read-only), WriteRow() still returns
 * false instead of throwing out of its catch block.
 *
 * The logger is the real one, not a mock, and the scenario runs in a child process against a private
 * copy of the installed logger package inside a temporary project root, so the repository's own files and
 * permissions are never touched. Where a logger release does not throw on an unwritable directory
 * there is nothing to verify and the test is skipped. The constructor's catch block (a
 * \Google\Exception that the pinned Google client never throws on this path) is covered by the
 * static check that both catch blocks go through the failure-safe helper.
 */
final class LoggingFailureTest extends TestCase
{
    public function testAFailedWriteIsStillReportedAsFalseWhenTheLoggerCannotWrite(): void
    {
        $root = sys_get_temp_dir() . '/gs-logging-failure-' . bin2hex(random_bytes(4));
        $hourDir = sprintf('%s/logs/%s/%s/%s/%s', $root, date('Y'), date('m'), date('d'), date('H'));
        try {
            $loggerFile = (string)(new ReflectionClass(Logger::class))->getFileName();
            $installed = dirname($loggerFile, 2);
            $this->copyDirectory($installed, $root . '/vendor/maatify/logger');
            mkdir($hourDir, 0775, true);
            chmod($hourDir, 0555);
            clearstatcache();
            if (is_writable($hourDir)) {
                $this->markTestSkipped('The temporary log directory cannot be made read-only here (running as root?).');
            }

            $result = $this->runScenario($root, substr($loggerFile, strlen($installed) + 1));
        } finally {
            $this->removeTree($root);
        }

        if (($result['loggerThrows'] ?? null) === false) {
            $this->markTestSkipped('This maatify/logger release does not throw on an unwritable log directory.');
        }
        $this->assertSame('false', $result['writeRow'] ?? null, 'WriteRow() must report the failed write as false even though the logger threw.');
    }

    public function testBothCatchBlocksReportThroughTheFailureSafeHelper(): void
    {
        $source = (string)preg_replace('~//[^\n]*|/\*.*?\*/~s', '', (string)file_get_contents(__DIR__ . '/../google_sheet/SheetHandler.php'));
        preg_match_all('/catch\s*\([^)]*\)\s*\{(.*?)\n\s{8}\}/s', $source, $blocks);
        $guarded = 0;
        foreach ($blocks[1] as $block) {
            if (!str_contains($block, 'self::reportFailure(')) {
                continue;
            }
            $guarded++;
            $this->assertStringNotContainsString('Logger::RecordLog', $block, 'A catch block calls the logger directly.');
        }
        $this->assertSame(2, $guarded, 'Both reporting catch blocks must use self::reportFailure().');
        $this->assertSame(1, substr_count($source, 'Logger::RecordLog('), 'Only the helper may call the logger.');
    }

    /** @return array<string, mixed> */
    private function runScenario(string $root, string $loggerFileInPackage): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $this->assertNotFalse($key);
        openssl_pkey_export($key, $pem);
        $credentials = $root . '/service-account.json';
        file_put_contents($credentials, json_encode([
            'type' => 'service_account',
            'project_id' => 'p',
            'private_key_id' => 'key-id',
            'private_key' => $pem,
            'client_email' => 'x@p.iam.gserviceaccount.com',
            'client_id' => '1',
        ]));

        $output = (string)shell_exec(sprintf(
            '%s -d display_errors=0 %s %s %s %s 2>/dev/null',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__ . '/Support/logging_failure_runner.php'),
            escapeshellarg($root),
            escapeshellarg($credentials),
            escapeshellarg($loggerFileInPackage)
        ));
        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded, 'The scenario runner printed no result: ' . $output);

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function copyDirectory(string $from, string $to): void
    {
        mkdir($to, 0775, true);
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
            $relative = substr($item->getPathname(), strlen($from) + 1);
            if ($item->isDir()) {
                mkdir($to . '/' . $relative, 0775, true);
            } else {
                copy($item->getPathname(), $to . '/' . $relative);
            }
        }
    }

    /** Removes the temporary tree, restoring write permission on every directory first. */
    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        chmod($path, 0775);
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item) {
            if ($item->isDir() && !$item->isLink()) {
                chmod($item->getPathname(), 0775);
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($path);
    }
}
