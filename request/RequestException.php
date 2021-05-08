<?php
namespace lv\request
{
	class RequestException extends \Exception
	{
		private $_opt = [];
		private $_header = [];
		
		public function set(Array $opt, Array $header)
		{
			$this->_opt = $opt;
			$this->_header = $header;
			
			return $this;
		}
		
		public function opt()
		{
			return $this->_opt;
		}
		
		public function header()
		{
			return $this->_header;
		}
	}
}