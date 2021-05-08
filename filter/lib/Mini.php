<?php
namespace lv\filter\lib
{
	// 8-10
	use lv\file\MiniInfo;
	
	trait Mini
	{
		public function setMini(MiniInfo $file)
		{
			return $this->setVal($file);
		}
		
		public function isMini()
		{
			!($this->get() instanceof MiniInfo) && $this->tarp('当前对象不是mini类型', 1001);
			return $this;
		}
		
		/**
		 * 检查文件后缀，返回一个数组
		 * @return multitype:
		 */
		public function getExts()
		{
			return array_keys(MiniInfo::$mime_types, $this->isMini()->get()->mini());
		}
		
		/**
		 * 检查当前文件是否为有效格式
		 * @return boolean
		 */
		public function isExt($key)
		{
			$val = isset(MiniInfo::$mime_types[$key]) ? MiniInfo::$mime_types[$key] : '';
			return ($val == $this->isMini()->get()->mini());
		}
	}
}