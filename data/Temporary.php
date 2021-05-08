<?php
namespace lv\data
{
	class Temporary extends \ArrayObject 
	{
		private static $_data = array();
		private $_name = 'tmp_1';
		
		public function __construct($point = '', $data = array())
		{
			$this->name(empty($point) ? $this->_name : $point);
			$data && parent::__construct($data);
		}
		
		public function name($key)
		{
			$this->_name = $key;
			self::$_data[$this->_name] = array();
		}
		
		public function set($key, $val)
		{
// 			self::$_data[$this->_name]
		}
		
		public function get($key = '')
		{
			if (empty($key)) 
			{
				return self::$_data[$this->_name];
			}
			elseif (isset(self::$_data[$this->_name][$key]))
			{
				return self::$_data[$this->_name][$key];
			}
			
			throw new \Exception('没有找到数据');
		}
		
		public function open($type = 1)
		{
			
		}
		
		public function save()
		{
			
		}
	}
}