<?php

namespace Maatify\GoogleSheet;

use Exception;
use Google_Client;
use Google_Service_Sheets;
use Maatify\Logger\Logger;

class SheetHandler
{
    //https://www.nidup.io/blog/manipulate-google-sheets-in-php-with-api
    //https://www.srijan.net/resources/blog/integrating-google-sheets-with-php-is-this-easy-know-how

    public function ReadAll($credentials_location, $spread_sheet_Id, $spread_sheet_range): array
    {
        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
        try {
            $client->setAuthConfig($credentials_location);
            $client->setAccessType('offline');

            $service = new Google_Service_Sheets($client);

            return ($service->spreadsheets_values->get($spread_sheet_Id, $spread_sheet_range))->getValues();
        } catch (\Google\Exception $e) {
            Logger::RecordLog($e, 'google_sheet_err');
            return [];
        }
    }

    //1F79RIlr0Sz1hky0-Gt-OA2f79hAqid-YHpLxLr1QBI0
    //Project ID: test-sheets-366510.

    public function WriteRow($credentials_location,
        string $spread_sheet_Id,
        array $newRow): void
    {
        $rows = [$newRow]; // you can append several rows at once
        $valueRange = new \Google_Service_Sheets_ValueRange();
        $valueRange->setValues($rows);
        $range = 'Sheet1'; // the service will detect the last row of this sheet
        $options = ['valueInputOption' => 'USER_ENTERED'];


        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        try {
            $client->setAuthConfig($credentials_location);
            $client->setAccessType('offline');
            $service = new Google_Service_Sheets($client);
            $service->spreadsheets_values->append($spread_sheet_Id, $range, $valueRange, $options);
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