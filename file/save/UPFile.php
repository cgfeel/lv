<?php
namespace lv\file\save
{
	use lv\filter\Check;
	use lv\file\MiniInfo;
	use lv\request\Header;
		
	class UPFile extends UP 
	{
		private $_header;
		private $_error_messages = array(
			1 => '文件大小超过了系统定义的最大值',
			2 => '文件大小超过了网页表单定义的最大值',
			3 => '文件上传不完全',
			4 => '文件没有上传',
			6 => '附件暂存目录丢失啦，重新试试吧～',
			7 => '无法将文件写入到服务器硬盘',
			8 => '上传过程中被其他扩展中断',
			'post_max_size' => '上传文件超过了系统请求限制',
			'max_file_size' => '文件太大了',
			'min_file_size' => '文件太小',
			'accept_file_types' => '文件类型不允许',
			'max_number_of_files' => '上传文件数量超过限制',
			'max_width' => '图像超过最大宽度',
			'min_width' => '图像未达到指定的最小宽度',
			'max_height' => '图像超过最大高度',
			'min_height' => '图像为达到指定的最小高度'
		);
		
		public function __construct(Array $err = array())
		{
			$this->_header = new Header();
			$err && $this->setErrMsg($err);
		}
		
		public function setErrMsg(Array $msg)
		{
			$this->_error_messages = array_merge($this->_error_messages, $msg);
		}
		
		public function post()
		{
			$this->_checkDel();
		}
		
		protected function init()
		{
			$self = $this;
			new Check(INPUT_SERVER, 'REQUEST_METHOD', function() use ($self)
			{
				switch ($this->get())
				{
					case 'OPTIONS': case 'HEAD': 
						
						break;
					case 'GET': break;
					case 'PATCH': case 'PUT': case 'POST': 
						$self->post();
						break;
						
					case 'DELETE': break;
					default: $this->_header->page(405);
				}
			});
		}
		
		protected function load($name = '')
		{
			$file_name = NULL;
			$content_range = NULL;
			$upload = isset($_FILES[$name]) ? $_FILES[$name] : NULL;
			$check = new Check(INPUT_SERVER, 'HTTP_CONTENT_DISPOSITION', function() use (&$file_name)
			{
				// Parse the Content-Disposition header, if available:
				$file_name = rawurldecode(preg_replace('/(^[^"]+")|("$)/', '', $this->get()));
			});
				
			$check->set('HTTP_CONTENT_RANGE', function() use (&$content_range)
			{
				// Parse the Content-Range header, which has the following form:
				// Content-Range: bytes 0-524287/2000000
				$content_range = preg_split('/[^0-9]+/', $this->get());
			});
			
			$files = array();
			$size = $content_range ? $content_range[3] : NULL;
			if ($upload && is_array($upload['tmp_name'])) 
			{
				// param_name is an array identifier like "files[]",
				// $_FILES is a multi-dimensional array:
				foreach ($upload['tmp_name'] as $index => $value)
				{
					/*
					 * $this->handle_file_upload(
                    $upload['tmp_name'][$index],
                    $file_name ? $file_name : $upload['name'][$index],
                    $size ? $size : $upload['size'][$index],
                    $upload['type'][$index],
                    $upload['error'][$index],
                    $index,
                    $content_range
                );
					 */
				}
			}
		}
		
		protected function handle_file_upload($file, $name, $size, $type, $error, $index = NULL, $range = NULL)
		{
			$file = new \stdClass();
			$file->name = $this->getBasename();
			$file->size = $this->getSize();
			$file->type = (new MiniInfo($this))->mini();
		}
		
		protected function validate($error)
		{
			if ($error) 
			{
				$file->error = isset($this->_error_messages[$error]) ? $this->_error_messages[$error] : $error;
            	return false;
			}
			
			$_SERVER;
		}
		
		private function _checkDel()
		{
			$self = $this;
			new Check(INPUT_REQUEST, '_METHOD', function() use ($self)
			{
				if ($this->get() == 'DELETE')
				{
					// delete...
				}
			});
		}
	}
}