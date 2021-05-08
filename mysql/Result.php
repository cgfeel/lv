<?php
namespace lv\mysql
{
	use lv\call\Merge;
	
	/**
	 * FILE_NAME : result.php   FILE_PATH : lv/mysql/
	 * 结果集对象，不能单独使用
	 *
	 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
	 * @author Levi
	 * @package	lv.mysql.Result
	 * @subpackage
	 * @version 2013-04-30
	 */
	class Result extends Merge
	{
		public function each($func)
		{
			if ($this->num_rows)
			{
				while (FALSE != ($row = $this->fetch_assoc()))
				{
					$func($row);
				}
			}
		}
		
		public function get()
		{
			return $this->num_rows ? $this->fetch_assoc() : FALSE;
		}
		
		public function back($func)
		{
			$do = FALSE;
			$this->each(function ($row) use ($do, $func)
			{
				!$do && $do = $func($row);
			});
			
			$do && DB::call($this->sql)->back();
		}
	}
}