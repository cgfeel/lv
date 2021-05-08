<?php
/**
 * FILE_NAME : Thumb.php   FILE_PATH : lv\attach\Thumb
 * 生成缩略图
 *
 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
 * @author Levi
 * @package	lv.attach.Thumb
 * @subpackage
 * @version 2013-06-27
 */

namespace lv\attach
{
	use Exception;
	use lv\file\Path;
	
	class Thumb
	{
		private $_thumb = '';
		private $_type = '';
		
		public function __construct($map, $type)
		{
			try 
			{
				$this->_type = '.'.$type;
				$map = $this->_package($map);
			}
			catch (Exception $e)
			{
				throw new Exception('系统没有获取到图片地址:'.$map);
			}
			
			$dir = explode('.', $map);
			$dir[1] .= '.thumb';
			
			$this->_thumb = (new Path(implode('.', $dir), $this->_type))->create()->copy(new Path($map, $this->_type))->get();
		}
		
		public function action($w, $h)
		{
			$w = (Int)$w;
			$h = (Int)$h;
			if ($w > 0 && $h > 0)
			{
				new ResizeImg($this->_thumb, $w, $h);
			}
		}
		
		public function geter()
		{
			return (String)$this->_thumb;
		}
		
		private function _package($map)
		{
			if (strstr($map, '-'))
			{
				try
				{
					$map = 'attach.'.explode('-', $map)[1];
					if ((new Path($map, $this->_type))->get()) 
					{
						return $map;
					}
				}
				catch(Exception $e)
				{
					throw new Exception('已存在缩略图。');
				}
			}
			else 
			{
				return 'attach.'.$map;
			}
		}
	}
}