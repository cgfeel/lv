<?php
namespace lv\mysql
{
	use mysqli;
	use Exception;
	use data\DataBase;
	
	/**
	 * 链接数据库
	 * @namespace lv\mysql
	 * @version 2014-08-10
	 * @author Levi <levi@cgfeel.com>
	 * @name DB
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	lv\mysql
	 * @subpackage \mysqli
	 */
	class DB extends mysqli
	{
		protected static $_db = NULL;
		
		private function __construct()
		{
			$this->init();
			
	//		parent::options(MYSQLI_INIT_COMMAND, "SET AUTOCOMMIT=0");
	//		parent::options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
			
			if (!$this->real_connect(DataBase::HOST, DataBase::USER, DataBase::PWD, DataBase::NAME, DataBase::POST)) 
			{
				throw new Exception(sprintf('Connect Error (%s): %s', $this->errno, $this->error));
			}
			else
			{
				if (parent::query('set names '.DataBase::CHARSET) === FALSE)
				{
					$err = sprintf
					(
						'数据库编码错误，请联系管理员解决此类问题。<br /><strong>%d_%s</strong>', 
						$this->errno, $this->error
					);
					
					throw new Exception($err);
				}
			}
		}
		
		protected static function create()
		{
			!(self::$_db instanceof self) && self::$_db = (Object)new DB();
		}
		
		final public function __clone() { throw new Exception('Conn禁止克隆。'); }
		
		/**
		 * 执行SQL，返回结果集对象
		 * @param string $sql
		 * @throws Exception
		 * @return \lv\mysql\result
		 */
		public static function exec($sql)
		{
			self::create();
			strstr($sql, '#@__') && $sql = str_replace('#@__', DataBase::PREFIX, $sql);
			if (($result = self::$_db->query($sql)) != FALSE)
			{
				return is_bool($result) ? $result : new result($result);
			}
			
			throw new Exception('SQL错误：('.self::$_db->errno.')'.self::$_db->error);
		}
		
		/**
		 * Mysql事物
		 * @param array $sql
		 * @throws Exception
		 * @return mixed
		 */
		public static function tran(\Closure $call)
		{
			self::create();
			self::$_db->query('begin');
			try 
			{
				$call();
			}
			catch (\Exception $e)
			{
				self::$_db->query('rollback');
				throw new Exception($e->getMessage());
			}
			
			return self::$_db->query('commit');
		}
		
		public static function call($func)
		{
			$result = self::exec($func());
			$result->sql = $func;
			
			return $result;
		}
		
		public static function stmt($sql)
		{
			!(self::$_db instanceof self) && self::$_db = (Object)new DB();
			strstr($sql, '#@__') && $sql = str_replace('#@__', DB::$prefix, $sql);
			
			if (($stmt = self::$_db->prepare($sql)) != FALSE)
			{
				return (Object)$stmt;
			}
			else throw new Exception(self::$_db->error);
		}
		
		public static function closed()
		{
			self::$_db->close();
		}
		
		public static function inset_id()
		{
			return (Int)self::$_db->insert_id;
		}
		
		public static function affected_rows()
		{
			return (Int)self::$_db->affected_rows;
		}
		
		public static function errno() 
		{
			return (Int)self::$_db->errno;
		}
	}
}