<?php
namespace lv\session
{
	/**
	 * FILE_NAME : Sess.php   FILE_PATH : lv/session/
	 * session操作
	 *
	 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
	 * @author Levi
	 * @package
	 * @subpackage
	 * @version 2013-05-01
	 */
	class Sess
	{
		const YEAR = 31536000;
		const MONTH = 2592000;
		const WEEK = 604800;
		const DAY = 86400;
				
		protected static $_sess = NULL;
		
		private function __construct($expire)
		{
			if ($expire) 
			{
				ini_set('session.gc_maxlifetime', $expire);
			} 
			else 
			{
				$expire = ini_get('session.gc_maxlifetime');
			}
			
			if (empty($_COOKIE['PHPSESSID'])) 
			{
				session_set_cookie_params($expire);
				session_start();
			} 
			else 
			{
				session_start();
				setcookie('PHPSESSID', session_id(), time() + $expire);
			}
		}
		
		public static function start($time = Sess::WEEK)
		{
			!(self::$_sess instanceof self) && self::$_sess = new Sess($time);
			return self::$_sess;
		}
		
		public static function close()
		{
			self::start();
			
			session_unset();
			session_destroy();
			
			self::$_sess = NULL;
		}
		
		/**
		 * 设置session存放地址，可以是redis，也可以是物理路径
		 * @param string $path
		 */
		public static function setPath($path = 'tcp://127.0.0.1:6379')
		{
			session_save_path($path);
		}
		
		public static function get($path)
		{
			$value = $_SESSION;
			$arg = func_get_args();
			$path = explode('.', $path);
			foreach ($path as $key)
			{
				if (!is_array($value) || !isset($value[$key])) return NULL;
				$value = $value[$key];
			}
			
			return isset($arg[1]) ? (bool)($value == $arg[1]) : $value;
		}
	}
}