<?php
/**
 * FILE_NAME : CreateThumb.php   FILE_PATH : lv\attach\thumb\CreateTumb
 * 生成缩略图
 *
 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
 * @author Levi
 * @package	lv.attach.thumb.CreateThumb
 * @subpackage
 * @version 2013-06-27
 */
namespace lv\attach\thumb
{
	use Exception;
	use lv\attach\ResizeImg;
	use lv\file\Path;
		
	class CreateThumb 
	{
		private $_init = '';
		private $_name = '';
		private $_thumb = '';
		private $_layout = '';
		
		public function __construct($init, $path, $layout)
		{
			try 
			{
				$this->_init = new Path($path.'.'.$init, $layout);
				
				$this->_name = (String)$init;
				$this->_layout = (String)$layout;
			}
			catch (Exception $e)
			{
				throw new Exception('没有找到生成缩略图的原始图片。');
			}
		}
		
		public function exec($target, $w, $h)
		{
			if ($w <= 0 || $h <= 0)
			{
				throw new Exception('生成的缩略图宽度和高度必须大于0');
			}
			
			$name = sprintf('/%s_%dx%d', $this->_name, $w, $h);
			$thumb = (new Path($target.$name, $this->_layout))->create()->copy($this->_init)->get();
			new ResizeImg($thumb, $w, $h);
		}
		
		public function url()
		{
			
		}
		
		public function path()
		{
			return (String)$this->_thumb;
		}
	}
}