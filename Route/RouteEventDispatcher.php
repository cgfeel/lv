<?php
namespace lv\Route 
{
	use lv\event\Event;
	use lv\event\EventDispatcher;
		
	class RouteEventDispatcher extends EventDispatcher 
	{
	    public $filter;
		private static $_num = 0;
		
		private $_tree = array();
		private $_sort = array();
		private $_check;
		
		public function __construct(Route $check)
		{
		    $this->_check = $check;
		}
		
		/**
		 * 获取tree
		 * @return multitype:
		 */
		public function tree($point = NULL)
		{
		    return $point ? (isset($this->_tree[$point]) ? $this->_tree[$point] : NULL) : $this->_tree;
		}
		
		/**
		 *  除了要监听事件以外，还要根据key设置tree
		 *  命名规则：
		 *    - 3:public:route:get:user/{id?}/{q?}/user，分别为：
		 *      | 自增ID：用于区别不同的route规则，避免成为父子级关系，例如，0:public:get:route/uri和1:public:get:route/uri.sub
		 *      | 所属域名：public-公共匹配、group-组合匹配（优先public）、域名（指定域名的group）、mobile（移动端的group）
		 *      | 路由类型：route-路由事件、filter-过滤事件
		 *      | 请求方式：get、put、post、delete...
		 *      | route规则：事件名称
		 *    - 0:public:get:route/uri.filter，给Route绑定过滤方法，优先级要设置高于Route
		 * @see \lv\event\EventDispatcher::attach()
		 */
		public function attach($event, $method, $num = 10)
		{
			/*
			 * 先给事件名称后面添加一个“/”，保证绝对以“?}/”结尾
			 * 再获取最后一个“?}/”之后的字符
			 * 检查字符中是否存在“{”，若存在则是一个无效的route
			 * 
			 * 为什么要过滤：因为PHP方法中，只允许末尾的参数为可选参数
			 */
			if (FALSE === strpos(substr(strrchr($event.'/', '?}/'), '3'), '{') && trim($event, '.') == $event) 
			{
				if (isset($this->_sort[$event])) 
				{
					$sort = $this->_sort[$event];
				}
				else 
				{
					$sort = self::$_num++;
					$this->_sort[$event] = $sort;
				}
				
				$event = sprintf('%d:%s', $sort, $event);
				parent::attach($event, $method, $num)->_setTree($event);
				
				$this->filter && $num != 8 && $num != 4 && $this->_check->filter($this->filter);
			}
			
			return $this;
		}
		
		public function pond($method, $before)
		{
		    $self = $this;
		    $key = 'route_group.num:'.self::$_num++;
		    
		    $this->plugin($key.':before', $before, 2);
		    $this->plugin($key, $method, 2);
		    $this->plugin($key.':od', function() use($self)
		    {
		        $self->filter = null;
		    }, 2);
		    
		    return $key;
		}
		
		/**
		 * 插入默认事件，不计算在当前tree中
		 * @param string|Event $event 事件名称
		 * @param mixed $method       绑定方法
		 * @param int $num            优先级
		 * @param string $once        true-不重复绑定、false-重复绑定
		 * @return Ambigous <\lv\Route\RouteEventDispatcher, \lv\event\EventDispatcher, \lv\event\EventDispatcher>|Ambigous <\lv\event\EventDispatcher, \lv\event\EventDispatcher>
		 */
		public function plugin($event, $method, $num, $once = FALSE) 
		{
			if ($once) 
			{
				return $this->has($event) ? $this : parent::attach($event, $method, $num);
			}
			
			return parent::attach($event, $method, $num);
		}
		
		/**
		 * 获取队列截点
		 * @return \lv\data\Queue
		 */
		public function getQue() 
		{
		    return $this->getTree();
		}
		
		/**
		 * 有两个改动：
		 *   1.每次派发都允许group事件；
		 *   2.回调函数排除Event对象
		 * @param array $parm 
		 *   - 如果是Route指定派发的事件，那么一定返回事件本身对象（包含Event）
		 *   - 如果是Route截点事件，那么返回的参数只有获取到的路由变量（不包含Event）
		 * (non-PHPdoc)
		 * @see \lv\event\EventDispatcher::_call()
		 */
		protected function _call($key, Event $event, Array $parm = Array())
		{
		    $tree = FALSE;
		    if (0 === strpos($key, 'route_group') || $key == 'route_end' || 
		        (($tree = isset($this->_tree[$key])) && FALSE !== ($parm = $this->_check->select($key, $this->_tree[$key])))) 
		    {
		        $tree && ($this->_check->dispatch = TRUE);
		        return $parm;
		    }

		    /*
		     * 注意哦，这里是FALSE
		     * 因为可能传过来的参数数组只有1个，如果弹出第一个事件对象后，返回的就是一个空数组
		     * 所以要判断最终是否执行事件，需要判断false !==
		     */
		    return FAlSE;
		}
		
		private function _setTree($key) 
		{
		    $info = explode(':', $key, 5);
			if ($info[4] == '/') 
			{
				$this->_tree[$key] = array('path' => $info, 'num' => array(1), 'pres' => array(''));
			}
			else 
			{
				$pres = explode('/', $info[4]);
				$this->_tree[$key] = array('path' => $info, 'num' => $this->_countPres($pres), 'pres' => $pres);
			}
		}
		
		private function _countPres(Array $pres) 
		{
			$cont = count($pres);
			$data = array($cont);
			
			foreach ($pres as $val) 
			{
				('?}' == substr($val, -2, 2)) && ($data[] = --$cont);
			}
			
			return $data;
		}
	}
}