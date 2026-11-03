<?php

namespace Maatify\GoogleSheet\Tests\Support;

use Google_Service_Sheets;
use Maatify\GoogleSheet\SheetHandler;
use ReflectionProperty;

/** A concrete SheetHandler for tests: a service-account file the client accepts offline and an injectable service. */
final class TestSheetHandler extends SheetHandler
{
    public function __construct(string $credentialsFile, string $spreadSheetId)
    {
        $this->credentials_file = $credentialsFile;
        $this->spread_sheet_Id = $spreadSheetId;
        $this->spread_sheet_range = 'Sheet1!A:Z';
        parent::__construct();
    }

    public function injectService(Google_Service_Sheets $service): void
    {
        (new ReflectionProperty(SheetHandler::class, 'service'))->setValue($this, $service);
    }
}
