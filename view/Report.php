<?php
namespace lv\view
{
	/**
	 * 错误提示、调试工具
	 *
	 * @namespace lv\view
	 * @version 2013-12-29
	 * @author Levi <levi@cgfeel.com>
	 * @name Report
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	lv\view\Report
	 * @subpackage lv\view\Sprite
	 */
	class Report extends Sprite
	{
		public static $tmp = 'template\default\CallBack';
		public $log = '';
		
		/**
		 * 设置日志数据
		 * @param string $log
		 */
		public function __construct($log)
		{
			$this->log = $log;
		}
		
		public function page($tmp = '')
		{
			$this->add(empty($tmp) ? self::$tmp : $tmp)->display();
		}
		
		public function put($isExit = TRUE)
		{
			parent::set($this->log)->display($isExit);
		}
		
		public function dump($isExit = TRUE)
		{
			ob_start();
			var_dump($this->log);
			$data = ob_get_clean();
			$data = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $data);
			
			$this->log = '<pre>'.$data.'</pre>';
			$this->put($isExit);
		}
		
		public function pre($isExit = TRUE)
		{
			ob_start();
			print_r($this->log);
			$data = ob_get_clean();
			
			$this->log = '<pre>'.$data.'</pre>';
			$this->put($isExit);
		}
	}
}