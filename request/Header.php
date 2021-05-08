<?php
namespace lv\request
{
	/**
	 * Header操作
	 *
	 * @namespace lv\request\Header
	 * @version 2013-12-29
	 * @author Levi <levi@cgfeel.com>
	 * @name URL
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	lv\url\URL
	 * @subpackage 
	 */
	class Header 
	{
		public static $char = 'utf-8';
		private $_header = array();
		
		public function __construct(Array $opt = array())
		{
			if (isset($opt['char'])) 
			{
				self::$char = $opt['char'];
				unset($opt['char']);
			}
			
			$this->_header = array('Content-Type' => 'text/html; charset='.self::$char);
			$opt && $this->opt($opt);
		}
		
		public function set($key, $val)
		{
			if (empty($key)) 
			{
				$this->_header[] = $val;
			}
			else
			{
				$this->_header[$key] = $val;
			}
			
			return $this;
		}
		
		public function opt(Array $header)
		{
			$this->_header = array_merge($this->_header + $header);
			return $this;
		}
		
		public function get()
		{
			return $this->_header;
		}
		
		public function noCache()
		{
			$https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0;
			$this->opt(array(
				'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
				'Last-Modified' => gmdate('D, d M Y H:i:s').' GMT',
				'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
				'Pragma' => $https ? 'public' : 'no-cache',
				'Expires' => 0
			));
			
			return $this;
		}
		
		public function init()
		{
			foreach(array_filter($this->_header) as $key => $val)
			{
				if (is_array($val)) 
				{
					$val[0] = sprintf('%s: %s', $key, $val[0]);
					call_user_func_array('header', $val);
				}
				else 
				{
					is_numeric($key) ? header($val) : header(sprintf('%s: %s', $key, $val));
				}
			}
			
			$this->_header = array();
			return $this;
		}
		
		public function page($num)
		{
			switch ($num)
			{
				case 206: return $this->set('', 'HTTP/1.1 206 Partial Content')->init();
				case 302: 
				case 301: 
					$opt = func_get_args();
					if (!isset($opt[1])) 
					{
						return $this;
					}
					
					$set = array(301 => 'HTTP/1.0 301 Moved Permanently', 302 => 'HTTP/1.1 302 Found');
					return $this->opt(array('' => $set[$num], 'Location' => $opt[1]))->init();
				
				case 400; return $this->set('', 'HTTP/1.1 400 Invalid Request')->init();
				case 403: return $this->set('', 'HTTP/1.1 403 Forbidden')->init();
				case 404: return $this->set('', 'HTTP/1.1 404 Not Found')->init();
				case 405: return $this->set('', 'HTTP/1.1 405 Method Not Allowed')->init();
				case 500: return $this->set('', 'HTTP/1.1 500 Internal Server Error')->init();
			}
		}
	}
}