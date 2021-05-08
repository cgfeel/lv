<?php
namespace lv\file\save
{
	use \Exception;
	use lv\request\HTTP;
		
	class Down extends Attach
	{
		private $_http = NULL;
		
		/**
		 * 下载远程资源到服务器
		 * @param HTTP $get
		 */
		public function __construct(HTTP $get)
		{
			$this->init($get);
		}
		
		public function init(HTTP $get)
		{
			$this->_http = $get;
			return $this;
		}
		
		/**
		 * 限制下载文件格式
		 * @param array $limit
		 * @throws Exception
		 * @return \lv\file\upload\UP
		 */
		public function ext(Array $limit)
		{
			if (!in_array($this->getRealExt(), $limit)) 
			{
				throw new Exception('下载的文件超出了指定范围');
			}
			
			return $this;
		}
		
		/**
		 * 限制下载文件大小，必须在load前执行；也可以在请求的HTTP对象中设置
		 * @param unknown $num
		 * @return \file\upload\Down
		 */
		public function size($num)
		{
			$this->_http->size($num);
			return $this;
		}
		
		/**
		 * 下载文件到临时目录，如果下载的文件不进行save的话，将会作为临时文件
		 * @return \lv\file\upload\Down
		 */
		public function load()
		{
			$this->set(sprintf('attach.tmp.down.%s_%s', time(), mt_rand()), 'tmp')->create();
			file_put_contents($this, $this->_http->get());
			
			return $this;
		}
	}
}