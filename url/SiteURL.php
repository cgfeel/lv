<?php
/**
 * FILE_NAME : SiteURL.php   FILE_PATH : lv\url\
 * 根据URL地址进行操作
 *
 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
 * @author Levi
 * @package	lv.url.SiteURL
 * @subpackage
 * @version 2013-05-31
 */

namespace lv\url
{
	class SiteURL extends URL
	{
		public function __construct($url)
		{
			$url = parse_url($url);
			$this->url = sprintf('%s://%s', $url['scheme'], $url['host'].$url['path']);
			if (isset($url['query']))
			{
				parse_str($url['query'], $map);
				$this->set($map, TRUE);
			}
		}
		
		/**
		 * (non-PHPdoc)
		 * @see \lv\url\URL::set()
		 */
		public function set(Array $args, $clear = FALSE)
		{
			return parent::set($clear ? $args : array_merge($this->query(), $args), TRUE);
		}
	}
}