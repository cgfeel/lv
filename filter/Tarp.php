<?php
namespace lv\filter
{
	/**
	 * FILE_NAME : Tarp.php   FILE_PATH : lv/filter/
	 * 捕获器
	 *
	 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
	 * @author Levi
	 * @package	lv.filter.Tarp
	 * @subpackage Exception
	 * @version 2013-02-09
	 */
	class Tarp extends \Exception
	{
		private $_key = '';
		private $_data = '';
		private $_type = 0;
		
		public function __construct($message = '', $key, $code = 1, $previous = NULL)
		{
			parent::__construct($message, $code, $previous);
			$this->_key = (String)$key;
		}
		
		public function set($type, $data)
		{
			$this->_type = $type;
			$this->_data = $data;
			
			return $this;
		}
		
		public function getType()
		{
			return $this->_type;
		}
		
		public function getData()
		{
			return $this->data;
		}
		
		/**
		 * 获取关键字
		 * @return string
		 */
		public function getKey()
		{
			return (String)$this->_key;
		}
	}
}