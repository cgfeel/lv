<?php
namespace lv\file\save
{
	use lv\file\Path;
	use lv\mysql\DB;
	
	/**
	 * 清理上传文件
	 * @namespace lv\file\upload
	 * @version 2013-07-10
	 * @author Levi <levi@cgfeel.com>
	 * @name UPFilp
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	
	 * @subpackage lv\file\upload\UPFilp
	 */
	class UPFilp
	{
		private $_path = [];
		private $_data = [];
		
		private $_filp = 0;
		
		/**
		 * 清理文件
		 */
		public function __construct()
		{
			$this->_select();
			foreach ($this->_path as $map => &$path)
			{
				array_shift($path);
				$this->_del($path, $map);
			}
			
			if ($this->_data) 
			{
				$id = implode(' OR `id`=', $this->_data);
				$sql = sprintf('DELETE FROM `#@__attach` WHERE (`id`=%s);', $id);
				
				DB::exec($sql);
				$this->_filp = DB::affected_rows();
			}
		}
		
		/**
		 * 获取清理的文件列表
		 * @return multitype:
		 */
		public function get()
		{
			return $this->_path;
		}
		
		/**
		 * 获取清理总计影响的行数
		 * @return number
		 */
		public function row()
		{
			return $this->_filp;
		}
		
		private function _select()
		{
			$path = &$this->_path;
			$sql = 'SELECT `id`, `map`, `path` FROM `#@__attach` WHERE `map` IN (
						SELECT `map` FROM `#@__attach` GROUP BY `map` HAVING COUNT(`map`) > 1
					) ORDER BY `id` ASC;';
				
			DB::exec($sql)->each(function ($row) use (&$path)
			{
				$map = $row['map'];
				$path[$map][] = ['path' => $row['path'], 'id' => $row['id']];
			});
		}
		
		private function _del(Array $path, $map)
		{
			$obj = new Path();
			foreach ($path as $p)
			{
				try 
				{
					$obj->cd(ROOT.$p['path'])->remove();
					$this->_delDir($obj);
					
					$this->_data[] = $p['id'];
				}
				catch (\Exception $e)
				{
					continue;
				}
			}
		}
		
		private function _delDir(Path $path)
		{
			!$path->parents()->ls() && $this->_delDir($path->remove());
		}
	}
}