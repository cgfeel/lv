<?php
namespace lv\file\down
{
	use \finfo;
	use \Exception;
	use lv\file\text\SplFile;
	use lv\request\Header;
		
	class To extends SplFile
	{
		private $_end = 0, $_start = 0, $_size = 0;
		private $_partial = FALSE;
		private $_header;
		
		public function __construct()
		{
			call_user_func_array(array('parent', '__construct'), func_get_args());
			if ($this->isFile())
			{
				$this->_init();
				$this->_setPartial();
				
				$opt = array(
					/*
					 * X-Content-Type-Options：IE和chrome中的一个非标准头域，只有一个值：nosniff；
					 * 通常针对一个请求的响应的头域中有content-type头域来描述内容的MIME类型，
					 * 但有些内容并没有提供其MIME类型，这时候浏览器可以自己探测该内容是什么类型，
					 * 而X-Content-Type-Options:nosniff正是用于关闭自动嗅探功能
					 */
					'X-Content-Type-Options' => 'nosniff',
					'Content-Transfer-Encoding' => 'binary',
					'Content-Encoding' => 'none'
				);
				
				$this->_header->opt($opt)->noCache()->init();
				$this->_header->set('Cache-Control', array('private', FALSE));
				
// 				header('Pragma: public');
// 				header('Expires: 0');
// 				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
// 				header('Cache-Control: private', false);
// 				header('Content-Transfer-Encoding: binary');
// 				header('Content-Encoding: none');
			}
			else
			{
				throw new Exception('不是有效的文件');
			}
		}
		
		public function down($name = '')
		{
			$name = empty($name) ? $this->getBasename() : $name.'.'.$this->getExtension();
			
			// Send standard headers
			$opt = array(
				'Content-Type' => (new finfo(FILEINFO_MIME))->file($this->getRealPath()),
				
				//  attachment表示以附件方式下载；如果要在页面中打开，则改为inline
				'Content-Disposition' => sprintf('attachment; filename="%s";', $name),
				'Content-Length' => $this->_size,
				'Accept-Ranges' => 'bytes'
			);
			
			$this->_header->opt($opt)->init();
// 			header('Content-Type: '.(new finfo(FILEINFO_MIME))->file($this->getRealPath()));
// 			header('Content-Length: '.$this->_size);
			
			
			/*
			 * attachment表示以附件方式下载；
			 * 如果要在页面中打开，则改为inline
			 */
// 			header('Content-Disposition: attachment; filename="'.$name.'";');
// 			header('Accept-Ranges: bytes');
			
			if ($this->_partial)
			{
				
// 				header('HTTP/1.1 206 Partial Content');
// 				header('Content-Range: bytes '.($this->_start-$this->_end/$this->_size));
				if (!$fp = fopen($this->getRealPath(), 'r'))
				{
					// Error out if we can't read the file
					$this->_header->page(500);
// 					header("HTTP/1.1 500 Internal Server Error");
					exit;
				}
				
				$this->_header->page(206)->set('Content-Range', 'bytes '.($this->_start-$this->_end/$this->_size))->init();
				$length = $this->_end - $this->_start;
				$this->_start && fseek($this->_start);
				while ($length > 0)
				{
					// Read in blocks of 8KB so we don't chew up memory on the server
					$read = ($length > 8192) ? 8192 : $length;
					$length -= $read;
				
					print(fread($fp, $read));
				}
				
				fclose($fp);
			}
			else 
			{
				while (!$this->eof()) 
				{
					echo $this->fgets();
				}
			}
		}
		
		private function _init()
		{
			$this->_header = new Header();
			if (header_remove()) 
			{
				throw new Exception('Header Sent');
			}
			
			ini_get('zlib.output_compression') && ini_set('zlib.output_compression', 'off');
		}
		
		private function _getRange()
		{
			if (isset($_SERVER['HTTP_RANGE'])) 
			{
				// IIS、Some Apache versions
				return $_SERVER['HTTP_RANGE'];
			}
			elseif (function_exists('apache_request_headers') && FALSE != ($apache = call_user_func('apache_request_headers')))
			{
				foreach ($apache as $header => $val)
				{
					if (strtolower($header) == 'range')
					{
						return $val;
					}
				}
			}
			
			return NULL;
		}
		
		private function _setPartial()
		{
			$this->_size = $this->getSize();
			if (FALSE != ($range = $this->_getRange()))
			{
				$this->_partial = TRUE;
					
				list($param, $range) = explode('=', $range);
				if (strtolower(trim($param)) != 'bytes')
				{
					// Bad request - range unit is not 'bytes'
					$this->_header->page(400);
// 					header("HTTP/1.1 400 Invalid Request");
					exit;
				}
					
				$range = explode(',', $range);
				$range = explode('-', $range[0]);    // We only deal with the first requested range
				if (count($range) != 2)
				{
					// Bad request - 'bytes' parameter is not valid
					$this->_header->page(400);
// 					header("HTTP/1.1 400 Invalid Request");
					exit;
				}
					
				if ($range[0] === '')
				{
					// First number missing, return last $range[1] bytes
					$this->_end = $this->_size - 1;
					$this->_start = $this->_end - intval($range[0]);
				}
				elseif ($range[1] === '')
				{
					// Second number missing, return from byte $range[0] to end
					$this->_start = intval($range[0]);
					$this->_end = $this->_size - 1;
				}
				else
				{
					// Both numbers present, return specific range
					$this->_start = intval($range[0]);
					$this->_end = intval($range[1]);
					if ($this->_end >= $this->_size || (!$this->_start && (!$this->_end || $this->_end == ($this->_size - 1))))
					{
						// Invalid range/whole file specified, return whole file
						$this->_partial = FALSE;
					}
				}
			}
		}
	}
}