<?php
/**
 * FILE_NAME : SplFile.php   FILE_PATH : lv\file\text\SplText
 * 文本操作类，继承自PHP核心类SplFileObject
 * 主要是将父类中设置的方法以及无返回的方法返回当前对象
 *
 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
 * @author Levi
 * @package	lv.file.text.SplText
 * @subpackage
 * @version 2013-06-27
 */
namespace lv\file\text
{
	use \Exception;
	
	class SplFile extends \SplFileObject
	{
		private $_last = 0;
		
		public function fread($start = 0, $length = 0)
		{
			$data = '';
			$this->fseek($start);
			while (FALSE !== ($char = $this->fgetc())) 
			{
				$data .= $char;
				if ($length > 0 && !(--$length))
				{
					break;
				};
			}
			
			return $data;
		}
		
		public function getLast()
		{
			return $this->_last;
		}
		
		public function each($fun)
		{
			$this->rewind();
			while (!$this->eof())
			{
				$fun($this->fgets());
			}
			
			return $this;
		}
		
		public function next()
		{
			parent::next();
			return $this;
		}
		
		public function rewind()
		{
			parent::rewind();
			return $this;
		}
		
		public function seek($line_pos)
		{
			parent::seek($line_pos);
			return $this;
		}
		
		public function setMaxLineLen($max_len)
		{
			parent::setMaxLineLen($max_len);
			return $this;
		}
		
		public function setFlags($flags)
		{
			parent::setFlags($flags);
			return $this;
		}
		
		public function ftruncate($size)
		{
			if (parent::ftruncate($size)) 
			{
				return $this;
			}
			
			throw new Exception('截取文本失败');
		}
		
		public function fflush()
		{
			if (parent::fflush()) 
			{
				return $this;
			}
			
			throw new Exception('刷新文本失败');
		}
		
		public function flock($operation, &$wouldblock = null) 
		{
			if (call_user_func_array(array('parent', 'flock'), func_get_args()))
			{
				return $this;
			}
			
			throw new Exception('文件锁设定失败');
		}
		
		public function fseek($offset, $whence = null)
		{
			if (call_user_func_array(array('parent', 'fseek'), func_get_args()) >= 0)
			{
				return $this;
			}
				
			throw new Exception('文件指针定位失败');
		}
		
		public function fwrite($str, $length = null)
		{
			$this->_last = call_user_func_array(array('parent', 'fwrite'), func_get_args());
			return $this;
		}
		
		public function setCsvControl($delimiter = null, $enclosure = null, $escape = null)
		{
			call_user_func_array(array('parent', 'setCsvControl'), func_get_args());
			return $this;
		}
	}
}