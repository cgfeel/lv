<?php
namespace lv\url
{
	use lv\request\Header;
	/**
	 * URL操作：获取、跳转、设置、清除
	 *
	 * @namespace lv\url
	 * @version 2013-04-29
	 * @author Levi <levi@cgfeel.com>
	 * @name URL
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	lv\url\URL
	 * @subpackage 
	 */
	class URL 
	{
		protected $url = '';
		private $query = array();
		
		public function __construct(Array $set = array(), $clear = FALSE)
		{
			$this->init()->set($set, $clear);
		}
		
		public function __toString()
		{
			return $this->get();
		}
		
		public function init()
		{
			// $_SERVER['PHP_SELF'];
// 			$this->url = \data\__URL();

			$this->url = $this->_url();
			return $this;
		}
		
		/**
		 * 设置URL请求参数
		 * @param array $args
		 * @param boolean $clear	如果为真则清除当前URL中所有请求
		 * @return \lv\url\URL
		 */
		public function set(Array $args, $clear = FALSE)
		{
			$map = array();
			!$clear && isset($_SERVER['QUERY_STRING']) && parse_str($_SERVER['QUERY_STRING'], $map);

			$this->query = $clear ? $args : array_merge($map, $args);
			return $this;
		}
		
		/**
		 * 去除URL中所有的请求
		 * @return \lv\url\URL
		 */
		public function clear()
		{
			$this->query = array();
			return $this;
		}
		
		/**
		 * 获取当前URL
		 * @return \lv\url\URL
		 */
		public function same()
		{
			$this->set(array());
			return $this;
		}
		
		/**
		 * 返回URL地址
		 * @return string
		 */
		public function get($code = TRUE)
		{
			$query = empty($this->query) ? '' : '?'.http_build_query($this->query);
			return $code ? urldecode($this->url.$query) : $this->url.$query;
		}
		
		/**
		 * 返回请求URI
		 * @return string
		 */
		public function query()
		{
			return $this->query;
		}
		
		/**
		 * 页面跳转
		 */
		public function go() 
		{
			(new Header())->page(302, $this->get());
			exit;
		}
		
		private function _url() 
		{
			$https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0;
			return
				($https ? 'https://' : 'http://').
				(!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
				(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'].
						($https && $_SERVER['SERVER_PORT'] === 443 || $_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT'])
				)).explode('?', $_SERVER['REQUEST_URI'])[0];
		}
	}
}