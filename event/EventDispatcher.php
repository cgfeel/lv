<?php
namespace lv\event
{
	use \Exception;
	use lv\data\Queue;
		
	/**
	 * 事件监听、派发、管理
	 * @namespace lv\event
	 * @version 2014-08-17
	 * @author Levi <levi@cgfeel.com>
	 * @name EventDispatcher
	 * @copyright Copyright (c) 2006-2014 Levi
	 * @package	lv\request\EventDispatcher
	 * @subpackage 
	 */
	abstract class EventDispatcher
	{	
	    private $_callback_method = array();

	    private $_callback_tree;
	    private $_once = FALSE;
	    
	    /**
	     * 添加事件，可重复添加事件
	     * @param event|string $event	事件类型
	     * @param mixed $method			事件方法
	     * @param number|null $num		执行顺序，默认是NULL，顺序为10
	     * @return \lv\event\EventDispatcher
	     */
	    public function attach($event, $method, $num = NULL)
	    {
	    	$name = (String)$event;
	    	if (!empty($name) && trim($name, '.') == $name) 
	    	{
	    		$this->_check_callable($method);
	    		$this->_callback_method[$name][] = $method;
	    		
	    		$this->getTree()->append($name, is_numeric($num) ? $num : 10, TRUE);
	    	}
	    	
	    	return $this;
	    }
	    
	    /**
	     * 只添加不相同的事件方法，若方法相同则不再添加
	     * @param event|string $event	事件类型
	     * @param mixed $method			事件方法
	     * @param number|null $num		执行顺序，默认是NULL，顺序为10
	     * @return Ambigous <\lv\event\EventDispatcher, \lv\event\EventDispatcher>
	     */
	    public function attachOnce($event, $method, $num = NULL) 
	    {
	    	return $this->has($event) ? $this : $this->attach($event, $method, $num);
	    }
	    
	    /**
	     * 替换事件，用于将一组多个监听的冒泡事件替换为唯一事件监听
	     * @param event|string $event	事件类型
	     * @param mixed $method			事件方法
	     * @param number|null $num		执行顺序，默认是NULL，顺序为10
	     * @return \lv\event\EventDispatcher
	     */
	    public function replace($event, $method, $num = NULL) 
	    {
	    	$name = (String)$event;
	    	if (!empty($name) && trim($name, '.') == $name)
	    	{
	    		$this->_check_callable($method);
	    		$this->_callback_method[$name] = array($method);
	    		$this->getTree()->delete($name)->append($name, is_numeric($num) ? $num : 10);
	    	}
	    	
	    	return $this;
	    }

	    /**
	     * 获取队列
	     * @return \lv\data\Queue
	     */
	    protected final function getTree()
	    {
	    	/*
	    	 * 为什么没有在初始化时申明队列？
	    	 * 因为这是一个抽象类，在继承类中很容易忽略parent::__construct，因此容易产生错误
	    	 */
	    	!$this->_callback_tree && ($this->_callback_tree = new Queue());
	    	return $this->_callback_tree;
	    }
	    
	    /**
	     * 设置事件优先级
	     *   - 只能设置事件的监听顺序
	     *   - 不能设置事件方法执行顺序，一个事件绑定多个方法，根据方法绑定的先后顺序来执行
	     * @param event|string $event	事件类型
	     * @param number $num			大于等于0的数字
	     * @return \lv\event\EventDispatcher
	     */
	    public function listen($event, $num) 
	    {
	    	$name = (String)$event;
	    	if (isset($this->_callback_method[$name])) 
	    	{
	    		$this->getTree()->delete($name)->append($name, is_numeric($num) ? $num : 10);
	    	}
	    	
	    	return $this;
	    }

	    /**
	     * 检测是否存在监听事件
	     *   - 没有提供参数$method，检测是否存在监听事件
	     *   - 若提供了参数$method，检测是否存在监听事件的方法
	     * @param event|string $event	事件类型
	     * @param mixed $method			事件方法
	     * @return boolean
	     */
	    public function has($event, $method = NULL)
	    {
	    	$name = (String)$event;
	    	if ($method) 
	    	{
	    		$has = FALSE;
	    		$this->_rollMethod($name, $method, function() use(&$has)
	    		{
	    			$has = TRUE;
	    			return TRUE;
	    		});
	    		
	    		return $has;
	    	}
	    	
	    	return isset($this->_callback_method[$name]);
	    }
	    
	    /**
	     * 删除事件
	     *   - 没有提供参数$method，则删除当前事件，以及当前事件下绑定的监听事件
	     *   - 若提供了参数$method，只删除监听事件对应的方法，不会删除事件及当前事件下绑定的监听事件
	     * @param event|string $event	事件类型
	     * @param mixed $method			事件方法
	     * @return \lv\event\EventDispatcher
	     */
	    public function remove($event, $method = NULL) 
	    {
	    	$name = (String)$event;
	    	$callMed = &$this->_callback_method;
	    	
	    	if ($method) 
	    	{
	    		/*
	    		 * 这里只删除了事件绑定的方法，没有删除队列，为什么？
	    		 * 因为目前要做的就是删除事件中的特定方法，即便事件方法为空，仍旧应该保留事件，以便根据事件触发子方法
	    		 * 而除非删除整个事件，才是将其事件、以及子事件一并删除
	    		 */
	    		$this->_rollMethod($name, $method, function($name, $key) use(&$callMed) 
	    		{
	    			unset($callMed[$name][$key]);
	    			return FALSE;
	    		});
	    		
	    		return $this;
	    	}
	    	
	    	if (!isset($callMed[$name])) 
	    	{
	    		return $this;
	    	}
	    	
	    	foreach ($callMed as $key => $method) 
	    	{
	    		if (($key == $name) || (0 === strpos($key, $name) && strstr($key, '.'))) 
	    		{
	    			unset($callMed[$key]);
	    			$this->getTree()->delete($key);
	    		}
	    	}
	    	
	    	return $this;
	    }
	    
	    /**
	     * 派发事件
	     *   派发顺序：
	     *   - 若当前事件绑定有子集事件，则相继派发
	     *   - 派发的顺序根据事件的优先级来决定，若两个优先级一致的事件，根据绑定的先后顺序来决定
	     *   - 不一定父集事件优先子集事件派发
	     *   
	     *   冒泡：
	     *   - 若阻止冒泡，则会阻止同一事件绑定的多个对象方法，只执行第一个方法后退出
	     *   - 阻止冒泡后，不影响父级事件和对应的子集事件派发
	     * @param Event $event
	     * @return void|\lv\event\EventDispatcher
	     */
	    public function dispatch(Event $event) 
	    {
	    	$name = $event->__toString();
	    	if (!isset($this->_callback_method[$name])) 
	    	{
	    	    return $this;
	    	}
	    	
	    	$event->target = $this;
	    	$parm = func_get_args();
	    	
	    	$once = $this->_once;
	    	$self = $this;
	    	
	    	// 这里我只是将过滤方法继承覆盖了，而真正派发的代码我隐藏在自身的_trigger中
	    	$this->getTree()->action(function($key, $num) use($event, $once, $parm, $name, $self)
	    	{
	    		if (FALSE !== ($call = $self->_call($key, $event, $parm))) 
	    		{
	    			$event->power = $num;
	    			$self->_trigger($key, $event->bubbles && !$once, $call);
	    		}
	    	});
	    	
	    	return $this;
	    }
	    
	    /**
	     * 检查、过滤参数
	     * @param string $key
	     * @param Event $event
	     * @param array $parm
	     * @return multitype:|unknown
	     */
	    protected function _call($key, Event $event, Array $parm) 
	    {
	        if ($key == $event->type || (0 === strpos($key, $event->type) && strstr($key, '.'))) 
	        {
	            return $parm;
	        }

	        return array();
	    }
	    
	    private function _trigger($name, $bubbles = TRUE, Array $pram) 
	    {
	    	$method = $this->_callback_method[$name];
	    	foreach ($method as $call)
	    	{
	    		// 不建议这么做，如果构造函数必须传参的话
	    		if (is_array($call) && is_string($call[0]))
	    		{
	    			$call[0] = new $call[0]();
	    		}
	    		
	    		call_user_func_array($call, $pram);
	    		if (!$bubbles) break;
	    	}
	    	
	    	return $this;
	    }
	    
	    /**
	     * 只派发一次事件，不冒泡
	     *   - 若一个事件绑定了多个方法，只执行第一个方法
	     *   - 若一个事件下还有子集事件，不受冒泡影响
	     * @param Event $event
	     * @return \lv\event\EventDispatcher
	     */
	    public function dispatchOnce(Event $event) 
	    {
	    	$this->_once = TRUE;
	    	call_user_func_array(array($this, 'dispatch'), func_get_args());
	    	
	    	$this->_once = FALSE;
	    	return $this;
	    }
	    
	    private function _rollMethod($name, $method, \Closure $func) 
	    {
	    	$repc = function(&$data)
	    	{
	    		is_array($data) && is_object($data[0]) && ($data[0] = get_class($data[0]));
	    	};
	    	
	    	if (isset($this->_callback_method[$name]))
	    	{
	    		$repc($method);
	    		foreach ($this->_callback_method[$name] as $key => $call)
	    		{
	    			$repc($call);
	    			if ($method == $call && $func($name, $key))
	    			{
	    				return $this;
	    			}
	    		}
	    	}
	    	
	    	return $this;
	    }
	    
	    private function _check_callable($method) 
	    {
	    	if (!is_callable($method)) 
	    	{
	    		throw new Exception('调用方法不存在');
	    	}
	    	
	    	return $this;
	    }
	}
}