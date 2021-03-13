# CodeIgniter4 DataTables Library
Server-side Datatables library for CodeIgniter4

## Description
CodeIgniter4-DataTables is CodeIgniter4 Library to handle server-side processing of DataTables jQuery Plugin via AJAX option by using Query Builder CodeIgniter 4


## Requirements
* Codeigniter 4.x
* jQuery DataTables v1.10.x

## Installing using composer
Use composer to install CodeIgniter4-DataTables into your project :

  > composer require hermawan/codeigniter4-datatables


### Manual installation
Or If you prefer not to use Composer to install, you can install manually. 
Download this from git repository. 
Extract and rename folder to codeigniter4-datatables in example place this on ThirdParty folder. 
Then open app/Config/Autoload.php and add namespace to the $psr4 array.

```php
$psr4 = [
     APP_NAMESPACE => APPPATH, // For custom app namespace
     'Config'      => APPPATH . 'Config',
     'Hermawan\DataTables'   => APPPATH .'ThirdParty/codeigniter4-datatables/src', // <-- add this line
];
```


## Example & Documentation

You can handle server-side processing of DataTables easier by only using build-in CodeIgniter Query Builder object:
```php
use \Hermawan\DataTables\DataTable;

$db = db_connect();
$queryBuilder = $db->table('users');

DataTable::of($queryBuilder)->toJson();
```
More detail [Read Complete Documentation here](https://hermawan.dev/codeigniter4-datatables/)
