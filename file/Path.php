<?php
namespace lv\file
{
	use Exception;
	use SplFileInfo;
	
	/**
	 * 文件、目录操作对象
	 *
	 * @namespace lv\file
	 * @version 2013-12-29
	 * @author Levi <levi@cgfeel.com>
	 * @name Path
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	lv\file\Path
	 * @subpackage lv\file\PathInfo
	 */
	class Path extends PathInfo
	{
		private $_size = 0;
		
		/**
		 * (non-PHPdoc)
		 * @see \lv\file\PathInfo::cd()
		 */
		public function cd($path)
		{
			$this->_size = 0;
			return parent::cd($path);
		}
		
		/**
		 * 遍历当前目录所有文件
		 * @throws Exception
		 * @return multitype:string
		 */
		public function ls()
		{
			$path = [];
			foreach (new \DirectoryIterator($this->getDir()) as $item)
			{
				if (($name = $item->getBasename()) != '.' && $name != '..')
				{
					$path[] = $item->getRealPath();
				}
			}
		
			return $path;
		}
		
		/**
		 * 遍历和当前文件、目录同级的所有目录和文件（不包含当前文件）
		 * @return unknown
		 */
		public function siblings() 
		{
			$target = $this->get();
			$list = $this->parents()->ls();
			
			$this->cd($target);
			if (false !== ($key = array_search($target, $list))) 
			{
				unset($list[$key]);
			}
			
			return $list;
		}
		
		/**
		 * 通过迭代的方式，查找当前目录所有文件
		 * @param string|function $filter	过滤条件，如果没有提供则默认将筛选当前目录有效文件
		 * @return boolean|multitype:NULL
		 */
		public function find($filter = NULL)
		{
			$path = [];
			$rit = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->getDir()));
			is_null($filter) && $filter = function(SplFileInfo $item)
			{
				return $item->isFile();
			};
			
			foreach ($rit as $item)
			{
				// $rit->getSubIterator()->isDot()
				if ($filter($item))
				{
					$path[] = $item->getRealPath();
				}
			}
			
			return $path;
		}
		
		/**
		 * 修改文件、目录权限
		 * @param int $num
		 * @param boolean $r
		 * @return \lv\file\Path
		 */
		public function chmod($num, $r = FALSE)
		{
			chmod($this->get(), $num);
			$r && $this->find(function(SplFileInfo $item) use ($num)
			{
				chmod($item->getRealPath(), $num);
			});
			
			return $this;
		}
		
		/**
		 * 如果当前路径是文件，则获取文件大小
		 * 如果当前路径是目录，则获取整个目录所有文件大小
		 * @return number
		 */
		public function getSize()
		{
			if ($this->_size) 
			{
				return $this->_size;
			}
			
			if ($this->isFile()) 
			{
				$size = parent::getSize();
			}
			else
			{
				$size = 0;
				$this->find(function(SplFileInfo $item) use (&$size)
				{
					$item->isFile() && $size += $item->getSize();
				});
			}
			
			// Fix for overflowing signed 32 bit integers,
			// works for sizes up to 2^32-1 bytes (4 GiB - 1):
			$size < 0 && ($size += 2.0 * (PHP_INT_MAX + 1));
			$this->_size = $size;
			
			return $this->_size;
		}
		
		/**
		 * 按照文件大小格式化输出
		 * @return string
		 */
		public function sizeFormat()
		{
			$bytes = $this->getSize();
			if($bytes >= 1073741824)
			{
				return round($bytes / 1073741824 * 100) / 100 . 'GB';
			}
			elseif($bytes >= 1048576)
			{
				return round($bytes / 1048576 * 100) / 100 . 'MB';
			}
			elseif($bytes >= 1024)
			{
				return round($bytes / 1024 * 100) / 100 . 'KB';
			}
			else
			{
				return $bytes . 'Bytes';
			}
		}
		
		/**
		 * 创建目录和文件
		 * @return \lv\file\Path
		 */
		public function create($mode = 0755)
		{
			$mkdir = function ($dir) use ($mode)
			{
				if (@mkdir($dir, $mode, TRUE)) 
				{
					return TRUE;
				}
				
				throw new Exception('建立目录失败。');
			};
			
			if (!$this->getRealPath()) 
			{
				$path = $this->__toString();
				if (strstr($path, '.'))
				{
					$dir = dirname($path);
					
					!file_exists($dir) && $mkdir($dir);
					if (!@fclose(fopen($path, 'w')))
					{
						throw new Exception('建立文件失败。');
					}
				}
				else 
				{
					$mkdir($path);
				}
			}
			
			return $this;
		}
		
		public static function coping($form, $to)
		{
			if (FALSE == @copy($form, $to))
			{
				$msg = '拷贝失败，请确认：1.文件是否存在；2.PHP拥有足够的操作权限<br />%sFrom: %s<br />%1$sTo: %s';
				throw new \Exception(sprintf($msg, PHP_EOL, $form, $to));
			}
		}
		
		/**
		 * 拷贝文件、目录到当前目录
		 * 如果需要验证拷贝文件是否成功，可以在拷贝之后使用get方法
		 * @param Path $path
		 * @return \lv\file\Path
		 */
		public function copy(Path $path)
		{
			if ($path->isFile()) 
			{
				Path::coping($path->get(), (String)$this->open($path->getBasename()));
			}
			else 
			{
				$len = strlen($path->get());
				$to = $this->getDir();
				$path->find(function (SplFileInfo $item) use ($len, $to)
				{
					if ($item->isFile()) 
					{
						$get = $item->getRealPath();
						Path::coping($get, (new Path())->cd($to.substr($get, $len))->create()->get());
					}
				});
			}
			
			return $this;
		}
		
		/**
		 * 拷贝文件或目录到指定目录，拷贝之后会重新定位到新的文件或目录的位置
		 * 如果需要验证拷贝文件是否成功，可以在拷贝之后使用get方法
		 * 如：$this->copy($path)->get();
		 * @param string $path
		 * @return boolean|Ambigous <boolean, \lv\file\Ambigous, \lv\file\PathInfo, \lv\file\Path>|Ambigous <\lv\file\Ambigous, \lv\file\PathInfo, \lv\file\Path>
		 */
		public function copy2(Path $path)
		{
			if ($this->isFile())
			{
				Path::coping($this->get(), $path->open($this->getBasename()));
			}
			else
			{
				$len = strlen($this->get());
				$to = $path->getDir();
				$this->find(function(SplFileInfo $item) use ($len, $to)
				{
					if ($item->isFile())
					{
						$get = $item->getRealPath();
						Path::coping($get, (new Path())->cd($to.substr($get, $len))->create()->get());
					}
				});
			}
			
			return $this->cd((String)$path);
		}
		
		/**
		 * 将文件、目录移动到当前目录
		 * @param Path $path
		 * @return \lv\file\Path
		 */
		public function move(Path $path)
		{
			$this->copy($path);
			$path->remove();
			
			return $this;
		}
		
		/**
		 * 将当前文件、目录移动到指定目录
		 * @param Path $path
		 * @return \lv\file\Path
		 */
		public function move2(Path $path)
		{
			$tar = $this->get();
			$this->copy2($path);

			(new Path())->cd($tar)->remove();
			return $this;
		}
		
		/**
		 * 将文件、目录添加到当前目录
		 * @param Path $path
		 * @param boolean $move
		 * @return \lv\file\Path
		 */
		public function add(Path $path, $move = FALSE)
		{
			$tar = $this->open($path->getBasename())->create();
			return $move ? $tar->move($path) : $tar->copy($path);
		}

		/**
		 * 将文件、目录添加到当前目录
		 * @param Path $path
		 * @param boolean $move
		 * @return \lv\file\Path
		 */
		public function warp(Path $path, $move = FALSE)
		{
			$tar = $path->open($this->getBasename())->create();
			return $move ? $this->move2($tar) : $this->copy2($tar);
		}
		
		/**
		 * 删除当前目录下的文件或目录
		 * @param string $name
		 * @return Ambigous <boolean, \lv\file\Path>
		 */
		public function kill($name)
		{
			return $this->open($name)->remove();
		}
		
		/**
		 * 删除文件或目录
		 * 注：删除目录时，会将目录下所有文件、目录全部删除
		 * @throws Exception
		 * @return \lv\file\Path
		 */
		public function remove()
		{
			if ($this->isFile())
			{
				if (!@unlink($this->getRealPath()))
				{
					throw new Exception('删除文件失败，请检查当前文件权限');
				}
			}
			else
			{
				$dir = $this->get();
				foreach (new \DirectoryIterator($dir) as $item)
				{
					if (($name = $item->getBasename()) != '.' && $name != '..')
					{
						(new Path())->cd($item->getRealPath())->remove();
					}
				}
				
				if (!@rmdir($dir)) 
				{
					throw new Exception('删除目录失败，请检查当前文件权限');
				}
			}
				
			return $this;
		}
	}
}