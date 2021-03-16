<?php 
namespace Hermawan\DataTables;

use \Config\Services;

class DataTableServerSide
{

     /**
     * DataTableQuery object.
     *
     * @var \Hermawan\CodeIgniter4-DataTable\DataTableQuery
     */
    private $query; 


     /**
     * Builder from CodeIgniter Query Builder
     * @param  Builder $builder
     */
    public function __construct($builder)
    {
        $this->query = new DataTableQuery($builder);
        return $this;
    }

     /**
     * Add numbering to first column
     * @param String $column 
     */
    public function addNumbering($column = NULL)
    {
        $this->query->addNumbering($column);
        return $this;
    }

    /**
     * custom Filter 
     * @param callback function
     */
    public function filter($filterFunction)
    {
        $this->query->filter($filterFunction);
        return $this;
    }


    /**
     * Set Searchable columns
     * @param String|Array
     */
    public function setSearchableColumns($columns)
    {
        $this->query->setSearchableColumns($columns);
        return $this;
    }

    /**
     * Add Searchable columns
     * @param String|Array
     */
    public function addSearchableColumns($columns)
    {
        $this->query->addSearchableColumns($columns);
        return $this;
    }

     /**
     * Add extra column 
     *
     * @param String $column
     * @param Callback $callback
     * @param String|int $position
     */
    public function add($column, $callback, $position = 'last')
    {
        $this->query->addColumn($column, $callback, $position);
        return $this;
    }

    /**
     * Edit column 
     *
     * @param String $column
     * @param Callback $callback
     */
    public function edit($column, $callback)
    {
        $this->query->editColumn($column, $callback);
        return $this;
    }

    /**
     * Format column 
     *
     * @param String $column
     * @param Callback $callback
     */
    public function format($column, $callback)
    {
        $this->query->formatColumn($column, $callback);
        return $this;
    }

     /**
     * Hide column 
     *
     * @param String $column
     */
    public function hide($column)
    {
        $this->query->removeColumn($column);
        return $this;
    }


     /**
     * Return JSON output 
     *
     * @param bool $returnAsObject
     * @return JSON
     */
    public function toJson($returnAsObject = NULL)
    {

        if(! DataTable::request('draw'))
        {
            return DataTableServerSide::throwError('no datatable request detected');
        }

        if($returnAsObject !== NULL) 
            $this->query->returnAsObject($returnAsObject);
        
        $response = Services::response();

        return $response->setJSON([
            'draw'              => DataTable::request('draw'),
            'recordsTotal'      => $this->query->countAll(),
            'recordsFiltered'   => $this->query->countFiltered(),
            'data'              => $this->query->getDataResult(),

        ]);
    }


    /**
     * Throw Error
     *
     * @param String $message
     * @return Error
     */
    public static function throwError($message)
    {
        $response = Services::response();
        return $response->setJSON([
            'error'             => $message,

        ]);
    }
  

}   // End of DataTableServerSide Class.
