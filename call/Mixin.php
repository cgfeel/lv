<?php
namespace lv\call
{
	class Mixin
	{
		private static $mixins = array();
		
		public static function implement($target)
		{
			$class = get_called_class();
			if (!isset(self::$mixins[$class]))
			{
				self::$mixins[$class] = array();
			}
		
			foreach (get_class_methods($target) as $method)
			{
				self::$mixins[$class][$method] = $target;
			}
		}
		
		public function class_name()
		{
			return self::get_class($this);
		}
		
		public function __call($method, $params)
		{
			$params = array_merge(array($this), $params);
			$class = get_called_class();
			
			$mixin = $this->find_mixin($class, $method);
			call_user_func_array($mixin, $params);
		}
		
		private function find_mixin($class, $method)
		{
			while ($class != NULL)
			{
				if (isset(self::$mixins[$class][$method]))
				{
					$target = self::$mixins[$class][$method];
					return array($target, $method);
				}
		
				$class = get_parent_class($class);
			}
		
			throw new \Exception("方法 $method 不存在");
		}
	}
}