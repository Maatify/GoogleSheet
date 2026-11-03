<?php

namespace Maatify\GoogleSheet\Tests;

use PHPUnit\Framework\TestCase;

/**
 * The manifest keeps the Google API client at the first release that can resolve without known
 * advisories, and keeps Composer's fail-closed policy, so the lowest supported dependency set is
 * resolved and audited under that policy in CI.
 */
final class ComposerManifestTest extends TestCase
{
    /** @return array<string, mixed> */
    private function manifest(): array
    {
        $manifest = json_decode((string)file_get_contents(__DIR__ . '/../composer.json'), true);
        $this->assertIsArray($manifest);

        /** @var array<string, mixed> $manifest */
        return $manifest;
    }

    public function testTheGoogleApiClientFloorIsTheFirstReleaseThatCanResolveCleanly(): void
    {
        $require = $this->manifest()['require'] ?? null;
        $this->assertIsArray($require);
        $constraint = $require['google/apiclient'] ?? null;
        $this->assertIsString($constraint);
        $this->assertSame(1, preg_match('/^\^2\.(\d+)(?:\.\d+)?$/', $constraint, $match), 'Unexpected constraint: ' . $constraint);
        $this->assertGreaterThanOrEqual(19, (int)$match[1], 'google/apiclient releases before 2.19 cannot resolve to dependency versions without known advisories.');
    }

    public function testTheFailClosedComposerPolicyIsKept(): void
    {
        $config = $this->manifest()['config'] ?? null;
        $this->assertIsArray($config);
        $policy = $config['policy'] ?? null;
        $this->assertIsArray($policy);
        $this->assertSame(['block' => true, 'audit' => 'fail'], $policy['advisories'] ?? null);
        $this->assertSame(['block' => true, 'block-scope' => 'all', 'audit' => 'fail'], $policy['malware'] ?? null);
        $this->assertSame(['audit' => 'fail'], $policy['abandoned'] ?? null);
    }
}
