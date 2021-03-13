<?php 
namespace Hermawan\DataTables;

use \Config\Services;

class DataTable
{

    /**
     * Make a DataTable instance from builder.
     *  
     * Builder from CodeIgniter Query Builder
     * @param  Builder $builder
     * @return DataTableServerSide
     */
    public static function of($builder)
    {
        return new DataTableServerSide($builder);
    }


    /**
     * Get DataTable Request
     *  
     * @param  String $requestName
     * @return String|Array
     */


    public static function request($requestName = NULL)
    {
    	$request = Services::request();
        if($requestName !== NULL)
    	   return $request->getGetPost($requestName);
        
        return (Object) (($request->getMethod() == 'get') ? $request->getGet() : $request->getPost());

    }
   
}   // End of DataTables Library Class.
