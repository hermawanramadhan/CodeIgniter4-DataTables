<?php 
namespace Hermawan\DataTables;


class DataTableQuery
{

    private $builder;

    /**
     * DataTableColumnDefs object.
     *
     * @var \Hermawan\DataTables\DataTableColumnDefs
     */
    private $columnDefs;

    private $filter;

    /**
     * postQuery
     *
     * @var Closure
     */
    private $postQuery;

    private $countResult;

    private $doQueryFilter = FALSE;

    private const DT_ROW_ID = 'DT_RowId';


    public function __construct($builder)
    {
        $this->builder = $builder;
    }


    /**
     * columnDefs
     *
     * @param \Hermawan\DataTables\DataTableColumnDefs $columnDefs
     */
    public function setColumnDefs($columnDefs)
    {
        $this->columnDefs = $columnDefs;
        return $this;
    }


    /**
     * postQuery
     *
     * @param Closure $postQuery
     */
    public function setPostQuery($postQuery)
    {
        $this->postQuery = $postQuery;
    }


    public function filter($filter)
    {
        $this->filter = $filter;
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
        $builder = clone $this->builder;

        $this->queryFilterSearch($builder);

        $this->countResult = ($this->countResult !== NULL && ! $this->doQueryFilter) ? $this->countResult : $builder->countAllResults();

        return $this->countResult;
    }


    public function getDataResult()
    {
        $queryResult = $this->queryResult();
        $result      = [];

        foreach ($queryResult as $row)
        {
            //escaping all
            foreach($row as $key => $val)
                $row->$key = esc($val);

            $data    = [];
            $columns = $this->columnDefs->getColumns();

            foreach ($columns as $column)
            {
                switch ($column->type) {
                    case 'numbering':
                        $value = $this->columnDefs->getNumbering();
                        break;

                    case 'add':
                        $callback = $column->callback;
                        $value    = $callback($row);
                        break;

                    case 'edit':
                        $callback = $column->callback;
                        $value    = $callback($row);
                        break;

                    case 'format':
                        $callback = $column->callback;
                        $value    = $callback($row->{$column->alias});
                        break;

                    default:
                        $value = $row->{$column->alias};
                        break;
                }

                if($this->columnDefs->returnAsObject){
                    $data[$column->alias] = $value;

                    if($column->type === 'primary')
                        $data[self::DT_ROW_ID] = $value;
                }
                else
                    $data[] = $value;
            }

            $result[] = $data;
        }

        return $result;
    }

    /* End Generating result */


    public function insertData($data)
    {
        $builder = clone $this->builder;

        $columns = $this->columnDefs->getColumns();

        $result = [];

        foreach ($data as $rowData) {
            $row = [];
            foreach ($columns as $column)
            {
                if(isset($rowData[$column->alias])){
                    $builder->set($column->key, $rowData[$column->alias]);
                    $row[$column->alias] = esc($rowData[$column->alias]);

                    if($column->type === 'primary')
                        $row[self::DT_ROW_ID] = esc($rowData[$column->alias]);
                }
            }

            if($builder->insert()){
                if(!isset($row[self::DT_ROW_ID]))
                    $row[self::DT_ROW_ID] = $builder->db()->insertID();

                $result[] = $row;

                if($this->postQuery !== NULL)
                {
                    $callback = $this->postQuery;
                    $callback($builder, $row);
                }
            }
        }

        return $result;
    }


    public function updateData($data, $primaryKey)
    {
        $builder = clone $this->builder;

        $result = [];

        foreach ($data as $key => $rowData) {
            $row = [];
            foreach ($rowData as $columnKey => $value)
            {
                $builder->set($columnKey, $value);
                $row[$columnKey] = esc($value);
            }

            $builder->where($primaryKey, $key);

            if($builder->update()){
                $result[] = $row;

                if($this->postQuery !== NULL)
                {
                    $callback = $this->postQuery;
                    $callback($builder, $row);
                }
            }
        }

        return $result;
    }


    public function deleteData($data, $primaryKey)
    {
        $builder = clone $this->builder;

        $columns = $this->columnDefs->getColumns();

        $result = [];

        foreach ($data as $key => $rowData) {
            $row = [];
            foreach ($columns as $column)
            {
                if(isset($rowData[$column->alias]))
                    $row[$column->alias] = esc($rowData[$column->alias]);

                if($column->type === 'primary')
                    $row[self::DT_ROW_ID] = esc($rowData[$column->alias]);
            }

            $builder->where($primaryKey, $key);

            if($builder->delete()){
                $result[] = $row;

                if($this->postQuery !== NULL)
                {
                    $callback = $this->postQuery;
                    $callback($builder, $row);
                }
            }
        }

        return $result;
    }


    /* Querying */

    private function queryOrder($builder)
    {

        $orderables         = $this->columnDefs->getOrderables();
        $oderColumnRequests = Request::get('order');

        if($oderColumnRequests)
        {
            foreach ($oderColumnRequests as $request)
            {
                $dir    = ($request['dir'] == 'desc') ? 'desc' : 'asc';
                $column = $orderables[$request['column']] ?? NULL;

                if( $column !== NULL)
                    $builder->orderBy($column, $dir);
            }
        }

    }


    private function queryFilterSearch($builder)
    {
        //individual column search (multi column search)
        $columnRequests = Request::get('columns');
        foreach ($columnRequests as $index => $request)
        {

            if($request['search']['value'] != '')
            {
                $column              = $this->columnDefs->getSearchRequest($index, $request);
                $this->doQueryFilter = TRUE;

                $builder->like($column, $request['search']['value']);
            }
        }

        //global search
        $searchRequest = Request::get('search');

        if($searchRequest['value'] != '')
        {
            $searchable = $this->columnDefs->getSearchable();

            if(! empty($searchable))
            {
                $this->doQueryFilter = TRUE;

                $builder->groupStart();
                foreach ($searchable as $column)
                    $builder->orLike(trim($column), $searchRequest['value']);

                $builder->groupEnd();
            }
        }

        $this->queryFilter($builder);
    }


    private function queryFilter($builder)
    {
        if($this->filter !== NULL)
        {
            $testBuilder = clone $builder;

            $callback = $this->filter;
            $callback($builder, Request::get());

            if($testBuilder != $builder)
                $this->doQueryFilter = TRUE;
        }
    }

    private function queryResult()
    {
        $builder = clone $this->builder;

        $this->queryOrder($builder);

        if(Request::get('length') != -1)
            $builder->limit(Request::get('length'), Request::get('start'));

        $this->queryFilterSearch($builder);

        if($this->postQuery !== NULL)
        {
            $callback = $this->postQuery;
            $callback($builder);
        }

        return $builder->get()->getResult();
    }

    /* End Querying */

}   // End of DataTableQuery Class.