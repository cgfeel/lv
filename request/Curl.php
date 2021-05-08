<?php
namespace lv\request
{
	use data;
	use lv\event\EventDispatcher;
	
	/**
	 * Curl 包装类，这是一个抽象类，不允许直接声明，请根据需要使用相应的子类
	 * @namespace lv\request
	 * @version 2014-07-28
	 * @author Levi <levi@cgfeel.com>
	 * @name Curl
	 * @copyright Copyright (c) 2006-2014 Levi
	 * @package	lv\request\Curl
	 * @subpackage lv\request\EventDispatcher
	 */
	abstract class Curl extends EventDispatcher
	{
		CONST EVENT = 'curl:request';
		
		protected $url;
		protected $opts = array();
		
		protected $_cache = array('data' => array(), 'state' => array());
		
		protected static $_state = array();
		protected static $_data = array();

		private $_isCache = TRUE;
		private $_isMulti = FALSE;
		private $_forced = FALSE;
		
		private $_field = array();
		private $_size = 0;
		
		
		/**
		 * 设置请求地址
		 * @param string $url
		 */
		public function __construct($url)
		{
			$this->init($url);
		}
		
		public function __toString()
		{
			return $this->url;
		}
		
		/**
		 * 初始化请求，每次请求新网址的时候都会初始化设置
		 *   - 根据请求的URL，决定是否SSL请求
		 *   - 若是混合http、https进行请求，则设置为非SSL请求
		 *   - 若要请求http和https，请分别请求
		 * @param string|array $url
		 * @return \lv\request\Curl
		 */
		public function init($url)
		{
			$this->url = $url;
			$this->opts = array();
			
			$this->_field = array();
			$this->_isMulti = is_array($url);
			
			$ex = is_array($url) ? $this->_checkArrURL($url) : $url;
			if (0 == strpos($ex, 'https://')) 
			{
				$this->set(array(
						CURLOPT_SSL_VERIFYPEER => FALSE, 	// 信任任何证书
						CURLOPT_SSL_VERIFYHOST => 2			// 检查证书中是否设置域名
// 						CURLOPT_SSL_VERIFYHOST => 1			// libcurl 7.28.1以后已不允许设置为1了
				));
			}
				
			return $this;
		}
		
		/**
		 * 批量配置CURL
		 * @param array $opts
		 * @return \lv\request\Curl
		 */
		public function set(Array $opts)
		{
			$this->opts = $opts + $this->opts;
			return $this;
		}
		
		/**
		 * 修改CURL配置信息
		 * @param int $key
		 * @param mixed $val
		 * @return \lv\request\Curl
		 */
		public function opt($key, $val)
		{
			$this->opts[$key] = $val;
			return $this;
		}
		
		/**
		 * SSL设置CA证书
		 * @param string $path
		 * @return Ambigous <\lv\request\Curl, \lv\request\Curl>
		 */
		public function ca($path)
		{
			return $this->set([
					CURLOPT_SSL_VERIFYPEER => TRUE, 	// 只信任CA颁布的证书
					CURLOPT_CAINFO => $path, 			// CA根证书（用来验证的网站证书是否是CA颁布）
					CURLOPT_SSL_VERIFYHOST => 2			// 检查证书中是否设置域名，并且是否与提供的主机名匹配
			]);
		}
		
		/**
		 * 设置头部请求信息
		 * @param array $set
		 * @param boolean $unshift
		 * @return \lv\request\Curl
		 */
		public function header(Array $set, $unshift = TRUE)
		{
			$header = &$this->opts[CURLOPT_HTTPHEADER];
			$header = $unshift ? ($set + $header) : (array_diff_key($header, $set) + $set);
				
			return $this;
		}
		
		/**
		 * 获取Response Header，如果是批量抓取请慎用
		 * @param \Closure $func
		 * @return void|number|Ambigous <\lv\request\Curl, \lv\request\Curl>
		 */
		public function resHeader(\Closure $func = NULL) 
		{
			$forced = $this->_forced;
			$this->mod(TRUE)->opt(CURLOPT_HEADERFUNCTION, function ($ch, $str) use($func) 
			{
				try 
				{
					if (strstr($str, ':'))
					{
						list ($name, $value) = array_map('trim', explode(':', $str, 2));
						$func(strtolower($name), $value);
					}
					else
					{
						$func($str, NULL);
					}
					
					return strlen($str);
				}
				catch (\Exception $e) 
				{
					return ;
				}
			})->state();
			
			return $this->mod($forced)->opt(CURLOPT_HEADERFUNCTION, NULL);
		}
		
		/**
		 * 获取响应状态，和头部信息
		 * 注意：若是一条新的post请求，请通过post方法中的参数get来抓取头部信息，否则系统默认认为是一条get请求
		 * @param string $key
		 * @param string $name
		 * @example
		 *   (new HTTP(url))->state();				// 获取一条或多条URL的头部信息
		 *   (new HTTP(url))->state(key);			// 获取多条URL中特定某一条的头部信息
		 *   (new HTTP(url))->state(key, name);		// 获取多条URL总特定某一条头部信息中某一类型状态信息
		 *   (new HTTP(url))->state(NULL, name);	// 获取多条URL中每一条指定类型状态信息
		 *   (new HTTP(url))->state()[name];		// 单条URL中建议通过这样的方式获取信息
		 * @return Ambigous <multitype:, unknown>|Ambigous <NULL, unknown>|multitype:
		 */
		public function state($key = NULL, $name = NULL)
		{
			$opt = array(CURLOPT_HEADER => TRUE, CURLOPT_NOBODY => TRUE);
			$state = $this->set($opt)->_get(0)->_select(0);
			
			$this->isMulti() && $key && ($state = isset($state[$key]) ? $state[$key] : array());
			if (!$name) 
			{
				return $state;
			}
			
			$fund = function($info) use($name) 
			{
				return isset($info[$name]) ? $info[$name] : NULL;
			};
			
			return ($key || !$this->_isMulti) ? $fund($state) : array_map($fund, $state);
		}
		
		/**
		 * 获取请求相应编码
		 * @param string $key
		 * @throws \Exception
		 * @return Ambigous <\lv\request\multitype:, \lv\request\Ambigous, NULL, unknown, multitype:>
		 */
		public function code($key = NULL)
		{
			!$this->_isMulti && $key = NULL;
			if (FALSE != ($code = $this->state($key, 'http_code')))
			{
				return $code;
			}
			
			throw new \Exception('获取状态失败');
		}
		
		/**
		 * 获取请求数据的大小
		 * @param string $get	true：下载文件方式来获取(默认); false: 通过头部信息来获取
		 * @param string $key	指定批量下载中某个数据，默认是NULL，为单一数据或者批量下载中所有的数据
		 * @return Ambigous <\lv\request\Ambigous, NULL, unknown, multitype:>
		 */
		public function size($key = NULL)
		{
			$istrun = function($get)
			{
				if ($get) 
				{
					if (is_array($get)) 
					{
						return count(array_filter($get)) == count($get);
					}
					
					return $get;
				}
				
				return FALSE;
			};
			
			$length = function($res) 
			{
				$size = 0;
				array_map(function($info) use(&$size) 
				{
					if (strstr($info, ':'))
					{
						list ($name, $value) = array_map('trim', explode(':', $info, 2));
						'content-length' == strtolower($name) && $size = $value;
					}
				}, 
				explode(PHP_EOL, $res));
				
				return $size;
			};
			
			$get = $this->state($key, 'size_download');
			if (!$istrun($get)) 
			{
				$forced = $this->_forced;
				$state = $this->mod(TRUE)->state();
				
				if ($this->mod($forced)->isMulti()) 
				{
					if (!is_null($key) && isset($state[$key]) && isset($state[$key]['response_header'])) 
					{
						$get = $length($state[$key]['response_header']);
					}
					elseif (is_null($key))
					{
						$get = array_map(function($n) use($length) 
						{
							return isset($n['response_header']) ? $length($n['response_header']) : 0;
						}, 
						$state);
					}
				}
				elseif (isset($state['response_header'])) 
				{
					$get = $length($state['response_header']);
				}
				
				if (!$istrun($get)) 
				{
					$this->data();
					$get = $this->state($key, 'size_download');
				}
			}
			
			return $get;
		}
		
		/**
		 * 是否为批量抓取
		 * @return boolean
		 */
		final public function isMulti() 
		{
			return $this->_isMulti;
		}
		
		/**
		 * 设置是否缓存抓取数据
		 * @param string $set
		 * @return \lv\request\Curl
		 */
		final public function cache($set = TRUE) 
		{
			$this->_isCache = (Bool)$set;
			return $this;
		}
		
		/**
		 * 是否缓存抓取的数据
		 * @return boolean
		 */
		final public function isCache()
		{
			return $this->_isCache;
		}
		
		/**
		 * 清空缓存，每次抓取数据后默认会清空一次
		 * @return \lv\request\Curl
		 */
		final public function flushCache()
		{
			$this->_cache = array('data' => array(), 'state' => array());
			return $this;
		}
		
		/**
		 * 清空暂存
		 * @return \lv\request\Curl
		 */
		final public function flush() 
		{
			self::$_data = array();
			self::$_state = array();
			
			return $this->flushCache();
		}
		
		/**
		 * 设置抓取模式
		 * @param Boolean $forced	true: 强制抓取，是否有缓存数据；false: 如果缓存有，就不再抓取，默认模式
		 * @return \lv\request\Curl
		 */
		final public function mod($forced = FALSE) 
		{
			$this->_forced = (Bool)$forced;
			return $this;
		}
		
		/**
		 * 获取当前请求状态：TRUE为强制请求；FALSE为检索请求
		 * @return boolean
		 */
		final public function getMod()
		{
			return $this->_forced;
		}
		
		/**
		 * 设置post字段，若没有设置则默认为Get请求
		 * @param string|Array $parm
		 * @return Ambigous <\lv\request\Curl, \lv\request\Curl>
		 */
		final protected function field($parm = NULL) 
		{
			$this->_field = array();
			if ($parm) 
			{
				if (is_array($parm)) 
				{
					$this->_field = $parm;
				}
				else 
				{
					$this->opt(CURLOPT_POSTFIELDS, $parm);
				}
				
				return $this->opt(CURLOPT_POST, TRUE);
			} 
			else 
			{
				return $this->set(array(CURLOPT_POST => NULL, CURLOPT_POSTFIELDS => NULL));
			}
		}
		
		/**
		 * 获取数据
		 * @param string $contype	文本类型
		 * @return Ambigous <string, unknown, multitype:NULL Ambigous <string, unknown> number >
		 */
		final protected function data($contype = NULL)
		{
			$type = array('Content-Type' => $contype);
			$opt = array(CURLOPT_HEADER => FALSE, CURLOPT_NOBODY => FALSE);
			
			return $this->set($opt)->header($type)->_get()->_select();
		}
		
		/**
		 * 监听请求
		 * @param str $method
		 * @param mixed $obj
		 * @return \lv\request\Curl
		 */
		public function attachRequest($method, $obj)
		{
			$this->attach(Curl::EVENT, array($obj, $method));
			return $this;
		}
		
		private function _get($type = 1) 
		{
			$url = NULL;
			$fd = array();
			
			$data = $type == 1 ? ($this->_cache['data'] + self::$_data) : ($this->_cache['state'] + self::$_state);
			if ($this->_forced) 
			{
				$url = $this->url;
				$fd = $this->_field;
			}
			else 
			{
				if ($this->_isMulti) 
				{
					$url = array_diff_key(array_flip($this->url), $data);
					$url && $url = array_flip($url);
				}
				else
				{
					if (empty($this->_field)) 
					{
						!isset($data[$this->url]) && $url = $this->url;
					}
					else 
					{
						foreach ($this->_field as $key => $field) 
						{
							if (isset($field[CURLOPT_POSTFIELDS]) && !isset($data[$field[CURLOPT_POSTFIELDS].':'.$this->url])) 
							{
								$fd[$key] = $field;
							}
						}
						
						return $fd ? $this->_multi($this->url, $fd) : $this;
					}
				}
			}

			if ($url) 
			{
				try 
				{
					($this->_isMulti || $fd) ? $this->_multi($url, $fd) : $this->_stream($url);
					$this->dispatch((new CurlEvent(Curl::EVENT))->setMod($type));
				}
				catch (\Exception $e) 
				{
					CurlEvent::data('', TRUE);
				}
			}
			
			return $this;
		}
		
		private function _select($type = 1) 
		{
			$value = array();
			$data = $type == 1 ? ($this->_cache['data'] + self::$_data) : ($this->_cache['state'] + self::$_state);
			if ($this->_isMulti)
			{
				foreach ($this->url as $key => $url)
				{
					$value[$key] = isset($data[$url]) ? $data[$url] : '';
				}
			}
			else
			{
				if (empty($this->_field)) 
				{
					$value = isset($data[$this->url]) ? $data[$this->url] : '';
				}
				else 
				{
					foreach ($this->_field as $key => $field) 
					{
						if (isset($field[CURLOPT_POSTFIELDS]) && isset($data[$field[CURLOPT_POSTFIELDS].':'.$this->url])) 
						{
							$value[$key] = $data[$field[CURLOPT_POSTFIELDS].':'.$this->url];
						}
						else 
						{
							$value[$key] = NULL;
						}
					}
				}
			}
			
			$this->flushCache();
			return $value;
		}
		
		private function _stream($url)
		{
			$ch = curl_init($url);
			curl_setopt_array($ch, $this->_filter());
			
			$data = curl_exec($ch);
			$state = curl_getinfo($ch);
			if (curl_errno($ch))
			{
				throw (new RequestException(curl_error($ch)))->set($this->opts, self::$_state);
			}
			
			curl_close($ch);
			return $this->_save($url, $state, $data);
		}
		
		/**
		 * 通过并行的方式批量获取数据
		 * @param array $option
		 * @return multitype:string
		 */
		private function _multi($url, Array $field = array())
		{
			$curls = array();
			$method = $this->opts[CURLOPT_POST];
			if (FALSE == ($group = $method ? $field : $url)) 
			{
				return $this;
			}

			$mh = curl_multi_init();
			$set = $this->_filter();
			foreach ($group as $key => $opt)
			{
				$ch = $method ? curl_init($url) : curl_init($opt);
				curl_setopt_array($ch, $method ? ($opt + $set) : $set);
					
				$curls[$key] = $ch;
				curl_multi_add_handle($mh, $ch);
			}
			
			do
			{
				$mrc = curl_multi_exec($mh, $still_running);
				curl_multi_select($mh);	// 缓减CPU压力
			}
			while ($still_running);
			
			foreach ($curls as $key => $ch)
			{
				if (FALSE != ($data = curl_multi_getcontent($ch))) 
				{
					if (isset($group[$key])) 
					{
						$point = $group[$key];
						$state = curl_getinfo($ch);
						
						$name = isset($point[CURLOPT_POSTFIELDS]) ? $point[CURLOPT_POSTFIELDS].':'.$url : $point;
						$this->_save($name, $state, $data);
					}
					
					curl_multi_remove_handle($mh, $ch);
					curl_close($ch);
				}
			}
			
			curl_multi_close($mh);
			return $this;
		}
		
		private function _save($key, Array $header, $content) 
		{
			if ($this->_isCache) 
			{
				$data = &self::$_data;
				$state = &self::$_state;
			}
			else 
			{
				$data = &$this->_cache['data'];
				$state = &$this->_cache['state'];
			}
			
			$state[$key] = $header;
			if ($this->opts[CURLOPT_HEADER])
			{
				$this->opts[CURLOPT_NOBODY] && ($state[$key]['response_header'] = $content);
			}
			else
			{
				$data[$key] = $content;
			}
			
			return $this;
		}
		
		private function _filter()
		{
			$data = $this->opts;
			if (isset($data[CURLOPT_HTTPHEADER]))
			{
				$header = array();
				foreach($data[CURLOPT_HTTPHEADER] as $key => $val)
				{
					!is_null($val) && ($header[] = $key.': '.$val);
				}
		
				$data[CURLOPT_HTTPHEADER] = $header ? $header : NULL;
			}
				
			foreach ($data as $key => $val)
			{
				if (is_null($val))
				{
					unset($data[$key]);
				}
			}
			
			return $data;
		}

		private function _checkArrURL(Array $url)
		{
			$num = 0;
			foreach ($url as $link)
			{
				if (0 === strpos($link, $link))
				{
					$num++;
				}
			}
				
			return ($num == count($url)) ? 'https://' : 'http://';
		}
	}
}