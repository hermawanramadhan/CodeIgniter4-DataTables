<?php 
namespace Hermawan\DataTables;

use \Config\Services;

class DataTable
{

     /**
     * DataTableQuery object.
     *
     * @var \Hermawan\CodeIgniter4-DataTable\DataTableQuery
     */
    private $query; 


     /**
     * DataTablColumns object.
     *
     * @var \Hermawan\CodeIgniter4-DataTable\DataTableColumns
     */
    private $columnDefs; 


     /**
     * Builder from CodeIgniter Query Builder
     * @param  Builder $builder
     */
    public function __construct($builder)
    {
        if(is_subclass_of($builder, '\CodeIgniter\BaseModel') && method_exists($builder, 'builder')){
            $builder = $builder->builder();
        }
        $this->query      = new DataTableQuery($builder);
        $this->columnDefs = new DataTableColumnDefs($builder);
    }

    /**
     * Make a DataTable instance from builder.
     *  
     * Builder from CodeIgniter Query Builder
     * @param  Builder $builder
     */
    public static function of($builder)
    {
        return new self($builder);
    }

    /**
     * postQuery
     * @param Closure $postQuery
     */
    public function postQuery($postQuery)
    {
        $this->query->postQuery($postQuery);
        return $this;
    }

    /**
     * custom Filter 
     * @param Closure function
     */
    public function filter($filterFunction)
    {
        $this->query->filter($filterFunction);
        return $this;
    }


    /**
     * Add numbering to first column
     * @param String $column 
     */
    public function addNumbering($column = NULL)
    {
        $this->columnDefs->addNumbering($column);
        return $this;
    }

   
     /**
     * Add extra column 
     *
     * @param String $column
     * @param Closure $callback
     * @param String|int $position
     */
    public function add($column, $callback, $position = 'last')
    {
        $this->columnDefs->add($column, $callback, $position);
        return $this;
    }

    /**
     * Edit column 
     *
     * @param String $column
     * @param Closure $callback
     */
    public function edit($column, $callback)
    {
        $this->columnDefs->edit($column, $callback);
        return $this;
    }

    /**
     * Format column 
     *
     * @param String $column
     * @param Closure $callback
     */
    public function format($column, $callback)
    {
        $this->columnDefs->format($column, $callback);
        return $this;
    }

     /**
     * Hide column 
     *
     * @param String $column
     */
    public function hide($column)
    {
        $this->columnDefs->remove($column);
        return $this;
    }

     /**
     * Set Searchable columns
     * @param String|Array
     */
    public function setSearchableColumns($columns)
    {
        $this->columnDefs->setSearchable($columns);
        return $this;
    }

    /**
     * Add Searchable columns
     * @param String|Array
     */
    public function addSearchableColumns($columns)
    {
        $this->columnDefs->addSearchable($columns);
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

        if(! Request::get('draw'))
        {
            return self::throwError('no datatable request detected');
        }

        if($returnAsObject !== NULL) 
            $this->columnDefs->returnAsObject($returnAsObject);

        $this->query->setColumnDefs($this->columnDefs);
        
        $response = Services::response();

        return $response->setJSON([
            'draw'              => Request::get('draw'),
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


   
}   // End of DataTables Library Class.
