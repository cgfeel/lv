<?php
namespace lv\filter\lib
{
	/**
	 * FILE_NAME : Icon.php   FILE_PATH : lv/filter/lib/
	 * 转换数据编码
	 *
	 * @copyright Copyright (c) 2006 - 2010	mailTo:levi@cgfeel.com
	 * @author Levi 
	 * @package	lv.filter.lib.Icon
	 * @subpackage
	 * @version 2013-08-12
	 */
	class IconvCheck extends StrCheck
	{
		private $_char = array('ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8');
		
		public function get($char = 'UTF-8')
		{
			$encode = $this->getChar();
			!in_array($char, $this->_char) && $char = 'UTF-8';
			
			if ($encode != $char)
			{
				return iconv($encode, strtolower($char).'//TRANSLIT//IGNORE', $this->get());
			}
			
			return $this->get();
		}
		
		public function getChar()
		{
			return mb_detect_encoding($this->get(), $this->_char);
		}
	}
}
?>