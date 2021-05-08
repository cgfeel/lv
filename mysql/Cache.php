<?php
namespace lv\mysql
{
	class Cache extends \Redis
	{
		private $_key = '';
		
		public function __construct()
		{
			parent::__construct();
			$this->connect('127.0.0.1', 6379);
		}
		
		public function set($key, $val)
		{
			parent::set($this->_key($key), $val);
			return $this;
		}
		
		public function get($key = '')
		{
			return parent::get($this->_key($key));
		}
		
		public function ttl($key)
		{
			return parent::ttl($this->_key($key));
		}
		
		public function expire($time, $key = '')
		{
			parent::expire($this->_key($key), $time);
			return $this;
		}
		
		public function del($key = '')
		{
			parent::del($this->_key($key));
			return $this;
		}
		
		private function _key($str)
		{
			strlen($str) && $this->_key = md5($str);
			return $this->_key;
		}
	}
}