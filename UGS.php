<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/src/autoload.php';

/**
* Call this function before call to Google API (limit 1 sec)
*/
function run_tl_func_gs() {
    //run_tl_func('gs-call', 1000);
}

class UGS {

    static $keyFilePath_DEFAULT = __DIR__ . '/google-api-key-example.json'; 
    static $keyFilePath_CURRENT = null;
//
    static $client = null;
    static $service = null;

    static function insertPermission($sheetId, $emailAddress) {
        run_tl_func_gs();
        if (self::$keyFilePath_CURRENT === null)
            self::init();

        $driveService = new Google_Service_Drive(self::$client);

        $driveService->getClient()->setUseBatch(true);
        try {
            $batch = $driveService->createBatch();

            $userPermission = new Google_Service_Drive_Permission(array(
                'type' => 'user',
                'role' => 'writer',
                'emailAddress' => $emailAddress,
            ));
            $request = $driveService->permissions->create(
                    $sheetId, $userPermission, array('fields' => 'id'));
            $batch->add($request, 'user');

//            $domainPermission = new Google_Service_Drive_Permission(array(
//                'type' => 'domain',
//                'role' => 'reader',
//                'domain' => 'example.com'
//            ));
//            $request = $driveService->permissions->create(
//                    $sheetId, $domainPermission, array('fields' => 'id'));
//            $batch->add($request, 'domain');

            $results = $batch->execute();
//
            foreach ($results as $result) {
                if ($result instanceof Google_Service_Exception) {
                    // Handle error
                    printf($result);
                } else {
                    printf("Permission ID: %s\n", $result->id);
                }
            }
        } finally {
            $driveService->getClient()->setUseBatch(false);
        }
//        return $response;
    }

    static function getSpreadsheet($sheetId) {
        run_tl_func_gs();
        if (self::$keyFilePath_CURRENT === null)
            self::init();
        $response = self::$service->spreadsheets->get($sheetId);
        return $response;
    }

    static function createSpreadsheet() {
        run_tl_func_gs();
        if (self::$keyFilePath_CURRENT === null)
            self::init();
        $requestBody = new Google_Service_Sheets_Spreadsheet();
        $response = self::$service->spreadsheets->create($requestBody);
        return $response;
    }

//    static function copySpreadsheet($srcSheetId) {
//        if (self::$keyFilePath_CURRENT === null)
//            self::init();
//        $requestBody = new Google_Service_Sheets_CopySheetToAnotherSpreadsheetRequest();
//        $response = self::$service->spreadsheets->copyTo($srcSheetId, 0, $requestBody);
//        return $response;
//    }

    static function clearTableData($sheetId, $tableNameOrRange) {
        run_tl_func_gs();
        if (self::$keyFilePath_CURRENT === null)
            self::init();
        UT::notEmpty(self::$service, "init service&client");
        UT::notEmpty($sheetId, "empty sheetId");
        trace("UGS::clearTableData|$sheetId|$tableNameOrRange");
        $response = self::$service->spreadsheets_values->clear($sheetId, $tableNameOrRange, new Google_Service_Sheets_ClearValuesRequest([]));
//        return $response['values'];
    }

    static function getTableData($sheetId, $tableNameOrRange) {
        run_tl_func_gs();
        if (self::$keyFilePath_CURRENT === null)
            self::init();
        UT::notEmpty(self::$service, "init service&client");
        UT::notEmpty($sheetId, "empty sheetId");
        trace("UGS::getTableData|$sheetId|$tableNameOrRange");
        $response = self::$service->spreadsheets_values->get($sheetId, $tableNameOrRange);
        return $response['values'];
    }

    static function updateTableData($sheetId, $tableNameOrRange, $values_rows) {
        run_tl_func_gs();
        if (self::$keyFilePath_CURRENT === null)
            self::init();
        UT::notEmpty(self::$service, "init service&client");
        UT::notEmpty($sheetId, "empty sheetId");
        trace("UGS::updateTableData|$sheetId|$tableNameOrRange|" . count($values_rows));
        $body = new Google_Service_Sheets_ValueRange(['values' => $values_rows]);
        $options = array('valueInputOption' => 'RAW');
        $response = self::$service->spreadsheets_values->update($sheetId, $tableNameOrRange, $body, $options);
    }

    /**
     * Table info
     */
    static function getTables($sheetId, $funcCheckTableName = null) {
        run_tl_func_gs();
        if (self::$keyFilePath_CURRENT === null)
            self::init();
        UT::notEmpty(self::$service, "init service&client");
        UT::notEmpty($sheetId, "empty sheetId");
        trace("UGS::getTables|$sheetId");
        $response = self::$service->spreadsheets->get($sheetId);
        $spreadsheetProperties = $response->getProperties();
        $sheets = array();
        foreach ($response->getSheets() as $sheet) {
            $sheetProperties = $sheet->getProperties();
            if ($funcCheckTableName != null && !$funcCheckTableName($sheetProperties->title))
                continue;
            $gridProperties = $sheetProperties->getGridProperties();
            $sheets[$sheetProperties->title] = array('cols' => $gridProperties->columnCount, 'rows' => $gridProperties->rowCount);
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

//        self::$client->addScope('https://www.googleapis.com/auth/spreadsheets');
        self::$client->addScope(array('https://www.googleapis.com/auth/drive', 'https://www.googleapis.com/auth/spreadsheets'));

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

class GoogleSheetCache {

    public $sheetId;
//
    public $tables;
    public $tablesData;

    public function getSheetId() {
        return $this->sheetId;
    }

    public function __construct($sheetId, $tables = null) {
        $this->sheetId = $sheetId;
        $this->tables = $tables;
    }

    public static $sheets = array();

    public static function load($sheetId) {
        if (empty(self::$sheets[$sheetId]))
            self::$sheets[$sheetId] = new GoogleSheetCache($sheetId);
        return self::$sheets[$sheetId];
    }

    public function cloneDoc() {
        $doc = new GoogleSheetCache($this->sheetId, $this->tables);
        $doc->tablesData = $doc->tablesData;
        return $doc;
    }

    public static function of($sheetId) {
        return new GoogleSheetCache($sheetId);
    }

    public function getNextTableRows($offsetList = 0) {
        $i = 0;
        foreach ($this->getTableNames() as $table_name)
            if ($i++ === $offsetList)
                return $this->getTableDataRows($table_name);
        return null;
    }

    public function getTableNames($funcCheckTableName = null) {
        return array_keys($this->getTables($funcCheckTableName));
    }

    public function getTables($funcCheckTableName = null) {
        if ($this->tables == null)
            $this->tables = UGS::getTables($this->sheetId, $funcCheckTableName);
//            $this->tables = UGS::getTables($this->sheetId, static function($name) use ($tableNameStartWith) {
//                        return empty($tableNameStartWith) ? true : US::startsWith($name, $tableNameStartWith);
//                    });
        return $this->tables;
    }

    public function getTableDataRows($table_name) {
        if (isset($this->tablesData[$table_name]))
            return $this->tablesData[$table_name];
        $table_rows = UGS::getTableData($this->sheetId, $table_name);
        $this->tablesData[$table_name] = $table_rows;
        return $table_rows;
    }

    public function setTableDataRows($table_name, $rows, $update = false) {
        $this->tablesData[$table_name] = $rows;
        if ($update)
            UGS::updateTableData($this->sheetId, $table_name, $rows);
//        return $this;
    }

//    public function clearTableData($table_name, $rows, $update = false) {
//        $this->tablesData[$table_name] = $rows;
//        UGS::updateTableData($this->sheetId, $table_name, new Google_Service_Sheets_ClearValuesRequest([]));
//    }
}

class GoogleSheetCacheFile {

    public static $__tables__ = "__tables__";
    public $sheetId = null;
    public $time_actual_sec = null;
    //
    public $tablesInfo;
    public $tablesData;
    public $tables_update_times;

    public function getSheetId() {
        return $this->sheetId;
    }

    public function __construct($sheetId, $time_actual_sec = 60) {
        $this->sheetId = $sheetId;
        $this->tablesInfo = null;
        $this->time_actual_sec = $time_actual_sec;
        $this->tablesData = array();
    }

    public static function of($sheetId, $time_actual_sec = 60) {
        return new GoogleSheetCacheFile($sheetId, $time_actual_sec);
    }

    public function getTableNames($funcCheckTableName = null,$dir_store = "%data") {
        $tables=array();
        foreach ($this->getTablesInfo() as $tablename => $cols_rows_grid_info_arr) {
            if($funcCheckTableName===null||$funcCheckTableName($tablename))
                $tables[]=$tablename;
        }
        return $tables;
    }

    public function getTablesInfo($dir_store = "%data") {
        if ($this->tablesInfo != null) {
            $last_time = $this->tables_update_times[self::$__tables__];
            $date_last_update = $last_time + $this->time_actual_sec;
            if (time() < $date_last_update)
                return $this->tablesInfo;
        }
        $tables_info = self::read_tablenames_from_file_actual($this->getSheetId(), self::$__tables__, $this->time_actual_sec, $dir_store);
        $this->tablesInfo = $tables_info;
        $this->tables_update_times[self::$__tables__] = time();
        return $tables_info;
    }

    public function getTableRows($table_name, $dir_store = "%data") {
        if (isset($this->tablesData[$table_name])) {
            $last_time = $this->tables_update_times[$table_name];
            $date_last_update = $last_time + $this->time_actual_sec;
            if (time() < $date_last_update)
                return $this->tablesData[$table_name];
        }
        $table_rows = self::read_table_from_file_actual($this->getSheetId(), $table_name, $this->time_actual_sec, $dir_store);
        $this->tablesData[$table_name] = $table_rows;
        $this->tables_update_times[$table_name] = time();
        return $table_rows;
    }

    /**
     * 
     * STATIC
     * 
     */
    static function create_filename($sheet_id, $tablename, $dir_store = "%data") {
        return $_SERVER['DOCUMENT_ROOT'] . '/' . $dir_store . '/' . $sheet_id . '/' . $tablename;
    }

    static function read_tablenames_from_file_actual($sheet_id, $tablename, $if_date_more_sec = 60, $dir_store = "%data") {
        $filename = self::create_filename($sheet_id, $tablename, $dir_store);
        if (!file_exists($filename)) {
            $rows = self::save_tablenames_to_file($sheet_id, $tablename, $dir_store);
            return $rows;
        }
        $date_last_change = filemtime($filename) + $if_date_more_sec;
        if (time() < $date_last_change)
            return self::readArrayFromFile($filename);
        $rows = self::save_tablenames_to_file($sheet_id, $tablename, $dir_store);
        return $rows;
    }

    static function read_table_from_file_actual($sheet_id, $tablename, $if_date_more_sec = 60, $dir_store = "%data") {
        $filename = self::create_filename($sheet_id, $tablename, $dir_store);
        if (!file_exists($filename)) {
            $rows = self::save_table_to_file($sheet_id, $tablename, $dir_store);
            return $rows;
        }
        $date_last_change = filemtime($filename) + $if_date_more_sec;
        if (time() < $date_last_change)
            return self::readArrayFromFile($filename);
        $rows = self::save_table_to_file($sheet_id, $tablename, $dir_store);
        return $rows;
    }

    /**
     * WRITE
     */
    static function save_tablenames_to_file($sheet_id, $tablename, $dir_store = "%data") {
        $rows = UGS::getTables($sheet_id);
        self::write_table_to_file($rows, $sheet_id, $tablename, $dir_store);
        return $rows;
    }

    static function save_table_to_file($sheet_id, $tablename, $dir_store = "%data") {
        $rows = UGS::getTableData($sheet_id, $tablename);
        self::write_table_to_file($rows, $sheet_id, $tablename, $dir_store);
        return $rows;
    }

    static function write_table_to_file($rows, $sheet_id, $tablename, $dir_store = "%data") {
        $filename = self::create_filename($sheet_id, $tablename, $dir_store);
        self::writeArrayToFile($filename, $rows, true);
    }

    /**
     * Work with files
     */
    static function readArrayFromFile($fn) {
        return include $fn;
    }

    static function writeArrayToFile($fn, $array, $mkdir = false) {
        if ($mkdir)
            @mkdir(dirname($fn), 0755, true);
        $string_data = "<?php  return " . var_export($array, true) . ';';
        file_put_contents($fn, $string_data);
    }

}
