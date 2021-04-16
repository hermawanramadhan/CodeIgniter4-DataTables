<?php 
namespace Hermawan\DataTables;

use \PHPSQLParser\PHPSQLParser;

class DataTableQuery
{

    private $builder;
    private $baseSQL;
    private $baseSQLParsed;

    private $countResult;
    private $returnAsObject = FALSE;
    private $tableField = [];
    private $columns;
    private $columnsAdded   = [];
    private $columnsEdited  = [];
    private $columnsFormatted  = [];
    private $columnsRemoved = [];
    private $extraColumn = [];
    private $numbering;
    private $numberingColumn;
    private $filterFunction;
    private $searchableColumns;
    private $addedSearchableColumns = [];


    public function __construct($builder)
    {
        $this->builder = $builder;
    }

    /* Modified column */

    public function addNumbering($column)
    {
        $this->numbering = TRUE;
        $this->numberingColumn = $column;

        if($column !== NULL)
            $this->extraColumn[] = $column;

    }

    public function filter($filterFunction)
    {
        $this->filterFunction = $filterFunction;
    }

    public function setSearchableColumns($columns)
    {
        $this->searchableColumns = $columns;
    }

    public function addSearchableColumns($columns)
    {
        if(is_array($columns))
            $this->addedSearchableColumns = array_merge($this->addedSearchableColumns, $columns);
        else
            $this->addedSearchableColumns[] = $columns;
    }

    public function addColumn($column, $callback, $position)
    {
        $this->columnsAdded[] = [
            'name'      => $column,
            'callback'  => $callback,
            'position'  => $position
        ];

        $this->extraColumn[] = $column;
    }

    public function editColumn($column, $callback)
    {
        $this->columnsEdited[$column] = $callback;
    }

    public function formatColumn($column, $callback)
    {
        $this->columnsFormatted[$column] = $callback;
    }

    public function removeColumn($column)
    {
        if(is_array($column))
            $this->columnsRemoved = array_merge($this->columnsRemoved, $column);
        else
            $this->columnsRemoved[] = $column;
    }

    public function returnAsObject($returnAsObject)
    {
        $this->returnAsObject = $returnAsObject;
    }

    /* End Modified column */


   /* Generating result */
    

    public function countAll()
    {
    	$builder = clone $this->builder;

        $this->countResult = $this->countResult !== NULL ? $this->countResult : $builder->countAllResults();  
        return $this->countResult;	
    }

    public function countFiltered()
    {
        $builder    = clone $this->builder;
        $doQuerying = $this->queryFilterSearch($builder);
         
        $this->countResult = ($this->countResult !== NULL && ! $doQuerying) ? $this->countResult : $builder->countAllResults();  

        return $this->countResult;    
    }

    public function getDataResult()
    {
        $result = $this->queryResult();

        $dataResult = [];

        if($this->returnAsObject)
        {
            $number = DataTable::request('start');
            foreach (DataTable::request('columns') as $column) 
                $columns[] = $column['data'];

            foreach ($result as $row) 
            {
                $number++;
                $data = [];

                if($this->numbering && $this->numberingColumn !== NULL)
                    $data[$this->numberingColumn] = $number;
               
                foreach ($row as $key => $value) 
                {
                    if(! in_array($key, $this->columnsRemoved))
                    {
                        if(isset($this->columnsEdited[$key]))
                            $data[$key] = $this->columnsEdited[$key]($row);
                        elseif(isset($this->columnsFormatted[$key]))
                            $data[$key] = $this->columnsFormatted[$key]($value);
                        else
                            $data[$key] = $value;
                    }

                }

                foreach ($this->columnsAdded as $column) 
                    $data[$column['name']] = $column['callback']($row);

                $dataResult[] = $data;

            }
        }
        else
        {
            $number = DataTable::request('start');
            foreach ($result as $row) 
            {
                $number++;
                
                $data   = [];
                foreach ($row as $key => $value) 
                {
                    if(! in_array($key, $this->columnsRemoved))
                    {
                        if(isset($this->columnsEdited[$key]))
                            $data[] = $this->columnsEdited[$key]($row);
                        elseif(isset($this->columnsFormatted[$key]))
                            $data[] = $this->columnsFormatted[$key]($value);
                        else
                            $data[] = $value;
                    }

                }

                foreach ($this->columnsAdded as $column) 
                {
                    switch ($column['position']) {
                        case 'first':
                            array_unshift($data, $column['callback']($row));
                            break;
                        case 'last':
                            $data[] = $column['callback']($row);
                            break;
                        default:
                            array_splice( $data, $column['position'], 0, [$column['callback']($row)]);
                            break;
                    }

                }

                if($this->numbering)
                    array_unshift($data, $number);

                $data = array_values($data);

                $dataResult[] = $data;
            }

        }
        
        return $dataResult;
    }

    /* End Generating result */



    /* Querying */

    private function queryOrder($builder)
    {
        $dtOrders = DataTable::request('order');
        if($dtOrders)
        {

            $orderableColumns = [];
            $columns = $this->getColumns();

            if($this->returnAsObject)
            {
                foreach (DataTable::request('columns') as $dtColumn) 
                {
                    if($dtColumn['name'])
                        $orderableColumns[] = $dtColumn['name'];

                    elseif(in_array($dtColumn['data'], $columns))
                        $orderableColumns[] = array_search($dtColumn['data'], $columns);

                    else
                        $orderableColumns[] = $dtColumn['data'];

                }
            }
            else
            {
                foreach ($columns as $column => $alias) 
                    $orderableColumns[] = $column;
            }

            foreach ($dtOrders as $order)
            {

                $orderIndex = $order['column'] - ($this->numbering && ! $this->returnAsObject  ? 1 : 0);

                if( $orderIndex >= 0 && $orderIndex < count($orderableColumns) && ! in_array($orderableColumns[$orderIndex], $this->extraColumn))
                    $builder->orderBy($orderableColumns[$orderIndex], $order['dir']);
            }
        }

    }

    private function queryFilterSearch($builder)
    {
        $doQuerying = FALSE;

        $columns = $this->getColumns();

        //individual column search (multi column search)
        foreach (DataTable::request('columns') as $index => $dtColumn) 
        {
            
            if($dtColumn['search']['value'] != '')
            {

                if($this->returnAsObject)
                {
                    if($dtColumn['name'])
                        $column = trim($dtColumn['name']);
                    elseif(in_array($dtColumn['data'], $columns))
                        $column = array_search($dtColumn['data'], $columns);

                    else
                        $column = $dtColumn['data'];
                }
                else
                {
                    $keyColumns = array_keys($columns);
                    $column = $keyColumns[$index];
                }

                

                $builder->like(trim($column), $dtColumn['search']['value']);
                $doQuerying = TRUE;
            }
        }

        //global search
        $dtSearch    = DataTable::request('search');
        $searchValue = $dtSearch['value'];

        if($searchValue != '')
        {

            if($this->searchableColumns !== NULL)
            {
                $searchableColumns = is_array($this->searchableColumns) 
                                   ? $this->searchableColumns 
                                   : explode(",",$this->searchableColumns);
            }
            else
            {

                if($this->returnAsObject)
                {
                    foreach (DataTable::request('columns') as $dtColumn) 
                    {
                        if($dtColumn['name'])
                            $searchableColumns[] = $dtColumn['name'];

                        elseif(in_array($dtColumn['data'], $columns))
                            $searchableColumns[] = array_search($dtColumn['data'], $columns);

                        else
                            $searchableColumns[] = $dtColumn['data'];

                    }
                }
                else
                {
                    foreach ($columns as $column => $alias) 
                        $searchableColumns[] = $column;
                }

                if(! empty($this->addedSearchableColumns))
                    $searchableColumns = array_merge($searchableColumns, $this->addedSearchableColumns);
            }

            if(! empty($searchableColumns))
            {
                $doQuerying = TRUE;
                $dtColumns  = DataTable::request('columns');
                
                $builder->groupStart(); 
                foreach ($searchableColumns as $index => $column)
                {
                    if($column != '' 
                        && ! in_array($column, $this->extraColumn)
                        && ($this->searchableColumns !== NULL 
                            || $dtColumns[$index]['searchable'] === 'true')) 
                    {
                        $builder->orLike(trim($column), $searchValue);
                    }
                }
                $builder->groupEnd();
            }

        }

        if($this->filterFunction !== NULL)
        {
            $testBuilder = clone $builder;

            $callback = $this->filterFunction;
            $callback($builder, DataTable::request());

            if($testBuilder != $builder)
                $doQuerying = TRUE;
        }

        return $doQuerying;
    }

    private function queryResult()
    {
        $builder = clone $this->builder;
        $this->queryOrder($builder);

        if(DataTable::request('length') != -1) 
            $builder->limit(DataTable::request('length'), DataTable::request('start'));

        $this->queryFilterSearch($builder);

        return $builder->get()->getResult();
    }

    /* End Querying */

    private function getBaseSQL()
    {
        if($this->baseSQL === NULL)
            $this->baseSQL = $this->builder->getCompiledSelect(false);

        return $this->baseSQL;
    }

    private function getBaseSQLParsed()
    {   
        if($this->baseSQLParsed === NULL)
        {
            $baseSQL = $this->getBaseSQL();
            $parser = new PHPSQLParser();

            $this->baseSQLParsed = $parser->parse($baseSQL);
        }
        
        return $this->baseSQLParsed;
    }

    private function getColumns() //for query or for result
    {

        if($this->columns !== NULL)
            return $this->columns;

        $columns   = [];

        $builder  = $this->builder;
        $QBSelect = Helper::getObjectPropertyValue($builder, 'QBSelect');
        $QBFrom   = Helper::getObjectPropertyValue($builder, 'QBFrom');
        $QBJoin   = Helper::getObjectPropertyValue($builder, 'QBJoin');


        if( ! empty($QBSelect) )
        {
            
            $sqlParsed  = $this->getBaseSQLParsed();

            foreach ($sqlParsed['SELECT'] as $index => $select) 
            {

                // if select column
                if ($select['expr_type'] == 'colref')
                {
                    //if have select all (*) query
                    if(strpos($select['base_expr'], '*') !== FALSE)
                    {
                        $fieldData = $this->getTableField($select['no_quotes']['parts'][0]);
                        foreach ($fieldData as $field)
                        {
                            if( ! in_array($field->name, $this->columnsRemoved))
                                $columns[$table.'.'.$field->name] = $field->name;
                        }

                    }
                    else
                    {

                        $fieldName = (! empty($select['alias']) ? end($select['alias']['no_quotes']['parts']) : end($select['no_quotes']['parts']) );

                        if( ! in_array($fieldName, $this->columnsRemoved))
                            $columns[$select['base_expr']] = $fieldName;
                    }

                }
                else
                {
                    $column = $QBSelect[$index];
                    
                    if(! empty($select['alias']) 
                        && substr($column, -1*(strlen($select['alias']['base_expr']))) == $select['alias']['base_expr'])
                    {
                        $column = substr($column, 0,-1*(strlen($select['alias']['base_expr'])));
                        $alias = end($select['alias']['no_quotes']['parts']);
                    }else
                        $alias = $column;

                    $columns[$column] = $alias;
                }
                
            }

        }
        else
        {

            foreach ($QBFrom as $table) 
            {
                $fieldData = $this->getTableField($table);
                foreach ($fieldData as $field)
                {
                    if( ! in_array($field->name, $this->columnsRemoved))
                        $columns[$table.'.'.$field->name] = $field->name;
                } 
            }

            foreach ($QBJoin as $table) 
            {
                $fieldData = $this->getTableField($table);
                foreach ($fieldData as $field)
                {
                    if( ! in_array($field->name, $this->columnsRemoved))
                        $columns[$table.'.'.$field->name] = $field->name;
                }
            }
            
        }
        

        foreach ($this->columnsAdded as $column) 
        {
            $columns[$column['name']] = $column['name'];
        }

        $this->columns = $columns;
        return $columns;
    }

    private function getTableField($table)
    {
        if( ! array_key_exists($table, $this->tableField))
        {
            $db = \Config\Database::connect();
            $this->tableField[$table] = $db->getFieldData($table);
        }
        return $this->tableField[$table];

    }
  

}   // End of DataTableQuery Library Class.
