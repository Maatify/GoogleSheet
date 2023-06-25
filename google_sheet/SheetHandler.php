<?php

namespace Maatify\GoogleSheet;

use Exception;
use Google_Client;
use Google_Service_Sheets;
use Maatify\Logger\Logger;

abstract class SheetHandler
{
    //https://www.nidup.io/blog/manipulate-google-sheets-in-php-with-api
    //https://www.srijan.net/resources/blog/integrating-google-sheets-with-php-is-this-easy-know-how
    protected string $credentials_file;
    protected string $spread_sheet_Id;
    protected string $spread_sheet_range;
    private Google_Service_Sheets $service;
    private Google_Client $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName('Google Sheets API PHP Quickstart');
        //        $this->client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
        $this->client->setScopes(Google_Service_Sheets::SPREADSHEETS);

        try {
            $this->client->setAuthConfig($this->credentials_file);
            $this->client->setAccessType('offline');
            $this->service = new Google_Service_Sheets($this->client);
        } catch (\Google\Exception $e) {
            Logger::RecordLog($e, 'google_sheet_err');
        }
    }

    public function ReadAll(): array
    {
        try {
            return ($this->service->spreadsheets_values->get($this->spread_sheet_Id, $this->spread_sheet_range))->getValues();
        } catch (Exception $e) {
            return [];
        }
    }

    //1F79RIlr0Sz1hky0-Gt-OA2f79hAqid-YHpLxLr1QBI0
    //Project ID: test-sheets-366510.

    public function WriteRow(
        array $newRow): void
    {
        $rows = [$newRow]; // you can append several rows at once
        $valueRange = new \Google_Service_Sheets_ValueRange();
        $valueRange->setValues($rows);
        $range = 'Sheet1'; // the service will detect the last row of this sheet
        $options = ['valueInputOption' => 'USER_ENTERED'];
        try {
            $this->service->spreadsheets_values->append($this->spread_sheet_Id, $range, $valueRange, $options);
        } catch (Exception $exception) {
            echo $exception;
        }
        /*
        $newRow = [
            '456740',
            'Hellboy',
            'https://image.tmdb.org/t/p/w500/bk8LyaMqUtaQ9hUShuvFznQYQKR.jpg',
            "Hellboy comes to England, where he must defeat Nimue, Merlin's consort and the Blood Queen. But their battle will bring about the end of the world, a fate he desperately tries to turn away.",
            '1554944400',
            'Fantasy, Action'
        ];
        $rows = [$newRow]; // you can append several rows at once
        $valueRange = new \Google_Service_Sheets_ValueRange();
        $valueRange->setValues($rows);
        $range = 'Sheet1'; // the service will detect the last row of this sheet
        $options = ['valueInputOption' => 'USER_ENTERED'];
        $service->spreadsheets_values->append($spreadsheetId, $range, $valueRange, $options);
        */
    }

}