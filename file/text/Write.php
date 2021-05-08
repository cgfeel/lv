<?php
namespace lv\file\text
{
	/**
	 * 文本写入操作，注意：
	 *   - 写入数据模式：w, a, c
	 *   - 读写数据模式：w+, a+, c+
	 *   
	 * @namespace lv\file\text
	 * @version 2013-06-29
	 * @author Levi <levi@cgfeel.com>
	 * @name Write
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	lv\file\text\Write
	 * @subpackage lv\file\text\SplFile
	 */
	class Write extends SplFile
	{
		/**
		 * 以写入或读写方式打开文件
		 */
		public function __construct()
		{
			$args = func_get_args();
			$mode = isset($args[1]) ? $args[1] : 'w+';
			
			$args[1] = $mode == 'r' ? 'w+' : $mode;
			call_user_func_array(array('parent', '__construct'), $args);
		}
		
		
		/**
		 * 插入数据
		 * @param string $data
		 * @param int $start
		 * @param string $type
		 * @return \lv\file\text\Write
		 */
		public function wseek($data, $start = 0, $type = SEEK_CUR)
		{
			return $this->fseek($start, $type)->fwrite($data);
		}
		
		/**
		 * 写入生成php数组配置文件
		 * @param array $data
		 * @return \lv\file\text\Write
		 */
		public function php(Array $data)
		{
			$var = sprintf('<?php%sreturn %s;%1$s?>', PHP_EOL, var_export($data, TRUE));
			return $this->fwrite($var);
		}
	}
}