<?php

namespace Maatify\GoogleSheet\Tests\Support;

use RuntimeException;

/** Stands in for the Sheets values resource and fails with a message carrying sensitive-looking text. */
final class FailingValues
{
    public function __construct(private readonly string $message)
    {
    }

    /**
     * @param array<mixed> $options
     */
    public function append(string $spreadsheetId, string $range, mixed $valueRange, array $options): never
    {
        throw new RuntimeException($this->message . ' ' . $spreadsheetId, 4242);
    }
}
