<?php
namespace lv\file\img
{
	use lv\file\save\Attach;
	class GD extends Attach
	{
		private $_info = [];
		
		public function info()
		{
			empty($this->_info) && $info = getimagesize($this->get());
			return $this->_info;
		}
		
		public function width()
		{
			return $this->info()[0];
		}
		
		public function height()
		{
			return $this->info()[1];
		}
		
		public function getExtension()
		{
			switch ($this->info()[2])
			{
				case 1: return 'gif';
				case 2: return 'jpg';
				case 3: return 'png';
				default: throw new \Exception('系统无法识别的图片格式');
			}
		}
	}
}