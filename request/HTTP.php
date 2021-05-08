<?php
namespace lv\request
{
	use lv\url\SiteURL;
	use lv\file\Path;
					
	/**
	 * 发起HTTP请求
	 *
	 * @namespace lv\request
	 * @version 2014-07-28
	 * @author Levi <levi@cgfeel.com>
	 * @name HTTP
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	lv\request\HTTP
	 * @subpackage lv\request\Curl
	 */
	class HTTP extends Curl
	{
		private $_request = array
		(
				CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36',
				CURLOPT_HTTPHEADER => 
				[
					'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
					'Accept-Charset' => 'GBK,utf-8;q=0.7,*;q=0.3',
					'Accept-Language' => 'zh-CN,zh;q=0.8',
					'Connection' => 'keep-alive'
				],
				
				CURLOPT_POST => NULL, 								// 暂时默认不是post
				CURLOPT_POSTFIELDS => NULL,
	
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE,		// 0.系统识别, 1.http 1.0, 2.http1.1，仅限curl
				CURLOPT_FOLLOWLOCATION => TRUE,						// 抓取重定向网页(301,302,303,307)
				CURLOPT_MAXCONNECTS => 10,							// 最大抓取次数
				CURLOPT_AUTOREFERER => TRUE,						// 彻底追终重定向网页，仅限curl
			
				CURLOPT_RETURNTRANSFER => TRUE,						// 真：返回字符，否：直接输出
			
				CURLOPT_CONNECTTIMEOUT => 30,						// 连接超时，仅限curl
				CURLOPT_TIMEOUT => 30,								// 数据传输超时
			
				CURLINFO_HEADER_OUT => TRUE,						// 获取header信息
		);
		
		/**
		 * (non-PHPdoc)
		 * @see \lv\request\Curl::init()
		 */
		public function init($url)
		{
			parent::init($url);
			return $this->set($this->_request);
		}
		
		/**
		 * 发起post请求
		 * @param array $fields		请求字段
		 * @param boolean $multi	是否为多次请求
		 * @param string $get		true: 抓取数据，默认抓取数据，false: 只获取头部信息
		 * @return multitype:string |multitype:Ambigous <\lv\request\Ambigous, NULL, unknown, multitype:, string, multitype:NULL Ambigous <string, unknown> multitype: > |Ambigous <\lv\request\Ambigous, NULL, unknown, multitype:, string, multitype:NULL Ambigous <string, unknown> multitype: >
		 */
		public function post(Array $fields, $multi = FALSE, $get = TRUE)
		{
			$parm = $multi ? array_map(function($parm)
			{
				return array(CURLOPT_POSTFIELDS => http_build_query($parm));
			}, $fields) : http_build_query($fields);
			
			if ($this->isMulti()) 
			{
				$post = array();
				$url = $this->url;
				foreach ($url as $key => $link) 
				{
					$this->init($link)->field($parm);
					$post[$key] = $get ? $this->data() : $this->state();
				}
				
				return $post;
			}
			else 
			{
				$this->field($parm);
				return $get ? $this->data() : $this->state();
			}
		}
		
		/**
		 * 发起get请求，返回抓取页面，若只需要头部信息，请使用state
		 * @param array $fields	请求字段
		 * @return Ambigous <\lv\request\Ambigous, string, unknown, multitype:NULL Ambigous <string, unknown> multitype: >
		 */
		public function get(Array $fields = array())
		{
			if ($fields) 
			{
				if ($this->isMulti()) 
				{
					$this->url = array_map(function($link) use($fields)
					{
						return (new SiteURL($link))->set($fields)->get();
					}, 
					$this->url);
				}
				else 
				{
					$this->init((new SiteURL($this->url))->set($fields)->get());
				}
			}
			
			return $this->field()->data();
		}
		
		/**
		 * 限制文件下载大小
		 * @param number $num
		 * @return \lv\request\Curl
		 */
		public function getLimit($num)
		{
			$data = $this->_cache['data'] + self::$_data;
			$action = function($ch, $str) use($num)
			{
				$leng = strlen($str);
				if ($num < strlen(CurlEvent::data($str))) 
				{
					return -1;
				}
				
				return $leng;
			};
			
			if ($this->isMulti())
			{
				$org = $this->url;
				$url = array_flip(array_flip($org));
				foreach ($url as $link)
				{
					if (!isset($data[$link]))
					{
						$data[$link] = $this->init($link)->attachRequest('resize', $this)->opt(CURLOPT_WRITEFUNCTION, $action)->data();
					}
				}
				
				foreach ($org as $key => $link) 
				{
					if (isset($data[$link]) && $data[$link])
					{
						$org[$key] = $data[$link];
					}
					else 
					{
						unset($org[$key]);
					}
				}
				
				return $org;
			}
			else 
			{
				if (isset($data[$this->url]))
				{
					return $data[$this->url];
				}
				
				return $this->opt(CURLOPT_WRITEFUNCTION, $action)->data();
			}
		}
		
		/**
		 * 发起上传文件请求
		 * @param array $field
		 * @return mixed
		 */
		public function upload(Array $field)
		{
			if ($this->isMulti()) 
			{
				$data = array();
				$url = $this->url;
				
				foreach ($url as $key => $link) 
				{
					$data[$key] = $this->init($link)->_upRequest($field);
				}
				
				return $data;
			}
			else 
			{
				return $this->_upRequest($field);
			}
		}
		
		/**
		 * 获取监听数据
		 */
		protected function resize() 
		{
			// 根据缓存设置将数据保存到指定位置
			if ($this->isCache()) 
			{
				self::$_data[$this->url] = CurlEvent::data();
			}
			else 
			{
				$this->_cache['data'][$this->url] = CurlEvent::data();
			}
			
			CurlEvent::data('', TRUE);
		}
		
		private function _upRequest(Array $field) 
		{
			$boundary = uniqid('------------------');
			$content_type = 'multipart/form-data; boundary='.$boundary;
			
			$MPboundary = '--'.$boundary;
			$endMPboundary = $MPboundary. '--';
			$multipartbody = '';
			
			foreach ($field as $parameter => $value)
			{
				$multipartbody .= $MPboundary.PHP_EOL;
				if ($value instanceof Path)
				{
					$target = $value->get();
					$name = $value->getBasename('.'.$value->getExtension());
						
					// attach/test/u.txt  empty txt was error
					$multipartbody .= sprintf('Content-Disposition: form-data; name="%s"; filename="%s"%s', $parameter, $name, PHP_EOL);
					$multipartbody .= sprintf("Content-Type: %s%s", (new \finfo(FILEINFO_MIME))->file($target), PHP_EOL.PHP_EOL);
					$multipartbody .= file_get_contents($target).PHP_EOL;
				}
				else
				{
					$multipartbody .= sprintf('content-disposition: form-data; name="%s"%s', $parameter, PHP_EOL.PHP_EOL);
					$multipartbody .= $value.PHP_EOL;
				}
			}
			
			$quote = array(CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $multipartbody.$endMPboundary);
			return $this->set($quote)->data($content_type);
		}
	}
}