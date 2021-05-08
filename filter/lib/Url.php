<?php
namespace lv\filter\lib
{
	/**
	 * url检查、处理
	 * @namespace lv\file\lib
	 * @version 2013-10-20
	 * @author Levi <levi@cgfeel.com>
	 * @name Str
	 * @copyright Copyright (c) 2006-2013 Levi
	 * @package	lv\filter\lib\Url
	 * @subpackage Check
	 */
	
	trait Url
	{
		/**
		 * 过滤器删除字符串中所有非法的 e-mail 字符
		 * @example	
		 *  filter_var('some(one)@exa\\mple.com', FILTER_SANITIZE_ENCODED)
		 *  someone@example.com
		 */
		public function mailSz()
		{
			return $this->sanitize(2);
		}
		
		/**
		 * 过滤器去除或 URL 编码不需要的字符
		 * @param int $flags
		 *  - NULL						默认
		 *  - FILTER_FLAG_STRIP_LOW		去除 ASCII 值在 32 以下的字符
		 *  - FILTER_FLAG_STRIP_HIGH	去除 ASCII 值在 32 以上的字符
		 *  - FILTER_FLAG_ENCODE_LOW	编码 ASCII 值在 32 以下的字符
		 *  - FILTER_FLAG_ENCODE_HIGH	编码 ASCII 值在 32 以上的字符
		 * @example	
		 *  filter_var('http://www.ex.com', FILTER_SANITIZE_ENCODED)
		 *  http%3A%2F%2Fwww.ex.com
		 */
		public function encodeSz($flags = NULL)
		{
			return $this->sanitize(4, $flags);
		}
		
		/**
		 * 解码URL
		 * @return \lv\filter\lib\Url
		 */
		public function decode()
		{
			return $this->upVal(urldecode($this->get(2)));
		}
		
		/**
		 * 检查URL地址
		 * @param int $flags
		 *  - NUL							默认
		 *  - FILTER_FLAG_SCHEME_REQUIRED	要求 URL 是 RFC 兼容 URL，比如：http://example
		 *  - FILTER_FLAG_HOST_REQUIRED		要求 URL 包含主机名，比如：http://www.example.com
		 *  - FILTER_FLAG_PATH_REQUIRED		要求 URL 在主机名后存在路径，比如：eg.com/example1/
		 *  - FILTER_FLAG_QUERY_REQUIRED	要求 URL 存在查询字符串，比如：eg.php?age=37
		 * @return \lv\filter\lib\Url
		 */
		public function isurl($flags = NULL)
		{
			return $this->sanitize(7, $flags);
		}
		
		public function urlSz()
		{
			return $this->sanitize(16);
		}
		
		/**
		 * 解析URL，将URL各个部分分段成Array
		 * @param int $component    获取 URL 中指定的部分
		 * @return \lv\filter\lib\Url
		 */
		public function parse_url($component = -1)
		{
			$val = parse_url($this->get(2), $component);
			return $this->upVal($val);
		}
	}
}