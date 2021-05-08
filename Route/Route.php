<?php
namespace lv\Route
{
    use \Exception;
	use lv\request\Header;
	use lv\url\URL;
	use lv\event\Event;
				
	class Route
	{
		const INT = 'INT';
		const UINT = 'UINT';

		public $dispatch = FALSE;
		
		// 为了给外部静态方法调用，所以$_parm、$_domain为静态变量
		private static $_mobile = null;
		private static $_domain = array();
		private static $_parm = array();
		private static $_request;
		private static $_key;

		private $_patter = array('global' => array(), 'route' => array());
		private $_group = 'public';
		private $_tinck = null;
		
		private $_route;
		private $_path;
		
		public function __construct() 
		{
			$url = (new URL)->get(FALSE);
			if (strstr($url, '?')) 
			{
				// 去掉URL中通过“?”进行的GET请求
				self::go(rtrim(explode('?', $url)[0], '/'));
			}
			
			$info = parse_url($url);
			if ('/' != $info['path'] && '/' == substr($url, -1, 1)) 
			{
				// 去掉以“/”结尾的URL
				self::go(rtrim($url, '/'));
			}
			
			$this->_init($info);
			self::$_domain = array();
			self::$_key = '';
		}
		
		/**
		 * 请求方式
		 * @param string $name    请求类型
		 * @param array $arg      绑定事件：键名-路由规则、键值-监听方法
		 * @throws \Exception
		 * @return \lv\Route\Route
		 */
		public function __call($name, Array $arg) 
		{
		    $this->_tinck = null;
			$route = $this->_route;
			
			$attach = function($str) use($route, $arg)
			{
				if (count($arg) > 1)
				{
					list($key, $method) = $arg;
					$key = trim($key, '/');
					
					$ispublic = ($this->_group == 'public');
					
					// 只有设置uri的group的时候，需要修改下规则路径
					if (strstr($this->_group, '/')) 
					{
					    $domain = explode(':', $this->_group, 2);
					    if (count($domain) == 2) 
					    {
					        $group = $domain[0];
					        $key = trim($domain[1].'/'.$key, '/');
					    }
					    else 
					    {
					        $group = 'group';
					        $key = trim($domain[0].'/'.$key, '/');
					    }
					}
					else 
					{
					    $group = $this->_group;
					    empty($key) && ($key = '/');
					}
					
					$key = sprintf('%s:route:%s:%s', $group, $str, $key);
					$route->attach($key, $method, $ispublic ? 10 : 6);
					return TRUE;
				}
				
				return FALSE;
			};
			
			$req = array(
					'any', 'get', 'post', 'head', 'put', 'delete', 
					'options', 'trace', 'patch', 'move', 'copy', 
					'link', 'unlink', 'wrapped', 'extension-mothed'
			);
			
			$name = strtolower($name);
			if (in_array($name, $req) && $attach($name)) 
			{
				return $this;
			}
			else 
			{
				throw new Exception('不存在的请求');
			}
		}
		
		/**
		 * 多个请求注册一个路由
		 * @param array $reqs
		 * @param string $name
		 * @param mixed $method
		 * @return \lv\Route\Route
		 */
		public function match(Array $reqs, $name, $method) 
		{
		    foreach ($reqs as $req) 
		    {
		        $this->$req($name, $method);
		    }
		    
		    return $this;
		}
		
		/**
		 * 筛选路由
		 * @param string $key     路由事件名称
		 * @param array $route    路由规则
		 * @return boolean|multitype:
		 */
		public function select($key, Array $route)
		{
		    self::$_key = $key;
		    if ($route['path'][3] != self::$_request && $route['path'][3] != 'any') 
		    {
		        return false;
		    }

		    // 如果设置了域请求，则只允许相关的域下的路由
		    $domain = $route['path'][1];
		    if ((empty(self::$_domain) && $domain != 'public' && $domain != 'group') ||
		        (self::$_domain && !isset(self::$_domain[$domain])))
		    {
		        return false;
		    }
		    
		    if ($route['path'][4] == $this->_path['route'])
		    {
		        return array();
		    }

		    if (in_array($this->_path['num'], $route['num']) && $this->_path['pres'][0] == $route['pres'][0])
		    {
		        try
		        {
		            $name = substr($key, strpos($key, ':') + 1);
		            $patter = isset($this->_patter['route'][$name]) ? $this->_patter['route'][$name] : array();
		            
		            $this->_getArg($this->_path['pres'], $route['pres'], $patter + $this->_patter['global']);
		            return self::$_parm;
		        }
		        catch (\Exception $e) {}
		    }
		    
		    return false;
		}
		
		/**
		 * 路由分组
		 * @param mixed $method   绑定事件
		 * @return \lv\Route\Route
		 */
		public function group($mod, $method = null) 
		{
		    if ($method) 
		    {
		        if (false === ($mod = $this->_pikMod($mod))) 
		        {
		            return $this;
		        }
		    }
		    else
		    {
		        $method = $mod;
		        $mod = 'group';
		    }
		    
			$self = $this;
			$this->_tinck = $this->_route->pond($method, function() use($mod, $self) 
			{
			    $self->_group = $mod;
			});
			
			$this->_route->plugin('route_group.after', function() use($self)
			{
			    $self->_group = 'public';
				$self->_route->filter = null;
				$self->_route->remove('route_group');
			}, 3, TRUE);
			
			return $this;
		}
		
		/**
		 * 获取当前的路由事件名称
		 * @return mixed
		 */
		public function current() 
		{
			$tree = array_keys($this->_route->tree());
			return array_pop($tree);
		}
		
		/**
		 * 设置全局的路由规则
		 * @param string|array $set   string则为规则名称，array则为规则键值对
		 * @param string|null $pat    只有当$set为string的时候才有用，若为NULL不会设置其规则
		 * @return \lv\Route\Route
		 */
		public function patter($set, $pat = NULL) 
		{
			return $this->_setPatter($this->_patter['global'], $set, $pat);
		}
		
		/**
		 * 设置局部规则，注：只设置当前路由规则，若public和group路由同名，不共享局部规则
		 * @param string|array $set   string则为规则名称，array则为规则键值对
		 * @param string|null $pat    只有当$set为string的时候才有用，若为NULL不会设置其规则
		 * @return \lv\Route\Route
		 */
		public function where($set, $pat = NULL) 
		{
		    // 如果是全局的filter，是不允许添加单个筛选规则的
		    if ($this->_tinck) 
		    {
		        return $this;
		    }
		    
		    $add = \Closure::bind(function($key) use($set, $pat)
		    {
		        !isset($this->_patter['route'][$key]) && ($this->_patter['route'][$key] = array());
		        $this->_setPatter($this->_patter['route'][$key], $set, $pat);
		    }, $this);
		    
			$key = $this->current();
			$group = explode(':', $this->current(), 5);
			array_shift($group);
			
			// 添加局部路由规则
			$group[1] = 'route';
			$add(implode(':', $group));
			
			// 添加局部路由对应filter的规则
			$group[1] = 'filter';
			$add(implode(':', $group));
			
			return $this;
		}
		
		/**
		 * 设置路由过滤，若public和group路由同名，不共享同一路由过滤
		 * @param mixed $method   过滤器绑定的方法
		 * @return \lv\Route\Route
		 */
		public function filter($method, $nb= null) 
		{
		    // 如果全局的话，则会监听一个全局的filter
		    if ($this->_tinck) 
		    {
		        $self = $this;
		        $name = $this->_tinck.':filter';
		        $this->_route->plugin($name, function () use($method, $self)
		        {
		            $self->_route->filter = $method;
		        }, 2);
		        
		        $this->_route->getQue()->before($name, $this->_tinck);
		    }
			else 
			{
			    $group = explode(':', $this->current(), 5);
			    $group[2] = 'filter';
			
    			array_shift($group);
    			$this->_route->attach(implode(':', $group), $method, $this->_group == 'public' ? '8' : '4');
			}
			
			return $this;
		}
		
		/**
		 * 执行，队列效验路由
		 * @return \lv\Route\Route
		 */
		public function action() 
		{
			$this->_route->plugin('route_group.start', function() 
			{
			    ob_start();
			}, 1, TRUE);
			
			$this->_route->dispatch(new Event('route_group.start'));
			return $this;
		}
		
		/**
		 * 页面跳转
		 * @param string $url 跳转的URL
		 * @param bool $tmp   true: 临时跳转（默认）、false: 永久跳转
		 */
		public static function go($url, $tmp = true)
		{
		    if ($url) 
		    {
		        ob_clean();
		        (new Header())->page($tmp ? 302 : 301, $url);
		        exit;
		    }
		}
		
		/**
		 * 终止页面，目前只支持：404、500
		 * @param number $num
		 */
		public static function abort($num = 404) 
		{
		    ob_clean();
		    (new Header())->page($num);
			exit;
		}
		
		/**
		 * 获取域名规则中的变量值
		 * @param string $key
		 * @return string|NULL
		 */
		public static function domain($key) 
		{
		    $name = explode(':', self::$_key, 5)[1];
		    if (isset(self::$_domain[$name]) && isset(self::$_domain[$name][$key])) 
		    {
		        return self::$_domain[$name][$key];
		    }
		    
		    return null;
		}
		
		/**
		 * 获取当前页面的URL
		 * @return string
		 */
		public static function url() 
		{
		    return (new URL)->get();
		}
		
		/**
		 * 获取URI中的请求参数
		 * @param string $key
		 * @return Ambigous <NULL, multitype:>
		 */
		public static function input($key) 
		{
			return isset(self::$_parm[$key]) ? self::$_parm[$key] : NULL;
		}
		
		/**
		 * 获取当前队列中的路由规则
		 * @return string
		 */
		public static function key() 
		{
		    return explode(':', self::$_key, 5)[4];
		}
		
		public static function request() 
		{
		    return self::$_request;
		}
		
		/**
		 * 检查是不是移动端口
		 * @return boolean
		 */
		public static function isMobile() 
		{
		    if (is_bool(self::$_mobile)) 
		    {
		        return self::$_mobile;
		    }
		    
		    $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
		    if ($userAgent) 
		    {
		        $clientkeywords = array(
		            'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'silk/',
		            'philips', 'panasonic', 'alcatel', 'lenovo', 'kindle', 'iphone', 'ipod', 'ipad', 'blackberry', 'meizu',
		            'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'psp', 'operamini', 'opera mini',
		            'operamobi', 'opera mobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
		        );
		        
		        $replace = str_replace($clientkeywords, '', $userAgent);
		        self::$_mobile = ($userAgent != $replace);
		    }
		    else 
		    {
		        self::$_mobile = false;
		    }

		    return self::$_mobile;
		}
		
		private function _init(array $info) 
		{
		    $path = $info['path'];
		    if (strlen($path) > 1)
		    {
		        $route = trim($path, '/');
		        $pres = explode('/', $route);
		        $this->_path = array('num' => count($pres), 'route' => $route, 'pres' => $pres, 'info' => $info);
		    }
		    else
		    {
		        $this->_path = array('num' => 1, 'route' => '/', 'pres' => array(''), 'info' => $info);
		    }
		    
		    self::$_request = strtolower($_SERVER['REQUEST_METHOD']);
		    $this->_route = new RouteEventDispatcher($this);
		    
		    $self = $this;
		    $this->_route->plugin('route_end', function () use($self)
		    {
		        exit;
		        $self->dispatch ? ob_end_flush() : Route::abort();
		    }, 15);
		}
		
		private function _setPatter(&$patter, $set, $pat = NULL) 
		{
			if (is_string($set) && $pat)
			{
				$patter[$set] = $pat;
			}
			elseif (is_array($set))
			{
				foreach ($set as $name => $pat)
				{
					$patter[$name] = $pat;
				}
			}
			
			return $this;
		}
		
		public function _pikMod($mod) 
		{
		    $domain = $path = null;
		    $isDomain = function($str)
		    {
		        return strstr($str, '.') && !strstr($str, '/');
		    };
		    
		    if (is_string($mod)) 
		    {
		        if ($isDomain($mod))
		        {
		            $domain = $mod;
		        }
		        else 
		        {
		            $path = $mod;
		        }
		    }
		    elseif (is_array($mod)) 
		    {
		        extract($mod);
		        $domain && !$isDomain($domain) && ($domain = null);
		    }
		    
		    if ($domain)
		    {
		        // 域名规则中不可以有?}
		        if (strstr($domain, '?}'))
		        {
		            return false;
		        }
		    
		        // 如果之前已有规定过规则，就不用再解析一遍了，只要将规则绑定到路由中就可以
		        if (!isset(self::$_domain[$domain]))
		        {
		            // 如果不匹配，也不再匹配了
		            $this->_pikDomain($domain) && (self::$_domain[$domain] = self::$_parm);
		        }
		    }
		    
		    if ($path)
		    {
		        if (strstr($path, '{'))
		        {
		            return false;
		        }
		    
		        $path = '/'.trim($path, '/');
		    }
		    
		    $path && ($path = '/'.trim($path, '/'));
		    if (!$domain && !$path)
		    {
		        return false;
		    }
		    elseif ($domain && $path) 
		    {
		        return $domain.':'.$path;
		    }
		    else
		    {
		        return $domain ? $domain : $path;
		    }
		}
		
		private function _pikDomain($mod)
		{
		    $host = $this->_path['info']['host'];
		    if ($host == $mod)
		    {
		        return true;
		    }
		
		    // 后缀必须一致
		    $suffix = substr($mod, strrpos($mod, '}') + 1);
		    if (rtrim($host, $suffix) != $host)
		    {
		        try
		        {
		            $info = explode('.', $host);
		            $route = explode('.', $mod);
		            if (count($info) == count($route))
		            {
		                $this->_getArg($info, $route, $this->_patter['global']);
		                return true;
		            }
		        }
		        catch (\Exception $e) {}
		    }
		
		    return false;
		}
		
		private function _getArg($info, $route, $patter)
		{
			self::$_parm = array();
			$exce = function($data, $patth) 
			{
				switch ($patth) 
				{
					case Route::INT: return is_numeric($data) ? (int)$data : false;
					case Route::UINT: 
					    if (is_numeric($data) && (int)$data > 0) 
					    {
					        return (int)$data;
					    }
					    
					    return false;
					default: 
					    $data = urldecode($data);
					    if (preg_match(sprintf('/^%s$/', $patth), $data)) 
					    {
					        return $data;
					    }
					    
					    return false;
				}
			};
			
			$parm = &self::$_parm;
			$clen = function($key, $val) use(&$parm, $patter, $exce)
			{
				if ('{' == $key[0])
				{
					if ($val && '}' == substr($key, -1, 1)) 
					{
						$name = rtrim(substr($key, 1, -1), '?');
						if (!($iset = isset($patter[$name]))) 
						{
						    $parm[$name] = urldecode($val);
						    return TRUE;
						}
						
						if ($iset && false !== ($val = $exce($val, $patter[$name]))) 
						{
							$parm[$name] = $val;
							return TRUE;
						}
					}
					elseif (!$val && '?}' == substr($key, -2, 2)) 
					{
						return TRUE;
					}
				}

				throw new \Exception('不存在的URI');
			};
			
			for ($i = 0, $l = count($info); $i < $l; $i++) 
			{
				$val = $info[$i];
				$kel = array_search($val, $route);
				$pot = array_shift($route);
				
				if (FALSE === $kel && $clen($pot, $val)) 
				{
					continue;
				}
				elseif (0 === $kel) 
				{
					continue;
				}
				else
				{
					if (isset($info[$i + $kel]) && $info[$i + $kel] == $val && $clen($pot, $val)) 
					{
						continue;
					}
					else
					{
						while ($kel && $clen($pot, NULL)) 
						{
							$pot = array_shift($route);
							$kel--;
							$i++;
						}
					}
				}
			}
			
			if ($route) 
			{
				foreach ($route as $str) 
				{
					$clen($str, NULL);
				}
			}
		}
	}
}