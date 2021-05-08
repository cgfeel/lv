<?php
namespace lv\filter
{
	use lv\event\EventDispatcher;
	
	/**
	 * 数据获取、验证、过滤
	 * @namespace lv\file
	 * @version 2014-07-17
	 * @author Levi <levi@cgfeel.com>
	 * @name Check
	 * @copyright Copyright (c) 2006-2013 Levi
	 * @package	lv\filter\Check
	 * @subpackage EventDispactcher
	 */
	class Check extends EventDispatcher
	{
		const ERROR = 'Check.error';
		const CUSTOM = -1;
		
		// 存储自定义数据
		private static $_custom = array();
		
		// 捕获异常还是抛出事件
		private $_throw = TRUE;
		
		// 当前获取数据方式
		private $_type = -1;
		
		// 关键字
		private $_key = '';
		
		use \lv\filter\lib\Num;
		use \lv\filter\lib\Str;
		use \lv\filter\lib\Arr;
		use \lv\filter\lib\Code;
		use \lv\filter\lib\Mini;
		use \lv\filter\lib\Url;
		
		/**
		 * 设置获取数据方式
		 * @param string $type
		 */
		public function __construct($type = INPUT_POST, $key = '', $func = NULL)
		{
			$this->point($type);
			!empty($key) && $this->set($key, $func);
		}
		
		public function __toString()
		{
			return $this->get();
		}
		
		public function setThrow($catch = FALSE)
		{
			$this->_throw = $catch;
			return $this;
		}
		
		/**
		 * 一旦发生监听，将关闭异常抛出，改为事件捕获
		 * 如果存在多种情况检测，请flush当前对象
		 * @see \lv\event\EventDispatcher::attach()
		 */
		public function attach($event, $method, $class = NULL)
		{
			parent::attach($event, $method, $class);
			$this->_throw = FALSE;
			
			return $this;
		}
		
		/**
		 * 更新获取数据方式
		 * @param int $type
		 * @return \lv\filter\Check
		 */
		public function point($type = -1)
		{
			$this->_type = (Int)$type;
			return $this;
		}
		
		/**
		 * 获取关键字
		 * @return string
		 */
		public function key()
		{
			return (String)$this->_key;
		}
		
		/**
		 * 获取请求类型
		 * @return number
		 */
		public function type()
		{
			return $this->_type;
		}
		
		/**
		 * 获取数据，可以指定关键字获取，如果不指定关键字，将以当前关键字获取数据
		 * @param int    $type	获取数据类型，默认0：任意数据类型；1.字符串；2.整型；3.浮点型；4.数组型；5.布尔型；6.对象型
		 * @param string $key
		 * @return NULL|multitype:
		 */
		public function get($type = 0, $key = '')
		{
			empty($key) && $key = $this->_key;
			if (!isset(self::$_custom[$key]))
			{
				$this->tarp('存储数据中没有找到和关键字匹配的信息', 1);
				return NULL;
			}
			
			switch ($type)
			{
				case 1: return (Int)self::$_custom[$key];
				case 2: return (String)self::$_custom[$key];
				case 3: return (Float)self::$_custom[$key];
				case 4: return (Array)self::$_custom[$key];
				case 5: return (Boolean)self::$_custom[$key];
				case 6: return (Object)self::$_custom[$key];
				default: return self::$_custom[$key];
			}
		}
		
		/**
		 * 在数据找查找数据
		 * @param string $key
		 * @param \stdClass $func
		 * @return \lv\filter\Check
		 */
		public function set($key, \Closure $func = NULL)
		{
			$this->_key = $key;
			$data = $this->_type();
			if (isset($data[$key])) 
			{
				self::$_custom[$key] = $data[$key];
				if ($func) 
				{
					try 
					{
						$bind = \Closure::bind($func, $this);
						$bind(self::$_custom[$key]);
					}
					catch (\Exception $e)
					{
						$code =  $e->getCode();
						$this->tarp($e->getMessage(), $code ? $code : 2);
					}
				}
			}
			else 
			{
				!$func && $this->tarp('请求中没有找到和关键字匹配的信息', 3);
			}
			
			return $this;
		}
		
		/**
		 * 自定义数据
		 * @param mixed $val
		 * @param string $key
		 * @param \stdClass $func
		 * @return Ambigous <\lv\filter\$this, \lv\filter\Check>
		 */
		public function setVal($val, $key = '', \Closure $func = NULL)
		{
			//$this->_key = empty($key) ? 'de_'.(time() + mt_rand()) : $key;
			if (empty($key)) 
			{
				$key = 'de_'.(time() + mt_rand());
			}
			
			self::$_custom[$key] = $val;
			return $this->point(self::CUSTOM)->set($key, $func);
		}
		
		/**
		 * 更新新当前值
		 * @param unknown $val
		 * @return \lv\filter\Check
		 */
		public function upVal($val)
		{
			$key = $this->_key;

			isset(self::$_custom[$key]) && self::$_custom[$key] = $val;
			return $this;
		}
		
		/**
		 * 刷新、初始数据
		 * @return $this;
		 */
		public function flush()
		{
			self::$_custom = array();
			return $this;
		}
		
		/**
		 * 抛出异常
		 * @param string $str
		 * @param int $code
		 * @throws Tarp
		 */
		public function tarp($str, $code = 0)
		{
			try 
			{
				$data = ($code == 2) ? $this->get() : NULL;
				throw (new Tarp($str, $this->key(), $code))->set($this->type(), $data);
			}
			catch (Tarp $e)
			{
				if ($this->_throw) 
				{
					throw $e;
				}
				else
				{
					parent::dispatch(new FilterEvent(self::ERROR, $e));
				}
			}
		}
		
		/**
		 * 批量验证数据
		 * @param int $flags
		 * @return array
		 */
		public function multi($flags)
		{
			$val = (Array)$this->get();
			return (Array)filter_var_array($val, $flags);
		}
		
		/**
		 * 数据范围检查
		 * @return $this
		 */
		public function inArray($data)
		{
			!in_array($this->get(), $data) && $this->tarp('提交的数据不在特定范围内', 5);
			return $this;
		}
		
		/**
		 * 获取必须要有的字段：如果全都在，则返回空，有缺少字段会以数组形式返回回来
		 * @param array $field
		 * @return multitype:
		 */
		public function must(Array $field)
		{
			return array_diff($field, array_keys(self::$_custom));
		}
		
		public function sanitize($type = 1, $flags = NULL)
		{
			$set = $flags ? array('flags' => $flags) : NULL;
			$val = $type % 2 ? $this->get(1) : $this->get(2);
			switch ($type)
			{
				case 1: $id = FILTER_SANITIZE_NUMBER_FLOAT; break;
				case 2: $id = FILTER_SANITIZE_EMAIL; break;
				case 3: $id = FILTER_SANITIZE_NUMBER_INT; break;
				case 4: $id = FILTER_SANITIZE_ENCODED; break;
				case 6: $id = FILTER_SANITIZE_MAGIC_QUOTES; break;
				case 8: $id = FILTER_SANITIZE_SPECIAL_CHARS; break;
				case 10: $id = FILTER_SANITIZE_FULL_SPECIAL_CHARS; break;
				case 12: $id = FILTER_SANITIZE_STRING; break;
				case 14: $id = FILTER_SANITIZE_STRIPPED; break;
				case 14: $id = FILTER_SANITIZE_URL; break;
				case 16: $id = FILTER_UNSAFE_RAW; break;
				default: $id = 0;
			}
			
			return $id ? $this->upVal(filter_var($val, $id, $set)) : $this;
		}
		
		public function validate($type = 1, $flags = NULL, $options = NULL)
		{
			$parm = array();
			$options && $parm['options'] = $options;
			$flags && $parm['flags'] = $flags;
			switch ($type)
			{
				case 1:
					$parm += array(TRUE);
					if ($parm !== filter_var($this->get(), FILTER_VALIDATE_BOOLEAN, $parm)) 
					{
						$this->tarp('数据类型验证错误', 10001);
					}
					break;
					
				case 2:
					if (!filter_var($this->get(2), FILTER_VALIDATE_EMAIL, $parm))
					{
						$this->tarp('邮箱地址不正确', 10002);
					}
					break;
					
				case 3:
					if (!filter_var($this->get(), FILTER_VALIDATE_FLOAT, $parm))
					{
						$this->tarp('数据不是浮点型', 10003);
					}
					break;
					
				case 4:
					if (!filter_var($this->get(), FILTER_VALIDATE_INT, $parm))
					{
						$this->tarp(empty($parm) ? '数据不是数字' : '数值范围错误', 10004);
					}
					break;
					
				case 5:
					if (!filter_var($this->get(2), FILTER_VALIDATE_IP)) 
					{
						$this->tarp('IP地址错误', 10005);
					}
					
				case 6:
					if (!filter_var($this->get(), FILTER_VALIDATE_REGEXP, $parm)) 
					{
						$this->tarp('数据错误', 10006);
					}
					break;
				case 7:
					if (!filter_var($this->get(2), FILTER_VALIDATE_URL, $parm))
					{
						$this->tarp('不是正确的URL地址', 10007);
					}
					break;
			}
			
			return $this;
		}
		
		public function _type()
		{
			switch ($this->_type)
			{
				case -1: return self::$_custom;
				case 0: return $_POST;
				case 1: return $_GET;
				case 2: return $_COOKIE;
				case 4: return $_ENV;
				case 5: return $_SERVER;
				case 6: return $_SESSION;
				case 99: return $_REQUEST;
				default: $this->tarp('错误的请求方式', 4);
			}
		}
	}
}