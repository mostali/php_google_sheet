# php_google_sheet
Simple api class UGS to get data from Google Sheet's

##Install or download 

Install or download pacakge from https://github.com/googleapis/google-api-php-client/releases

1. Copy source code (folder src) from downloaded package
2. Put in folder src instance Google.php file
3. Put in folder, by default - src, file UGS.php & google-api-key-example.json

```
include_once $_SERVER['DOCUMENT_ROOT'] . '/src/UGS.php';

$gsheet=new GoogleSheetCache('sheet id code');

echo 'All Found Tables from '.$gsheet->getSheetId();
var_dump($gsheet->getTableNames());

foreach($gsheet->getTableNames() as $tableName)
{
   echo 'All Found Data from table '.$tableName;
   $rows=$gsheet->getTableDataRows($tableName);
   var_dump(rows);
}

```
