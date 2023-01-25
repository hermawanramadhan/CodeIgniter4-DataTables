<?php

namespace Hermawan\DataTables;

class Column
{

    public $key;
    public $alias;
    public $type;
    public $callback;
    public $searchable;
    public $orderable;


    public function __construct($key, $alias, $type ='column', $primaryKey = 'id', $searchable = TRUE, $orderable = TRUE)
    {
        $this->key        = $key;
        $this->alias      = $alias;
        $this->type       = $type;
        $this->searchable = $searchable;
        $this->orderable  = $orderable;

        if ($key === $primaryKey) {
            $this->type = 'primary';
        }
    }
}
