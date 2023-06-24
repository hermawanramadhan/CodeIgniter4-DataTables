# CodeIgniter4 DataTables Library
[![Donate](https://img.shields.io/badge/donate-paypal-blue.svg)](https://www.paypal.me/mbahcoding)
[![Total Downloads](https://poser.pugx.org/hermawan/codeigniter4-datatables/downloads)](https://packagist.org/packages/hermawan/codeigniter4-datatables)
![GitHub repo size](https://img.shields.io/github/repo-size/hermawanramadhan/CodeIgniter4-DataTables?label=size)
![GitHub](https://img.shields.io/github/license/hermawanramadhan/CodeIgniter4-DataTables)

Server-side Datatables library for CodeIgniter4 PHP framework
CodeIgniter4-DataTables is CodeIgniter4 Library to handle server-side processing of DataTables jQuery Plugin via AJAX option by using Query Builder CodeIgniter 4

# Documentation
For more complete example and demo please visit [Documentation here](https://codeigniter4-datatables.hermawan.dev/welcome)



## Requirements
* Codeigniter 4.x
* jQuery DataTables v1.10.x

## Installing 

### Using composer 
Use composer to install CodeIgniter4-DataTables into your project :

  > composer require hermawan/codeigniter4-datatables


## Simple Initializing

### Using CodeIgniter Query Builder
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

### Using CodeIgniter Model
You can initialize using `Model` instead `Query Builder` 
This is simple example basic code

**Controller :**
```php
use \Hermawan\DataTables\DataTable;
use \App\Models\CustomerModel;

public function ajaxDatatable()
{
    $customerModel = new CustomerModel();
    $customerModel->select('customerNumber, customerName, phone, city, country, postalCode');

    return DataTable::of($customerModel)->toJson();
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



For more complete example and demo please visit [Documentation here](https://codeigniter4-datatables.hermawan.dev/welcome)
