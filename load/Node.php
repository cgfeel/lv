<?php
namespace lv\load
{
	use lv\file\Path;
	
	/**
	 * 以节点、包的形式加载PHP
	 * @namespace lv\load
	 * @version 2014-11-2
	 * @author Levi <cgfeel@gmail.com>
	 * @name HTTP
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	lv\load\Node
	 * @subpackage lv\load\L
	 */
    class Node extends L
    {
        private $_package;
        
        private $_ispath = 0;
        private $_point = '';
        
        private static $_defined = array(
        	'config' => array(
        		'event' => 'lv\event\Event',
        		'event_dispatcher' => 'lv\event\EventDispatcher',
        		'event_query' => 'lv\event\EventQuery',
        		'http' => 'lv\request\HTTP',
        		'http_proxy' => 'lv\request\HTTP_Proxy',
        		'path' => 'lv\file\Path',
        		'queue' => 'lv\data\Queue',
        		'route' => 'lv\route\Route'
        	),
            'repath' => array()
        );
        
        /**
         * 指定包的名称，预备调用对应的PHP
         * @param string $package
         * @param string $subname
         * @throws \Exception
         */
        public function __construct($package, $subname = '') 
        {
        	parent::boot();
        	if (is_string($package)) 
        	{
        		if (false != ($path = $this->_get($package))) 
        		{
        			$package = $path;
        		}
        		
        		if (!empty($subname)) 
        		{
//         			$this->_point && 
        			$this->_point = $subname;
        			$subname = '\\'.$subname;
        		}
        		
        		$this->_package = $this->trim(str_replace('/', '\\', $package).$subname);
        	}
        	else 
        	{
        		throw new \Exception('package 必须为字符串');
        	}
        }
        
        /**
         * 调用并返回包对应的php对象
         * @throws \Exception
         * @return mixed
         */
        public function create() 
        {
        	if (false == ($pack = $this->_package)) 
        	{
        		throw new \Exception('没有扩张包');
        	}
        	
        	$args = func_get_args();
        	if ($this->_point) 
        	{
        		parent::load($this->_package);
        		parent::_checkLoad($this->_point) && $pack = $this->_point;
        	}
        	
        	array_unshift($args, $pack);
        	return call_user_func_array(__NAMESPACE__.'\L::loadObj', $args);
        }
        
        /**
         * 加载对象中的静态方法
         * @param string $name
         * @return mixed
         */
        public function stat($name) 
        {
        	return $this->_call($this->_package.'::'.$name, func_get_args());
        }
        
        /**
         * 加载对象中的常量
         * @param string $name
         * @param boolean $glob
         * @throws \Exception
         * @return mixed
         */
        public function cont($name, $glob = FALSE) 
        {
        	$space = sprintf('%s%s%s', $this->_package, $glob ? '\\' : '::', $name);
        	if (defined($space)) 
        	{
        		return constant($space);
        	}
        	
        	throw new \Exception('不存在的常量');
        }
        
        /**
         * 加载对象中的函数
         * @param string $name
         * @throws \Exception
         * @return mixed
         */
        public function func($name) 
        {
        	$pack = '\\'.$this->trim($this->_package.'\\'.$name);
        	if (!function_exists($pack))
        	{
        		$path = (new Path($this->trim($pack).'.php'))->get();
        	
        		parent::$_include ? include $path : require $path;
        		if (!function_exists($pack))
        		{
        			throw new \Exception('不存在的函数');
        		}
        	}
        	
        	return $this->_call($pack, func_get_args());
        }
        
        /**
         * 过滤、处理包的名称
         * @param string $pack
         * @return string
         */
        public function trim($pack) 
        {
        	return trim($pack, '\\');
        }
        
        /**
         * 检查包
         * @param string $name
         */
        public function inPack($name = '') 
        {
        	return empty($name) ? self::$_defined : isset($this->_get()[$name]);
        }
        
        /**
         * 将路由和路径保存成一个新的包，以便后续调用
         * @param string $name
         * @param boolean $repath
         * @return \lv\load\Node
         */
        public function save($name, $repath = false) 
        {
        	if (!isset($this->_get()[$name])) 
        	{
        		self::$_defined[$repath ? 'repath' : 'config'][$name] = $this->_package;
        		
        		$this->_ispath = $repath ? 2 : 1;
        		$repath && $this->_point = $name;
        	}
        	
        	return $this;
        }
        
        private function _get($name = '') 
        {
        	if (empty($name)) 
        	{
        		return self::$_defined['config'] + self::$_defined['repath'];
        	}
        	elseif (isset(self::$_defined['config'][$name]))
        	{
        		$this->_ispath = 1;
        		return self::$_defined['config'][$name];
        	}
        	elseif (isset(self::$_defined['repath'][$name])) 
        	{
        		$this->_ispath = 2;
        		$this->_point = $name;
        		return self::$_defined['repath'][$name];
        	}
        	
        	return '';
        }
        
        private function _call($pack, $arg) 
        {
        	array_shift($arg);
        	return call_user_func_array($pack, $arg);
        }
    }
}