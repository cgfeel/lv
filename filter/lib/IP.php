<?php
namespace lv\filter\lib
{
	trait IP
	{
		const KEY = 'IP';
		
		public function setIP($ip = '')
		{
			if (empty($ip))
			{
				$cath = $this->_catchIP('HTTP_X_FORWARDED_FOR') ||
				$this->_catchIP('HTTP_CLIENT_IP') ||
				$this->_catchIP('REMOTE_ADDR');
					
				if (!$cath)
				{
					$this->point(INPUT_ENV);
					$this->_catchIP('HTTP_X_FORWARDED_FOR') ||
					$this->_catchIP('HTTP_CLIENT_IP') ||
					$this->_catchIP('REMOTE_ADDR') ||
					$this->tarp('系统无法识别IP地址', 1001);
				}
			}
			else 
			{
				$ip = $this->_filter($ip);
				$this->upVal($ip);
			}
			
			return $this;
		}
		
		/**
		 * 转换IP为数字型
		 * @param string $ip
		 * @return number
		 */
		public function long($ip = '')
		{
			$ip && $this->upVal($this->setIP($ip)->get(2));
			$ip = $this->isip()->get(2);
			
			return bindec(decbin(ip2long($ip)));
		}

		/**
		 * 转化数字为IP
		 * @param string $num
		 * @return Ambigous <string, unknown>
		 */
		public function str($num = -1)
		{
			$num >= 0 && $this->upVal($num);
			$ip = long2ip($this->size(0)->get(1));
			
			return $this->setIP($ip)->isip()->get(2);
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
		
		public function isip()
		{
			return $this->validate(5);
		}
		
		private function _filter($val)
		{
			return ($val == '::1' || $val == 'localhost') ? '127.0.0.1' : $val;
		}
	}
}