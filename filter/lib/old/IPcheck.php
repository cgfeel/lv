<?php
namespace lv\filter\lib
{
	use lv\filter\Check;
	
	/**
	 * 检查、获取IP
	 *
	 * @namespace lv\filter\lib
	 * @version 2013-08-14
	 * @author Levi <levi@cgfeel.com>
	 * @name IPcheck
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	lv\filter\lib\IPcheck
	 * @subpackage lv\filter\Check
	 */
	class IPcheck extends Check
	{
		const KEY = 'IP';
		
		/**
		 * 设置IP
		 * @param string $ip
		 */
		public function __construct($ip = '')
		{
			parent::point(INPUT_SERVER);
			!empty($ip) && $this->_check($ip) && $this->setVal(self::KEY, $ip);
		}
		
		/**
		 * 获取关键字
		 * @return string
		 */
		public function key()
		{
			return self::KEY;
		}
		
		/**
		 * 转换IP为数字型
		 * @param string $ip
		 * @return number
		 */
		public function long($ip = '')
		{
			$ip = empty($ip) ? $this->get() : ($this->_check($ip) ? $ip : $this->Tarp('IP地址不正确', 1001));
			return bindec(decbin(ip2long($ip)));
		}
		
		/**
		 * 转化数字为IP
		 * @param string $num
		 * @return Ambigous <string, unknown>
		 */
		public function str($num = -1)
		{
			return $num < 0 ? $this->get() : $this->_filter(long2ip($num));
		}
		
		/**
		 * (non-PHPdoc)
		 * @see \lv\filter\Check::get()
		 */
		public function get()
		{
			if (!empty(parent::get())) 
			{
				return $this->_filter(parent::get());
			}
			
			$cath = $this->_catchIP('HTTP_X_FORWARDED_FOR') || 
					$this->_catchIP('HTTP_CLIENT_IP') || 
					$this->_catchIP('REMOTE_ADDR');
			
			if (!$cath)
			{
				parent::__construct(INPUT_ENV);
				$this->_catchIP('HTTP_X_FORWARDED_FOR') ||
				$this->_catchIP('HTTP_CLIENT_IP') ||
				$this->_catchIP('REMOTE_ADDR') ||
				$this->tarp('系统无法识别IP地址', 1002);
			}
			
			return $this->_filter(parent::get());
		}
		
		private function _catchIP($key)
		{
			try
			{
				$this->set($key);
				return TRUE;
			}
			catch (\lv\filter\Tarp $e)
			{
				return FALSE;
			}
		}
		
		private function _filter($val)
		{
			return ($val == '::1' || $val == 'localhost') ? '127.0.0.1' : $val;
		}
		
		private function _check($ip)
		{
			return filter_var($ip, FILTER_VALIDATE_IP);
		}
	}
}


?>