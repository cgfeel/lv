<?php
/**
 * FILE_NAME : indexArr.php   FILE_PATH : lv/data/
 * 在多维数组中根据键名快速查询其父键以及父键值
 *
 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
 * @author Levi
 * @package	lv.data.IndexArr
 * @subpackage
 * @version 2011-04-29
 */

class IndexArr
{
	private $_arr = array();
	
	public function __construct($data)
	{
		$this->_createIndex($data);
	}
	
	public function printArr()
	{
		return (Array)$this->_arr;
	}
	
	public function search($key)
	{
		return isset($this->_arr[$key]) ? $this->_arr[$key]['parent'] : NULL;
	}
	
	public function parentValue($key)
	{
		return isset($this->_arr[$key]) ? $this->_arr[$key]['data'] : NULL;
	}
	
	private function _createIndex($data, $parent = NULL)
	{
		foreach ($data as $key => $value)
		{
			$this->_checkIndex($key, $parent, $data);
			if (is_array($value))
			{
				$this->_createIndex($value, $key);
			}
		}
	}
	
	private function _checkIndex($key, $parent, $data)
	{
		$data = $parent && isset($data[$parent]) ? $data[$parent] : $data;
		!isset($this->_arr[$key]) && $this->_arr[$key] = array('data' => $data, 'parent' => '');
		
		$index = &$this->_arr[$key]['parent'];
		if (!empty($index))
		{
			if (is_array($index))
			{
				array_push($index, $parent);
			}
			else $index = array($index, $parent);
		}
		else $index = $parent;
	}
}
?>