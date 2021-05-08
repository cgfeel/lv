<?php
namespace lv\event 
{
	/**
	 * 根据对象绑定事件方法
	 * @namespace lv\event
	 * @version 2014-08-17
	 * @author Levi <levi@cgfeel.com>
	 * @name EventQuery
	 * @copyright Copyright (c) 2006-2014 Levi
	 * @package	lv\request\EventQuery
	 * @subpackage lv\request\EventDispatcher
	 */
	class EventQuery extends EventDispatcher
	{
		private $_set = array();
		private $_obj;
		
		/**
		 * 传入初始对象，之后派发事件会根据这个对象来决定
		 * @param object $obj
		 */
		public function __construct($obj)
		{
			$this->_obj = $obj;
		}
		
		/**
		 * 绑定事件
		 * @param string|Event $name	事件类型
		 * @param mixed $func			若传递的方法是一个字符串，则会在事件绑定的对象中，查找和这个字符串同名的方法
		 */
		public function __set($name, $func)
		{
			$this->_attach($name, $func);
		}
		
		/**
		 * 绑定事件
		 * @param string|Event $method	事件类型
		 * @param mixed $func			若传递的方法是一个字符串，则会在事件绑定的对象中，查找和这个字符串同名的方法
		 * @return \lv\event\EventQuery
		 */
		public function on($method, $func = NULL)
		{
			if (is_string($method)) 
			{
				if (!$func) return $this;
				$method = array($method => $func);
			}
			
			foreach ($method as $key => $func)
			{
				$this->_attach((string)$key, $func);
			}
			
			return $this;
		}
		
		/**
		 * 派发事件
		 * @param Event|Array $events	必须是一个Event对象，或者是一组Event对象
		 * @return \lv\event\EventQuery
		 */
		public function trigger($events) 
		{
			$parm = func_get_args();
			$events = array_shift($parm);
			
			is_object($events) && ($events = array($events));
			foreach ($events as $evt) 
			{
				is_a($evt, 'lv\event\Event') && call_user_func_array(array($this, 'dispatch'), array_merge(array($evt), $parm));
			}
			
			return $this;
		}
		
		/**
		 * 解绑对象
		 *   - 若只传入事件对象，则解除绑定整个事件
		 *   - 若传入方法名称或方法对象，则删除事件中特定的方法
		 * @param string|Event $events	事件类型
		 * @return \lv\event\EventQuery
		 */
		public function unbind($events)
		{
			if (is_array($events)) 
			{
				foreach ($events as $key => $tar) 
				{
					is_string($key) ? $this->remove($key, array($this->_obj, $tar)) : $this->_remove($tar);
				}
			}
			else
			{
				$this->_remove($events);
			}
			
			return $this;
		}
		
		private function _remove($name) 
		{
			foreach ($this->_set as $key => $set) 
			{
				if ($name != $key) continue;
				foreach ($set as $methon) 
				{
					$this->remove($key, is_string($methon) ? array($this->_obj, $methon) : $methon);
				}
			}
		}
		
		private function _attach($name, $func) 
		{
			$this->_set[$name][] = $func;
			$this->attach($name, is_string($func) ? array($this->_obj, $func) : $func);
		}
	}
}