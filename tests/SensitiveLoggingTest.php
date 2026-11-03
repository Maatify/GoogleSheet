<?php

namespace Maatify\GoogleSheet\Tests;

use Google_Client;
use Google_Service_Sheets;
use Maatify\GoogleSheet\Tests\Support\CapturesLogs;
use Maatify\GoogleSheet\Tests\Support\FailingValues;
use Maatify\GoogleSheet\Tests\Support\TestSheetHandler;
use PHPUnit\Framework\TestCase;

/**
 * A failed sheet operation must be logged by exception class, code and line only: never the
 * exception message or trace (Google exceptions can carry credential paths, sheet ids and tokens),
 * whatever maatify/logger version is installed.
 */
final class SensitiveLoggingTest extends TestCase
{
    use CapturesLogs;

    private const CANARIES = ['CANARY-SECRET-TOKEN', 'CANARY-SHEET-ID', 'canary-credentials-dir', 'canary-project'];

    private string $dir = '';

    protected function setUp(): void
    {
        $this->startLogCapture();
        $this->dir = sys_get_temp_dir() . '/canary-credentials-dir-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        @rmdir($this->dir);
    }

    public function testAFailedWriteLogsOnlyTheExceptionClassCodeAndLine(): void
    {
        $handler = new TestSheetHandler($this->credentialsFile(), 'CANARY-SHEET-ID');
        $service = new Google_Service_Sheets(new Google_Client());
        $service->spreadsheets_values = new FailingValues('CANARY-SECRET-TOKEN failed at ' . $this->dir);
        $handler->injectService($service);

        $this->assertFalse($handler->WriteRow(['a', 'b']));

        $logged = $this->loggedText();
        $this->assertStringContainsString('RuntimeException', $logged);
        $this->assertStringContainsString('4242', $logged);
        foreach (self::CANARIES as $canary) {
            $this->assertStringNotContainsString($canary, $logged);
        }
    }

    public function testNoLogCallPassesTheExceptionOrItsMessage(): void
    {
        $calls = 0;
        foreach (glob(__DIR__ . '/../google_sheet/*.php') ?: [] as $file) {
            $code = (string)preg_replace('~//[^\n]*|/\*.*?\*/~s', '', (string)file_get_contents($file));
            preg_match_all('/Logger::RecordLog\((.*?)\);/s', $code, $matches);
            foreach ($matches[1] as $arguments) {
                $calls++;
                foreach (['getMessage', 'getTraceAsString', 'getFile', 'credentials_file', 'spread_sheet'] as $forbidden) {
                    $this->assertStringNotContainsString($forbidden, $arguments, basename($file));
                }
                $this->assertDoesNotMatchRegularExpression('/message:\s*\$(e|exception)\b/', $arguments, basename($file) . ': a raw throwable is logged with its message and trace');
            }
        }
        $this->assertGreaterThan(0, $calls, 'No Logger::RecordLog() call was found; the check would be vacuous.');
    }

    private function credentialsFile(): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $this->assertNotFalse($key);
        openssl_pkey_export($key, $pem);
        $file = $this->dir . '/service-account.json';
        file_put_contents($file, json_encode([
            'type' => 'service_account',
            'project_id' => 'canary-project',
            'private_key_id' => 'key-id',
            'private_key' => $pem,
            'client_email' => 'canary@canary-project.iam.gserviceaccount.com',
            'client_id' => '1',
        ]));

        return $file;
    }
}
