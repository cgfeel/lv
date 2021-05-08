<?php
namespace lv\load
{
	use lv\file\Path;
	class L
	{
		private static $_throw = false;
		private static $_include = TRUE;
		private static $_l = NULL;
		
		protected function __construct()
		{
			L::autoload();
		}
		
		protected static function _checkLoad($classes)
		{
			return (class_exists($classes, false) || interface_exists($classes, false));
		}
		
		final public function __clone() { throw new \Exception('禁止克隆对象'); }
		
		public static function boot()
		{
			if (NULL === self::$_l) 
			{
				self::$_l = new self();
			}
		}
		
		public static function autoload($func = 'lv\load\L::load', $enable = TRUE)
		{
			$enable ? spl_autoload_register($func) : spl_autoload_unregister($func);
		}
		
		public static function state($set)
		{
			self::$_include = (Bool)$set;
		}
		
		public static function load($classes)
		{
			if (self::_checkLoad($classes)) 
			{
				return TRUE;
			}
			
			try
			{
				$path = (new Path($classes.'.php'))->get();
				return self::$_include ? include $path : require $path;
			}
			catch (\Exception $e) 
			{
				if (self::$_throw) 
				{
					self::$_throw = false;
					throw new \Exception($e->getMessage());
				}
			}
		}
		
		public static function loadObj($classes)
		{
			self::$_throw = true;
			strstr($classes, '.') && $classes = str_replace('.', '\\', $classes);
			
			if (func_num_args() > 1)
			{
				$args = func_get_args();
				array_shift($args);
				
				$class = new \ReflectionClass($classes);
				return $class->newInstanceArgs($args);
			}
			else 
			{
				return new $classes();
			}
		}
	}
}