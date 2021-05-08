<?php
namespace lv\request
{
	use lv\file\Path;
	
	/**
	 * 通过伪造发起HTTP请求
	 *
	 * @namespace lv\request
	 * @version 2013-07-05
	 * @author Levi <levi@cgfeel.com>
	 * @name Curl
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	lv\request\Curl
	 * @subpackage
	 */
	class HTTP_Proxy extends HTTP
	{
		/**
		 * 伪造IP
		 * @param unknown $str
		 * @return Ambigous <\lv\request\Curl, \lv\request\Curl>
		 */
		public function ip($str)
		{
			return $this->header(array('X-FORWARDED-FOR' => $str, 'CLIENT-IP' => $str));
		}
		
		/**
		 * 伪造来路
		 * @param unknown $url
		 * @return Ambigous <\lv\request\Curl, \lv\request\Curl>
		 */
		public function referer($url)
		{
			return $this->opt(CURLOPT_REFERER, $url);
		}
		
		/**
		 * 设置cookies
		 * @param boolean $set
		 * @return Ambigous <\lv\request\Curl, \lv\request\Curl, \lv\request\HTTP_Proxy>
		 */
		public function cookies($set = TRUE)
		{
			$path = $set ? (new Path('attach.tmp.cookies', '.txt'))->create()->get() : NULL;
			return $this->opt(CURLOPT_COOKIEFILE, $path)->opt(CURLOPT_COOKIEJAR, $path);
		}
	}
}