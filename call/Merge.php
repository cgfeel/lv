<?php
namespace lv\call
{
	/**
	 * FILE_NAME : Merge.php   FILE_PATH : lv/call/
	 * 合并多个对象
	 * 
	 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
	 * @author Levi
	 * @package	lv.call.Merge
	 * @subpackage
	 * @version 2013-02-08
	 */
	class Merge
	{
		private $_objs = array();
		
		public function __construct()
		{
			$this->_objs = func_get_args();
		}
		
		public function __set($a, $b)
		{
			$this->_objs[$a] = $b;
		}
		
		public function __get($attr)
		{
			foreach ($this->_objs as $obj)
			{
				if (property_exists($obj, $attr))
				{
					return $obj->$attr;
				}
			}
		}
		
		public function __call($fn, $args)
		{
			foreach ($this->_objs as $obj)
			{
				if (method_exists($obj, $fn))
				{
					return call_user_func_array(array($obj, $fn), $args);
				}
			}
		}
	}
}