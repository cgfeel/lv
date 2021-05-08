<?php
namespace lv\session
{
	use lv\mysql\DB;

	/**
	 * FILE_NAME : SessHandler.php   FILE_PATH : lv/session/
	 * Session入库操作
	 *
	 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
	 * @author Levi
	 * @package	lv.session.SessHandler
	 * @subpackage
	 * @version 2013-04-30
	 */
	class SessDB extends Sess
	{
		private $_time = 0;
		private $_table = '`sess`';
		
		private function __construct($time)
		{
			$this->_time = $time > 0 ? $time : get_cfg_var('session.gc_maxlifetime');
			session_set_save_handler
			(
				array($this, 'open'), 
				array($this, 'close'), 
				array($this, 'select'), 
				array($this, 'write'), 
				array($this, 'destroy'), 
				array($this, 'gc')
			);
			
			session_start();
		}
		
		public static function start($time = Sess::WEEK)
		{
			!(self::$_sess instanceof self) && self::$_sess = new SessDB($time);
			return self::$_sess;
		}
		
		public static function setPath(){}
		
		public function select($id)
		{
			$sql = "SELECT `value` FROM #@__%s WHERE `id` = '%s' AND `expiration` > %d;";
			$result = DB::exec(sprintf($sql, $this->_table, $id, time()))->get();
			
			return $result ? $result['value'] : '';
		}
		
		public function write($id, $value)
		{
			if (empty($value)) return ;
			
			$time = time() + $this->_time;
			$sql = "INSERT INTO #@__%s VALUES ('%s', '%d', '%s') ON DUPLICATE KEY UPDATE `expiration` = '%3\$d', `value` = '%4\$s';";
			
			DB::exec(sprintf($sql, $this->_table, $id, $time, $value));
			return DB::affected_rows();
		}
		
		public function destroy($id)
		{
			$sql = "DELETE FROM #@__%s WHERE `id` = '%s';";
			DB::exec(sprintf($sql, $this->_table, $id));
			
			return DB::affected_rows();
		}
		
		public function gc()
		{
			$time = time() - $this->_time;
			$sql = "DELETE FROM #@__%s WHERE  `expiration` < %d;";
			
			DB::exec(sprintf($sql, $this->_table, $time));
			return DB::affected_rows();
		}
	}
}