# php_google_sheet
Simple api class UGS to get data from Google Sheet's

## How to use?

Install or download pacakge from https://github.com/googleapis/google-api-php-client/releases

1. Copy source code (folder src) from downloaded package to own site folder
2. Put in folder src instance Google.php file
3. Put in folder, by default - src, file UGS.php & google-api-key-example.json

## How to get key.json?
1. Go to https://console.developers.google.com/apis/credentials
2. Create service account key && rename donwloaded key to google-api-key-example.json or ...

Key google-api-key-example.json get from
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
