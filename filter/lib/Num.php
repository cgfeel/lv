<?php
namespace lv\filter\lib
{
	/**
	 * 数字检查、处理
	 * @namespace lv\file\lib
	 * @version 2013-10-20
	 * @author Levi <levi@cgfeel.com>
	 * @name Num
	 * @copyright Copyright (c) 2006-2013 Levi
	 * @package	lv\filter\lib\Num
	 * @subpackage Check
	 */
	trait Num
	{
		/**
		 * 判断数据类型是否为int型
		 * @return \lv\filter\lib\Num
		 */
		public function isint()
		{
			!is_int($this->get()) && $this->tarp('当前数据不是整数型', 100);
			return $this;
		}

		/**
		 * 判断数据类型是否为浮点型
		 * @return \lv\filter\lib\Num
		 */
		public function isfloat()
		{
			return $this->validate(3);
		}

		/**
		 * 判断数据类型是否为数字
		 * @return \lv\filter\lib\Num
		 */
		public function isnum()
		{
			return $this->validate(4);
		}
		
		/**
		 * 过滤器删除浮点数中所有非法的字符，该过滤器默认允许所有数字以及 +-
		 * @param int $flags
		 *  - FILTER_FLAG_ALLOW_FRACTION	允许小数分隔符，比如：.
		 *  - FILTER_FLAG_ALLOW_THOUSAND	允许千分隔符，比如：,
		 *  - FILTER_FLAG_ALLOW_SCIENTIFIC	允许科学技术法，比如：e 和 E
		 * @example	
		 *  filter_var("5-2f+3.3pp", FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
		 *  5-2+3.3
		 */
		public function floatSz($flags = FILTER_FLAG_ALLOW_FRACTION)
		{
			return $this->sanitize(1, $flags);
		}
		
		/**
		 * 过滤器删除数字中所有非法的字符，该过滤器允许所有数字以及 +-
		 * @example	
		 *  filter_var("5-2+3pp", FILTER_SANITIZE_NUMBER_INT)
		 *  5-2+3
		 */
		public function intSz()
		{
			return $this->sanitize(3);
		}
		
		/**
		 * 比较数值大小
		 * @param int $min
		 * @param int $max
		 * @return \lv\filter\lib\Int
		 */
		public function size($min = NULL, $max = NULL, $flags = NULL)
		{
			$options = array();
			is_int($min) && $options['min_range'] = $min;
			is_int($max) && $options['max_range'] = $max;
			
			return $this->validate(4, $flags, $options);
		}
		
		/**
		 * 转换数据为制定长度的浮点型
		 * @param number $num
		 * @return \lv\filter\lib\Int
		 */
		public function sprintf($num)
		{
			return $this->upVal(sprintf("%.{$num}f", $this->get(1)));
		}
		
		/**
		 * 转换数据为浮点型
		 * @param number $num
		 * @return \lv\filter\lib\Int
		 */
		public function round($num = 0)
		{
			return $this->upVal(round($this->get(1), $num));
		}
	}
}