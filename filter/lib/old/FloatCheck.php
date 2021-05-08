<?php
namespace lv\filter\lib
{
	/**
	 * 浮点数检查、处理
	 * @namespace lv\file
	 * @version 2013-09-29
	 * @author Levi <levi@cgfeel.com>
	 * @name FloatCheck
	 * @copyright Copyright (c) 2006-2013 Levi
	 * @package	lv\filter\Check
	 * @subpackage IntCheck
	 */
	class FloatCheck extends IntCheck
	{
		public function sprintf($num)
		{
			return $this->upVal(sprintf("%.{$num}f", $this->get()));
		}
		
		public function round($num = 0)
		{
			return $this->upVal(round($this->get(), $num));
		}
	}
}