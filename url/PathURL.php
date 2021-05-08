<?php
/**
 * FILE_NAME : PartURL.php   FILE_PATH : lv\url\PathURL
 * 根据服务器路径操作URL
 *
 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
 * @author Levi
 * @package	lv.url.PartURL
 * @subpackage
 * @version 2013-06-27
 */
namespace lv\url
{
	use lv\file\Path;
	
	class PathURL extends URL
	{
		/**
		 * 根据路径获取URL
		 * @param Path $path
		 * @param boolen $get
		 * @return \lv\url\PathURL
		 */
		public function path(Path $path, $arg = [])
		{
			$this->url = str_replace(ROOT, INDEX, $path);
			return $arg ? $this->set($arg, TRUE) : $this->clear();
		}
	}
}