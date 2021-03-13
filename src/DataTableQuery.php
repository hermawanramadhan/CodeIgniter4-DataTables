<?php 
namespace Hermawan\DataTables;


class DataTableQuery
{

    private $builder;
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


    public function __construct($builder)
    {
        $this->builder = $builder;
    }

    /* Modified column */

    public function addNumbering($column)
    {
        $this->numbering = true;
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
                    if(in_array($key, $columns) && ! in_array($key, $this->columnsRemoved))
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

            $orderableColumns = $this->getColumns();

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

        $dtSearch    = DataTable::request('search');
        $searchValue = $dtSearch['value'];

        if($searchValue)
        {
            $doQuerying = TRUE;

            if($this->searchableColumns !== NULL)
            {
                $searchableColumns = is_array($this->searchableColumns) 
                                   ? $this->searchableColumns 
                                   : explode(",",$this->searchableColumns);
            }
            else
                $searchableColumns = $this->getColumns();

            if(! empty($searchableColumns))
            {
                $dtColumns = DataTable::request('columns');
                $builder->groupStart(); 
                foreach ($searchableColumns as $index => $column)
                {
                    if(! in_array($column, $this->extraColumn) && $dtColumns[$index]['searchable'] === 'true')
                        $builder->orLike(trim($column), $searchValue);
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

    private function getColumns() //for query or for result
    {

        if($this->columns !== NULL)
            return $this->columns;

        $columns   = [];

        if($this->returnAsObject)
        {
            foreach (DataTable::request('columns') as $column) 
                $columns[] = $column['name'] ? $column['name'] : $column['data'];

            $this->columns = $columns;
            return $columns;
        }

        
        $builder  = $this->builder;
        $QBSelect = Helper::getObjectPropertyValue($builder, 'QBSelect');
        $QBFrom   = Helper::getObjectPropertyValue($builder, 'QBFrom');
        $QBJoin   = Helper::getObjectPropertyValue($builder, 'QBJoin');
        
        if( ! empty($QBSelect))
        {

            foreach ($QBSelect as $select) 
            {
                //if subquery or something like if concat not yet support

                if (strpos($select, '*') !== false) 
                {
                    $table = str_replace("*", "", $select);
                    $table = str_replace(".", "", $table);
                    $table = str_replace(" ", "", $table);

                    $fieldData = $this->getTableField($table);

                    foreach ($fieldData as $field)
                    {
                        if( ! in_array($field->name, $this->columnsRemoved))
                            $columns[] = $table.'.'.$field->name;
                    }

                }
                elseif (strpos($select, ' as ') !== false)
                {
                    list($select, $alias) = explode(' as ', $select);

                    if( ! in_array($field->name, $this->columnsRemoved))
                        $columns[] = $select;
                } 
                elseif (strpos($select, ' ') !== false)
                {
                    list($select, $alias) = explode(" ", $select);

                    if( ! in_array($field->name, $this->columnsRemoved))
                        $columns[] = $select;
                }
                else
                {
                    if( ! in_array($select, $this->columnsRemoved))
                        $columns[] = $select;
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
                        $columns[] = $table.'.'.$field->name;
                } 
            }

            foreach ($QBJoin as $table) 
            {
                $fieldData = $this->getTableField($table);
                foreach ($fieldData as $field)
                {
                    if( ! in_array($field->name, $this->columnsRemoved))
                        $columns[] = $table.'.'.$field->name;
                }
            }
            
        }
        

        foreach ($this->columnsAdded as $column) 
        {
            switch ($column['position']) {
                case 'first':
                    array_unshift($columns, $column['name']);
                    break;
                case 'last':
                    $columns[] = $column['name'];
                    break;
                default:
                    array_splice( $columns, $column['position'], 0, [$column['name']]);
                    break;
            }

        }

        $columns = array_values($columns);

        $this->columns = $columns;
        return $columns;
    }

    private function getTableField($table)
    {
        if(array_key_exists($table, $this->tableField))
            return $this->tableField[$table];
        
        $db = \Config\Database::connect();
        $this->tableField[$table] = $db->getFieldData($table);
        
        return $this->tableField[$table];

    }
  

}   // End of DataTableQuery Library Class.
