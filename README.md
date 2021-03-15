# CodeIgniter4 DataTables Library
Server-side Datatables library for CodeIgniter4 PHP framework
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
Extract and rename folder to `codeigniter4-datatables` in example place this on `ThirdParty` folder. 
Then open `app/Config/Autoload.php` and add namespace to the `$psr4` array.

```php
$psr4 = [
     APP_NAMESPACE => APPPATH, // For custom app namespace
     'Config'      => APPPATH . 'Config',
     'Hermawan\DataTables'   => APPPATH .'ThirdParty/codeigniter4-datatables/src', // <-- add this line
];
```


## Simple Initializing

This is simple basic code just write `DataTable::of($builder)` call method `toJson()` for output

`$builder` is CodeIgniter build-in Query Builder object.

**Controller :**
```php
use \Hermawan\DataTables\DataTable;

public function ajaxDatatable()
{
    $db = db_connect();
    $builder = $db->table('customers')->select('customerNumber, customerName, phone, city, country, postalCode');

    return DataTable::of($builder)->toJson();
}
```

**Javascript :**
```javascript
$(document).ready(function() {
    $('#table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '<?php echo site_url('customers/ajaxDatatable'); ?>'
    });
});
```



For more complete example and demo please visit [Documentation here](https://hermawan.dev/codeigniter4-datatables/)
