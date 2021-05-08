<?php
namespace lv\filter\lib
{
	use lv\filter\Check;
			
	/**
	 * FILE_NAME : StrCheck.php   FILE_PATH : lv/filter/lib/
	 * 检查字符串数据类
	 *
	 * @copyright Copyright (c) 2006-2010 mailTo:levi@cgfeel.com
	 * @author Levi
	 * @package	lv.filter.lib.StrCheck
	 * @subpackage Check
	 * @version 2013-09-29
	 */
	class StrCheck extends Check
	{
		/**
		 * 比较字符长度
		 * @param number $min
		 * @param number $max
		 * @return \lv\filter\lib\StrCheck
		 */
		public function length($min = 0, $max = 100)
		{
			$len = strlen($this->get());
			if ($len < $min || $len > $max)
			{
				$this->tarp(sprintf('字符长度不能超过%d个字符，也不能低于%d个字符。', $max, $min), 100);
			}
			
			return $this;
		}
		
		public function isstr()
		{
			!is_string($this->get()) && $this->tarp('当前数据不是字符创类型', 101);
			return $this;
		}
		
		/**
		 * 比较两个字符是否一致
		 * @param string $key
		 * @return \lv\filter\lib\StrCheck
		 */
		public function same($key)
		{
			try 
			{
				$str = new StrCheck($this->type(), $key);
				($this->get() != $str->trim()->get()) && $this->tarp('字符不一致', 102);
			}
			catch (\lv\filter\Tarp $e)
			{
				$this->tarp('字符不一致', 102);
			}
			
			return $this;
		}
		
		/**
		 * 去除数据首尾多于的字符
		 * @param string $list
		 * @return Ambigous <\lv\filter\Check, \lv\filter\lib\StrCheck>
		 */
		public function trim($list = NULL)
		{
			return $this->upVal(trim($this->get(), $list));
		}
		
		/**
		 * md5加密
		 * @return Ambigous <\lv\filter\Check, \lv\filter\lib\StrCheck>
		 */
		public function md5()
		{
			return $this->upVal(md5($this->get()));
		}
		
		/**
		 * 转换字符大小写
		 * @param int $num
		 * @return Ambigous <\lv\filter\Check, \lv\filter\lib\StrCheck>
		 */
		public function uc($num)
		{
			$val = (String)$this->get();
			$val = $num == 1 ? strtolower($val) : ($num == 2 ? strtoupper($val) : ucwords($val));
			
			return $this->upVal($val);
		}
		
		/**
		 * 转换HTML编码
		 * @param int $flags
		 * @return Ambigous <\lv\filter\Check, \lv\filter\lib\StrCheck>
		 */
		public function html($flags = '')
		{
			if (empty($flags)) 
			{
				$val = filter_var($this->get(), FILTER_SANITIZE_SPECIAL_CHARS);
			}
			else
			{
				$val = filter_var($this->get(), FILTER_SANITIZE_FULL_SPECIAL_CHARS, array('flags' => $flags));
			}
			
			return $this->upVal($val);
		}
		
		/**
		 * 转换URL编码
		 * @param int $flags
		 * @return Ambigous <\lv\filter\Check, \lv\filter\lib\StrCheck>
		 */
		public function urlEn($flags = FILTER_FLAG_STRIP_LOW)
		{
			$val = filter_var($this->get(), FILTER_SANITIZE_ENCODED, array('flags' => $flags));
			return $this->upVal($val);
		}
		
		/**
		 * 去除html标签
		 * @param int $flags
		 * @return Ambigous <\lv\filter\Check, \lv\filter\lib\StrCheck>
		 */
		public function strip($flags = FILTER_FLAG_NO_ENCODE_QUOTES)
		{
			$val = filter_var($this->get(), FILTER_SANITIZE_STRING, array('flags' => $flags));
			return $this->upVal($val);
		}
		
		/**
		 * 截取字符串
		 * @param number $start
		 * @param int $length
		 * @param number $flag
		 * @return Ambigous <\lv\filter\Check, \lv\filter\lib\StrCheck>
		 */
		public function substr($start, $length, $flag = 1)
		{
			$val = '';
			switch ($flag)
			{
				case 1: $val = substr($this->get(), $start, $length); break;
				case 2: $val = mb_substr($this->get(), $start, $length, CHAR); break;
				case 3: $val = mb_strcut($this->get(), $start, $length, CHAR); break;
			}
			
			return empty($val) ? $this : $this->upVal($val);
		}
	}
}