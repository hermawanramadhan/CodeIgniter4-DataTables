<?php
namespace Hermawan\DataTables;

class Column{

    public $key;
    public $alias;
    public $type = 'column';
    public $callback;
    public $searchable = TRUE;
    public $orderable = TRUE;


    public function __construct($key, $alias, $primaryKey = 'id')
    {
        $this->key = $key;
        $this->alias = $alias;

        if($key === $primaryKey)
          $this->type = 'primary';
        }
    }
}