<?php
namespace lv\filter\lib
{
	use Closure;
	use lv\filter\Check;
	
	trait Arr
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
		
		/**
		 * 遍历数组
		 * @param \Closure $func
		 * @return \lv\filter\lib\Arr
		 */
		public function each(Closure $func)
		{
			foreach ($this->get(4) as $key => $val)
			{
				$check = (new Check())->setVal($val, $key);
				$action = Closure::bind($func, $check);
				$action();
			}
			
			return $this;
		}
		
		/**
		 * 替换数组中的键名，提供的参数为一个数组，参数的键名为数组中的旧键名，参数的键值为数组新键名
		 * @param array $arg
		 * @return \lv\filter\lib\Arr
		 */
		public function replace_key(Array $arg) 
		{
			$data = $this->get();
			foreach ($arg as $key => $val) 
			{
				if (isset($data[$key])) 
				{
					$data[$val] = $data[$key];
					unset($data[$key]);
				}
			}
			
			return $this->upVal($data);
		}
		
		/**
		 * 合并数组，为数组设置默认对象
		 * @param array $default
		 * @return \lv\filter\lib\Arr
		 */
		public function extend(Array $default) 
		{
			return $this->upVal(array_merge($default, $this->get()));
		}
		
		/**
		 * 执行一次
		 * @param mixed $key
		 * @param Closure $func
		 */
		public function once($key, Closure $func = NULL)
		{
			$data = $this->get();
			if (isset($data[$key]))
			{
				if ($func) 
				{
					$check = (new Check())->setVal($data[$key], $key);
					$action = Closure::bind($func, $check);
					$action();
				}
			}
			else 
			{
				$this->tarp('当前数据中不存在要选择的信息', 301);
			}
		}
	}
}