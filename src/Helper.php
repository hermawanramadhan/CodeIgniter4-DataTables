<?php 
namespace Hermawan\DataTables;

class Helper
{

	public static function getObjectPropertyValue($object, $varName)
	{
		$rp = new \ReflectionProperty($object, $varName);

		if($rp->isPublic())
			return $object->$varName;
		else
		{
			$rp->setAccessible(TRUE);
			return $rp->getValue($object);
		}

	}
   
}   // End of Helper Library Class.
