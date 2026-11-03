<?php

namespace Maatify\GoogleSheet\Tests;

use Google_Client;
use Google_Service_Sheets;
use Maatify\GoogleSheet\Tests\Support\FailingValues;
use Maatify\GoogleSheet\Tests\Support\TestSheetHandler;
use Maatify\Logger\Logger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Throwable;

/**
 * A failure of the logger itself must not replace the outcome of the operation that failed: when
 * the real maatify/logger cannot write (its log directory is read-only), WriteRow() still returns
 * false instead of throwing out of its catch block.
 *
 * The logger is the real one, not a mock. Where a logger release does not throw on an unwritable
 * directory there is nothing to verify and the test is skipped; the constructor's catch block
 * (a \Google\Exception that the pinned Google client never throws on this path) is covered by the
 * static check that both catch blocks go through the failure-safe helper.
 */
final class LoggingFailureTest extends TestCase
{
    private string $hourDir = '';
    private string $credentialsDir = '';

    protected function setUp(): void
    {
        $loggerFile = (string)(new ReflectionClass(Logger::class))->getFileName();
        $this->hourDir = sprintf('%s/logs/%s/%s/%s/%s', dirname($loggerFile, 5), date('Y'), date('m'), date('d'), date('H'));
        if (!is_dir($this->hourDir)) {
            mkdir($this->hourDir, 0775, true);
        }
        // Files already written this hour are made read-only too, so appending to them fails as well.
        foreach (glob($this->hourDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                chmod($file, 0444);
            }
        }
        chmod($this->hourDir, 0555);
        clearstatcache();
        if (is_writable($this->hourDir)) {
            $this->restore();
            $this->markTestSkipped('The log directory cannot be made read-only here (running as root?).');
        }
        $this->credentialsDir = sys_get_temp_dir() . '/gs-logging-failure-' . bin2hex(random_bytes(4));
        mkdir($this->credentialsDir);
    }

    protected function tearDown(): void
    {
        $this->restore();
        array_map('unlink', glob($this->credentialsDir . '/*') ?: []);
        if ($this->credentialsDir !== '') {
            @rmdir($this->credentialsDir);
        }
    }

    private function restore(): void
    {
        if ($this->hourDir !== '' && is_dir($this->hourDir)) {
            chmod($this->hourDir, 0775);
            foreach (glob($this->hourDir . '/*') ?: [] as $file) {
                if (is_file($file)) {
                    chmod($file, 0664);
                }
            }
        }
    }

    public function testAFailedWriteIsStillReportedAsFalseWhenTheLoggerCannotWrite(): void
    {
        $loggerThrows = false;
        try {
            Logger::RecordLog(message: 'probe', logFile: 'probe');
        } catch (Throwable) {
            $loggerThrows = true; // the real logger throws here: the situation under test
        }
        if (!$loggerThrows) {
            $this->markTestSkipped('This maatify/logger release does not throw on an unwritable log directory.');
        }

        $handler = new TestSheetHandler($this->credentialsFile(), 'sheet-id');
        $service = new Google_Service_Sheets(new Google_Client());
        $service->spreadsheets_values = new FailingValues('write failed');
        $handler->injectService($service);

        $this->assertFalse($handler->WriteRow(['a', 'b']));
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

    private function credentialsFile(): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $this->assertNotFalse($key);
        openssl_pkey_export($key, $pem);
        $file = $this->credentialsDir . '/service-account.json';
        file_put_contents($file, json_encode([
            'type' => 'service_account',
            'project_id' => 'p',
            'private_key_id' => 'key-id',
            'private_key' => $pem,
            'client_email' => 'x@p.iam.gserviceaccount.com',
            'client_id' => '1',
        ]));

        return $file;
    }
}
