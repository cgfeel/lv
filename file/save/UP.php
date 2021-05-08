<?php
namespace lv\file\save
{
	use Exception;
	use lv\file\Path;
			
	/**
	 * 获取上传文件
	 * @namespace lv\file\upload
	 * @version 2013-07-10
	 * @author Levi <levi@cgfeel.com>
	 * @name UP
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	lv\file\Path
	 * @subpackage lv\file\upload\UP
	 */
	class UP extends Attach
	{
		/**
		 * 捕获上传的文件
		 * @param String $name
		 * @throws Exception
		 */
		public function __construct($name = '')
		{
			$this->load($name);
		}
		
		/**
		 * 限制上传文件格式
		 * @param array $limit
		 * @return \lv\file\save\UP
		 */
		public function ext(Array $limit)
		{
			if (!in_array($this->getRealExt(), $limit)) 
			{
				throw new \Exception('上传的文件格式超出指定范围');
			}
			
			return $this;
		}
		
		/**
		 * 限制上传文件大小
		 * @param unknown $num
		 * @throws Exception
		 * @return \lv\file\upload\UP
		 */
		public function size($num)
		{
			if ($this->getSize() > $num) 
			{
				throw new Exception('上传的文件超出了指定大小');
			}
			
			return $this;
		}
		
		/**
		 * 拷贝上传文件到临时目录
		 * @param string $name
		 * @throws Exception
		 * @return \lv\file\upload\UP
		 */
		public function load($name = '')
		{
			$this->set('attach\tmp\upload');
			$html5 = isset($_SERVER['HTTP_CONTENT_DISPOSITION']) ? $_SERVER['HTTP_CONTENT_DISPOSITION'] : NULL;
			$pattem = '/attachment;\s+name="(.+?)";\s+filename="(.+?)"/i';
			
			if ($html5 && preg_match($pattem, $html5, $info))
			{
				// HTML5上传
				file_put_contents($this->__toString(), file_get_contents("php://input"));
			}
			else
			{
				if (!isset($_FILES[$name]))
				{
					throw new Exception('文件域的name错误');
				}
				
				$upfile = $_FILES[$name];
				if (!empty($upfile['error']))
				{
					echo $name."<br />";
					echo $upfile['error'];
					exit;
					switch ($upfile['error'])
					{
						case 1: throw new Exception('文件大小超过了系统定义的最大值');
						case 2: throw new Exception('文件大小超过了HTML定义的最大值');
						case 3: throw new Exception('文件上传不完全');
						case 4: throw new Exception('没有文件上传');
						case 6: throw new Exception('找不到临时文件夹');
						case 7: throw new Exception('文件写入失败');
						case 8: throw new Exception('上传被其他扩展中断');
						default: throw new Exception('上传文件无有效错误代码：'.$upfile['error']);
					}
				}
				
				if (empty($upfile['tmp_name']) || $upfile['tmp_name'] == 'none')
				{
					throw new Exception('没有文件上传。');
				}
				
				$this->create()->move((new Path())->cd($upfile['tmp_name']));
			}
			
			return $this;
		}
	}
}