<?php
namespace lv\attach
{
	/**
	 * FILE_NAME : ResizeImg.php   FILE_PATH : lv/attach/
	 * 改变图片大小
	 *
	 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
	 * @author Levi
	 * @package	lv.attach.ResizeImg
	 * @subpackage
	 * @version 2013-02-10
	 */
	class ResizeImg
	{	
		public function __construct($img, $ow, $oh, $constrain=TRUE)
		{
			$i = 0;
			while ($i < 100)
			{
				$im = $this->_loadImg($img);
				if (!empty($im)) break;
			}
			if (empty($im)) throw new \Exception('图片更改尺寸失败：'.$img);
			
			$_w = imagesx($im);
			$_h = imagesy($im);
			if ($_w <= $ow && $_h <= $oh) return ;
			if($constrain)
			{
				if($_w >= $_h)
				{
					$setW = ($oh / $_h) * $_w;
					$setH = $oh;
					$setX = ($setW - $ow) / 2;
					$setY = 0;
				}
				else
				{
					$setW = $ow;
					$setH = ($ow / $_w) * $_h;
					$setX = 0;
					$setY = ($setH - $oh) / 2;
				}
			}
			
			if(function_exists('imagecreatetruecolor'))
			{
				$ne = imagecreatetruecolor($ow, $oh);
			}
			else $ne = imagecreate($ow,$oh);
			
			$white = ImageColorAllocate($ne, 255, 255, 255);
			
			//描边
			//imagerectangle($ne, 10, 10, 190, 190, $white);
			
			imagefilledrectangle($ne, 0, 0, $ow, $oh, $white);
			
			if(function_exists('imagecopyresampled'))
			{
				imagecopyresampled($ne, $im, 0, 0, $setX, $setY, $setW, $setH, $_w, $_h);
			}
			else imagecopyresized($ne, $im, 0, 0, $setX, $setY, $setW, $setH, $_w, $_h);
			
			$stat = getimagesize($img);
			switch ($stat[2])
			{
				case 1:
					imagegif($ne, $img);
					break;
				case 2:
					imagejpeg($ne, $img, 100);
					break;
				case 3:
					imagepng($ne, $img, 9);
				default:
					return FALSE;
			}
			
			imagedestroy($im);
			return TRUE;
		}
		
		private function _loadImg($img)
		{
			$stat = getimagesize($img);
			switch($stat[2])
			{
				case 1:
					$im = @imagecreatefromgif($img);
					break;
				case 2:
					$im = @imagecreatefromjpeg($img);
					break;
				case 3:
					$im = @imagecreatefrompng($img);
					break;
				default:
					$im = '';
			}
			
			return $im;
		}
	}
}