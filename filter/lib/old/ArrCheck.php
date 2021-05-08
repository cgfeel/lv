<?php
namespace lv\filter\lib
{
	use lv\filter\Check;
	
	/**
	 * FILE_NAME : ArrCheck.php   FILE_PATH : lv/filter/lib/
	 * 检查数组数据类
	 *
	 * @copyright Copyright (c) 2006-2010 mailTo:levi@cgfeel.com
	 * @author Levi
	 * @package	lv.filter.lib.ArrCheck
	 * @subpackage Check
	 * @version 2013-04-30
	 */
	
	class ArrCheck extends Check 
	{
		/**
		 * 效验数据格式
		 * @throws Tarp
		 * @reutn $this
		 */
		public function isArray()
		{
			!is_array($this->get()) && $this->tarp('当前数据类型不是数组', 300);
			return $this;
		}
		
		public function each($func)
		{
			$data = [];
			foreach ($this->get() as $key => $val)
			{
				$check = (new Check())->setVal($val, $key);
				$action = \Closure::bind($func, $check);
				$data[] = $action();
			}
			
			$this->upVal($data);
		}
		
		public function once($key, $func = NULL)
		{
			$data = $this->get();
			if (isset($data[$key]))
			{
				if ($func instanceof \Closure) 
				{
					$check = (new Check())->setVal($data[$key], $key);
					$action = \Closure::bind($func, $check);
					
					$data[$key] = $action();
					$this->upVal($data);
				}
			}
			else
			{
				$this->tarp('当前数据中不存在要选择的信息', 301);
			}
		}
		
		/**
		 * (non-PHPdoc)
		 * @see Check::inArray()
		 * @return $this
		 */
		public function inArray($data)
		{
			$map = function($arr) use ($this, $data)
			{
				foreach ($arr as $val)
				{
					if (is_array($val))
					{
						$map($val);
						return;
					}
					
					!in_array($val, $data) && $this->tarp('当前数据中不存在要查找的信息', 302);
				}
			};
			
			$map($this->get());
			return $this;
		}
	}
}