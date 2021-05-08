<?php
namespace lv\filter\lib
{	
	/**
	 * FILE_NAME : StrCheck.php   FILE_PATH : lv/filter/lib/
	 * 检查地址
	 *
	 * @copyright Copyright (c) 2006-2010 mailTo:levi@cgfeel.com
	 * @author Levi
	 * @package	lv.filter.lib.UrlCheck
	 * @subpackage StrCheck
	 * @version 2013-04-30
	 */
	class UrlCheck extends StrCheck
	{
		/**
		 * 检查URL地址
		 * @param int $flags
		 * @return Ambigous <\lv\filter\lib\UrlCheck, \lv\filter\Check, \lv\filter\lib\UrlCheck>
		 */
		public function url($flags = NULL)
		{
			$set = $flags ? array('flags' => $flags) : NULL;
			if (FALSE === ($val = filter_var($this->get(), FILTER_VALIDATE_URL, $set)))
			{
				$this->tarp('不是正确的URL地址。', 110);
			}
			
			return is_null($val) ? $this : $this->upVal($val);
		}
		
		/**
		 * 解析URL
		 * @param int $component    获取 URL 中指定的部分
		 * @return Ambigous <\lv\filter\Check, \lv\filter\lib\UrlCheck>
		 */
		public function parse_url($component = -1)
		{
			$val = parse_url($this->get(), $component);
			return $this->upVal($val);
		}
		
		/**
		 * 检查IP地址
		 * @param int $flags
		 * @return \lv\filter\lib\UrlCheck
		 */
		public function ip($flags = NULL)
		{
			$set = $flags ? array('flags' => $flags) : NULL;
			if (FALSE === (filter_var($this->get(), FILTER_VALIDATE_IP, $set)))
			{
				$this->tarp('不是正确的IP地址。', 111);
			}
			
			return $this;
		}
		
		/**
		 * 检查邮箱地址
		 * @return \lv\filter\lib\UrlCheck
		 */
		public function mail()
		{
			if (FALSE === (filter_var($this->get(), FILTER_VALIDATE_EMAIL)))
			{
				$this->tarp('邮箱地址不正确。', 112);
			}
			
			return $this;
		}
	}
}