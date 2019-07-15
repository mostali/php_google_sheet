<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/src/autoload.php';

/**
 * Utils Google Sheets 
 * https://developers.google.com/sheets/api/quickstart/php
 * https://github.com/gsuitedevs/php-samples
 * //
 * https://github.com/Archakov06/react-products-example
 * https://github.com/googleapis/google-api-php-client/blob/master/README.md#installation
 * https://console.developers.google.com/cloud-resource-manager
 * https://github.com/gsuitedevs/php-samples
 * https://developers.google.com/sheets/api/quickstart/php
 * &&
 * https://github.com/asimlqt/php-google-spreadsheet-client
 * https://github.com/mach3/google-spreadsheet.php
 * https://github.com/magnetikonline/php-google-spreadsheet-api
 * &&
 * https://codd-wd.ru/primery-google-sheets-tablicy-api-php/
 */
class UGS {

    static $keyFilePath_DEFAULT = __DIR__ . '/google-api-key-example.json';
    static $keyFilePath_CURRENT = null;
    //
    static $client = null;
    static $service = null;

    static function getTableData($sheetId, $tableNameOrRange) {

        if (self::$keyFilePath_CURRENT === null)
            self::init();

        trace("UGS::getTableData|$sheetId|$tableNameOrRange");
        UT::notEmpty(self::$service, "init service&client");
        UT::notEmpty($sheetId, "empty sheetId");

        $response = self::$service->spreadsheets_values->get($sheetId, $tableNameOrRange);
        return $response['values'];
    }

    static function getTables($sheetId, $filterSheetTitleNameClosure = null) {

        if (self::$keyFilePath_CURRENT === null)
            self::init();

        trace("UGS::getTables|$sheetId");
        UT::notEmpty(self::$service, "init service&client");
        UT::notEmpty($sheetId, "empty sheetId");

        $response = self::$service->spreadsheets->get($sheetId);

        $spreadsheetProperties = $response->getProperties();

        $sheets = array();
        foreach ($response->getSheets() as $sheet) {
            $sheetProperties = $sheet->getProperties();
            if ($filterSheetTitleNameClosure != null && !$filterSheetTitleNameClosure($sheetProperties->title))
                continue;
            $gridProperties = $sheetProperties->getGridProperties();
            $sheets[$sheetProperties->title] = array('' => array($gridProperties->columnCount, $gridProperties->rowCount));
        }
        return $sheets;
    }

    static $v = 0;

    static function init($googleAccountKeyFilePath = null) {

        $isset = isset($googleAccountKeyFilePath) && !empty($googleAccountKeyFilePath);
        $googleAccountKeyFilePath = $isset ? $googleAccountKeyFilePath : self::$keyFilePath_DEFAULT;

        self::$client = new Google_Client();

        putenv("GOOGLE_APPLICATION_CREDENTIALS=$googleAccountKeyFilePath");

        self::$client->useApplicationDefaultCredentials();

        self::$client->addScope('https://www.googleapis.com/auth/spreadsheets');

        self::$service = new Google_Service_Sheets(self::$client);

        self::$keyFilePath_CURRENT = $googleAccountKeyFilePath;
    }

    static function info($spreadsheetId) {
        if (self::$service == null)
            throw new Exception("init service&client");
        if (empty($spreadsheetId))
            throw new Exception("empty sheetId");


        $response = self::$service->spreadsheets->get($spreadsheetId);

        $spreadsheetProperties = $response->getProperties();
        $spreadsheetProperties->title; // Название таблицы

        foreach ($response->getSheets() as $sheet) {

            // Свойства листа
            $sheetProperties = $sheet->getProperties();
            echo $sheetProperties->title; // Название листа
            echo'</br>';
            $gridProperties = $sheetProperties->getGridProperties();
            echo $gridProperties->columnCount; // Количество колонок
            echo'</br>';
            echo $gridProperties->rowCount; // Количество строк
            echo'</br>';
            echo'</br>';
        }
    }

}