<?php
namespace lv\filter\lib
{
	use lv\filter\Check;
	
	/**
	 * 字符串检查、处理
	 * @namespace lv\file\lib
	 * @version 2013-10-20
	 * @author Levi <levi@cgfeel.com>
	 * @name Str
	 * @copyright Copyright (c) 2006-2013 Levi
	 * @package	lv\filter\lib\Str
	 * @subpackage Check
	 */
	trait Str 
	{
		/**
		 * 判断数据类型是否为字符型
		 * @return \lv\filter\lib\Str
		 */
		public function isstr()
		{
			!is_string($this->get()) && $this->tarp('当前数据不是字符创类型', 200);
			return $this;
		}
		
		/**
		 * 比较字符长度
		 * @param number $min
		 * @param number $max
		 * @return \lv\filter\lib\Str
		 */
		public function length($min = 0, $max = 100)
		{
			$len = strlen($this->get(2));
			if ($len < $min || $len > $max)
			{
				$this->tarp(sprintf('字符"%s"长度不能超过%d个字符，也不能低于%d个字符。', $this->key(), $max, $min), 201);
			}
				
			return $this;
		}
		
		/**
		 * 比较两个字符是否一致
		 * @param string $key
		 * @return \lv\filter\lib\Str
		 */
		public function same($key)
		{
			$str = new Check($this->type(), $key);
			($this->get() != $str->trim()->get(2)) && $this->tarp('字符不一致', 202);
			
			return $this;
		}
		
		/**
		 * 转换时间戳为数字格式
		 * @return \lv\filter\lib\Str
		 */
		public function strtotime() 
		{
			(FALSE == ($data = strtotime($this->get()))) && $this->tarp(sprintf('字符%s不是有效时间格式'));
			return $this->upVal($data);
		}
		
		/**
		 * 去除数据首尾多于的字符
		 * @param string $list
		 * @return \lv\filter\lib\Str
		 */
		public function trim($list = NULL)
		{
			return $this->upVal(trim($this->get(2), $list));
		}
		
		/**
		 * md5加密
		 * @return \lv\filter\lib\Str
		 */
		public function md5()
		{
			return $this->upVal(md5($this->get(2)));
		}
		
		/**
		 * 转换字符大小写
		 * @param int $num
		 * @return \lv\filter\lib\Str
		 */
		public function uc($num = 0)
		{
			$val = $this->get(2);
			$val = $num == 1 ? strtoupper($val) : ($num == 2 ? ucwords($val) : strtolower($val));
			
			return $this->upVal($val);
		}
		
		/**
		 * 转换HTML编码
		 * @param int $type
		 * @param int $flags
		 *  - FILTER_FLAG_STRIP_LOW
		 *  - FILTER_FLAG_STRIP_HIGH
		 *  - FILTER_FLAG_ENCODE_HIGH
		 *  - FILTER_FLAG_NO_ENCODE_QUOTES
		 * @example	
		 *  filter_var('Is Peter <smart> & funny?', FILTER_SANITIZE_SPECIAL_CHARS)
		 *  Is Peter &#60;smart&#62; &#38; funny?
		 *  
		 *  filter_var('Is Peter <smart> & funny?', FILTER_SANITIZE_FULL_SPECIAL_CHARS)
		 *  Is Peter &lt;smart&gt; &amp; funny?
		 */
		public function htmlSz($type = 1, $flags = '')
		{
			switch ($type)
			{
				case 1: return $this->sanitize(8, $flags);
				case 2: return $this->sanitize(10, $flags);
				case 3: return $this->upVal($this->get(2), ENT_QUOTES);
			}
		}
		
		/**
		 * 从数组中替换数据，例如当前值为'a'，数组为array('a' => 'def')，则用'def'替换当前数据
		 * @param array $arr
		 * @param string $def
		 * @return \lv\filter\lib\Str
		 */
		public function strReKey(Array $arr, $def = '__check_set_def') 
		{
			$data = $this->get();
			return $this->upVal(isset($arr[$data]) ? $arr[$data] : ($def == '__check_set_def' ? $data : $def));
		}
		
		/**
		 * 过滤器对字符串执行 addslashes() 函数，预定义字符前添加反斜杠
		 * @param int $flags
		 * @example	
		 *  filter_var("Peter's here!", FILTER_SANITIZE_ENCODED)
		 *  Peter\'s here!
		 */
		public function quoteSz($flags = NULL)
		{
			return $this->sanitize(6, $flags);
		}
		
		/**
		 * 过滤去除html标签
		 * @param int $flags
		 *  - NULL							默认剔除
		 *  - FILTER_FLAG_NO_ENCODE_QUOTES	该标志不编码引号
		 *  - FILTER_FLAG_STRIP_LOW			去除 ASCII 值在 32 以下的字符
		 *  - FILTER_FLAG_STRIP_HIGH		去除 ASCII 值在 32 以上的字符
		 *  - FILTER_FLAG_ENCODE_LOW		编码 ASCII 值在 32 以下的字符
		 *  - FILTER_FLAG_ENCODE_HIGH		编码 ASCII 值在 32 以上的字符
		 *  - FILTER_FLAG_ENCODE_AMP		把 & 字符编码为 &amp;
		 * @example	
		 *  filter_var("<b>Bill Gates<b>", FILTER_SANITIZE_ENCODED)
		 *  Bill Gates
		 */
		public function sanitize($type = 1, $flags = NULL)
		{
			return $this->sanitize(12, $flags);
		}
		
		/**
		 * 截取字符串
		 * @param int $start
		 * @param int $length
		 * @param int $flag
		 * @return \lv\filter\lib\Str
		 */
		public function substr($start, $length, $flag = 0)
		{
			$val = $this->get(2);
			switch ($flag)
			{
				case 1: $val = mb_substr($val, $start, $length, CHAR); break;
				case 2: $val = mb_strcut($val, $start, $length, CHAR); break;
				default: $val = substr($val, $start, $length); break;
			}
			
			return empty($val) ? $this : $this->upVal($val);
		}
	}
}