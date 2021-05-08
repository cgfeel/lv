<?php
namespace lv\filter\lib
{
	use lv\file\MiniInfo;
	use lv\filter\Check;
		
	class MiniCheck extends Check
	{
		CONST EXT = 'MiniCheck.ext';
		
		public function __construct(MiniInfo $file = NULL, $key = '')
		{
			$file && $this->setVal($file);
		}
		
		public function get()
		{
			!(($mini = parent::get()) instanceof MiniInfo) && $this->tarp('当前对象不是mini类型', 1003);
			return $mini;
		}
		
		/**
		 * (non-PHPdoc)
		 * @see \lv\filter\Check::setVal()
		 */
		public function setVal($val, $key = '')
		{
			if (!($val instanceof MiniInfo)) 
			{
				throw new \Exception('数据格式不正确');
			}
			
			empty($key) && $key = 'mini_'.time();
			return parent::setVal($val, $key);
		}
		
		/**
		 * 检查文件后缀，返回一个数组
		 * @return multitype:
		 */
		public function getType()
		{
			return array_keys(MiniInfo::$mime_types, $this->get()->mini());
		}
		
		/**
		 * 检查当前文件是否为有效格式
		 * @return boolean
		 */
		public function is($key)
		{
			if ($key) 
			{
				$val = isset(MiniInfo::$mime_types[$key]) ? MiniInfo::$mime_types[$key] : '';
				return ($val == $this->get()->mini());
			}
			
			return FALSE;
		}
	}
}