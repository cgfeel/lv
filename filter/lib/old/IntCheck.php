<?php
namespace lv\filter\lib
{
	use lv\filter\Check;
	
	/**
	 * FILE_NAME : IntCheck.php   FILE_PATH : lv/filter/lib/
	 * 检查整型数据类
	 *
	 * @copyright Copyright (c) 2006-2010 mailTo:levi@cgfeel.com
	 * @author Levi
	 * @package	lv.filter.lib.IntCheck
	 * @subpackage Check
	 * @version 2013-04-30
	 */
	class IntCheck extends Check
	{
		/**
		 * 比较数值大小
		 * @param int $min
		 * @param int $max
		 * @return $this
		 */
		public function length($min = NULL, $max = NULL)
		{
			$options = array();
			is_int($min) && $options['min_range'] = $min;
			is_int($max) && $options['max_range'] = $max;
			if ($options && FALSE === filter_var($this->get(0), FILTER_VALIDATE_INT, array('options' => $options)))
			{
				$this->tarp('数值范围错误', 200);
			}
			
			return $this;
		}
	}
}