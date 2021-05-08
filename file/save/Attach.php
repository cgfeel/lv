<?php
namespace lv\file\save
{
	use \Exception;
	use lv\file\Path;
	use lv\file\save\UPFilp;
	use lv\mysql\DB;
			
	class Attach extends Path
	{
		/**
		 * 保存文件，有这么几种可能会造成保存出错：
		 *   1.大小超过mysql int保存最大值
		 *   2.文件格式超出系统指定方位
		 *   
		 * @param Path $path
		 * @throws Exception	抛出异常信息，并删除附件
		 * @return \file\upload\Attach
		 */
		public function save(Path $path = NULL)
		{
			$hash = $this->getHash();
			is_null($path) && $path = (new Path(sprintf('attach/%s', date('Y/m_d', time()))))->create();
			
			$this->move2($path)->rename(sprintf('%s.%s', md5($hash.$this->getCTime()), $this->getRealExt()));
			$path = str_replace(ATTACH, '', $this->get());
			
			try
			{
				$sql = "INSERT IGNORE INTO `#@__attach` VALUES ('%s', '%s');";
				$sql = sprintf($sql, $hash, $path);
				
				DB::exec($sql);
				if (!DB::affected_rows())
				{
					$this->remove();
					return $this->select($hash);
				}
				
				return $this;
			}
			catch (Exception $e)
			{
				$this->remove();
				throw new Exception($e->getMessage());
			}
		}
		
		/**
		 * 通过hash获取文件
		 * @param string $hash
		 * @throws Exception
		 * @return Path
		 */
		public function select($hash)
		{
			$sql = "SELECT `path` FROM `#@__attach` WHERE `map`='{$hash}'";
			$result = DB::exec($sql);
			if (!$result->num_rows) 
			{
				throw new Exception('没有获取到文件');
			}
			
			return $this->cd(ATTACH.$result->fetch_assoc()['path']);
		}
		
		public function update()
		{
			
		}
		
		public function clean()
		{
			return new UPFilp();
		}
	}
}