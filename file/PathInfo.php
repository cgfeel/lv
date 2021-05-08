<?php
namespace lv\file
{
	use lv\filter\Check;
		
	/**
	 * 文件、目录信息
	 *
	 * @namespace lv\file
	 * @version 2014-08-10
	 * @author Levi <levi@cgfeel.com>
	 * @name PathInfo
	 * @copyright Copyright (c) 2006-2010 Levi
	 * @package	lv\file\PathInfo
	 * @subpackage \SplFileInfo
	 */
	abstract class PathInfo extends \SplFileInfo
	{
		private static $_path_base = '';
		
		const DS = DIRECTORY_SEPARATOR;
		const PHP = '.php', TXT = '.txt', HTML = '.html', HTM = '.htm', JSON = '.json';
		
		// 优先级文件格式
		private $_pri = array(
				'aif', 'djv', 'dll', 'html', 'igs', 'jpg', 'lha', 
				'm4a', 'midi', 'mp3', 'mpeg', 'ogg', 'pnt', 'qti', 
				'ra', 'sgm', 'skd', 'smil', 'sv4cpio', 'texi', 
				'tif', 'wbxml', 'xht', 'xml'
		);
		
		/**
		 * 初始路径，可以是文件或路径，路径可以存在或不存在
		 * @param string $package
		 * @param string $type
		 */
		public function __construct($path = '', $type = '')
		{
			if (empty(self::$_path_base))
			{
				$root = dirname(dirname(dirname(dirname(dirname(__FILE__))))).PathInfo::DS;
				self::$_path_base = $this->pack2Path($root);
			}
			
			$this->set($path);
		}
		
		/**
		 * 定位路径
		 * @param string $path
		 * @return \lv\file\PathInfo
		 */
		public function cd($path)
		{
			parent::__construct($this->pack2Path($path));
			return $this;
		}
		
		/**
		 * 根据项目根目录定位路径
		 * @param string $path
		 * @return Ambigous <\lv\file\PathInfo, \lv\file\PathInfo>
		 */
		public function set($path) 
		{
			return $this->cd(self::$_path_base.ltrim($path, PathInfo::DS));
		}
		
		/**
		 * 将包解析成路径
		 * @param string $pack
		 * @return Ambigous <unknown, mixed>
		 */
		public function pack2Path($pack) 
		{
			return strstr($pack, '\\') ? str_replace('\\', PathInfo::DS, $pack) : $pack;
		}
		
		/*
		 * 将包解析成路径
		 * @param string $package
		 * @param string $type
		 * @return Ambigous <\lv\file\PathInfo, \lv\file\PathInfo>
		 *
		public function set($package = '', $type = '')
		{
			$package = str_replace(PathInfo::INTERVAL, '/', $package);
			
			!empty($type) && $type = '.'.trim($type, '.');
			return $this->cd(\data\__($package.$type));
		}*/
		
		/**
		 * 获取真实有效的路径
		 * 注：路径可能是文件，也可能是目录
		 * @throws \Exception
		 * @return string
		 */
		public function get()
		{
			if (FALSE != ($path = $this->getRealPath())) 
			{
				return $path;
			}
			
			throw new \Exception('请求的文件不存在');
		}
		
		/**
		 * 如果当前路径是文件，则获取当前文件目录的路径
		 * 如果当前路径是目录，则返回当前路径
		 * @return string
		 */
		public function getDir()
		{
			return $this->isFile() ? dirname($this->getRealPath()) : $this->get();
		}
		
		/**
		 * 获取文件、目录的权限
		 * @return string
		 */
		public function getPS()
		{
			return substr(sprintf('%o', $this->getPerms()), -4);
		}
		
		/**
		 * 获取文件、目录所属用户、用户组
		 * @param number $type
		 * @return multitype:
		 */
		public function getPW($type = 1)
		{
			return $type === 1 ? posix_getpwuid($this->getOwner()) : posix_getgrgid($this->getGroup());
		}
		
		/**
		 * 获取文件、目录的hash
		 * @param number $type
		 * @return string
		 */
		public function getHash($type = 1)
		{
			return $type === 1 ? md5_file($this->get()) : sha1_file($this->get());
		}
		
		/**
		 * 定位到上级目录
		 * @return Ambigous <\lv\file\Ambigous, \lv\file\PathInfo, \lv\file\PathInfo>
		 */
		public function parents()
		{
			return $this->cd(dirname($this->__toString()));
		}
		
		/**
		 * 打开当前对象所在目录下的文件或目录
		 * 注：打开的目录或文件可以不存在
		 * @param string $name
		 * @return Ambigous <multitype:, \lv\file\Ambigous, \lv\file\PathInfo, \lv\file\PathInfo>
		 */
		public function open($name)
		{
			return $this->cd($this->getDir().'/'.$name);
		}
		
		/**
		 * 修改文件、目录名
		 * @param string $name
		 * @throws \Exception
		 * @return \lv\file\PathInfo
		 */
		public function rename($name)
		{
			$target = $this->get();
			if (!@rename($target, $this->parents()->open($name)))
			{
				$this->cd($target);
				throw new \Exception('改名失败，请检查当前文件权限');
			}
			
			return $this;
		}
		
		public function getRealExt($pri = [])
		{
			if ($this->isFile())
			{
				$ext = $this->getExtension();
				$mini = new MiniInfo($this);
				$check = (new Check())->setMini($mini);
				
				if ($check->isExt($ext))
				{
					return $ext;
				}
				else
				{
					$type = $check->getExts();
					if (count($type) == 1) 
					{
						return $type[0];
					}
					
					empty($pri) && $pri = $this->_pri;
					if (!($get = array_intersect($pri, $type))) 
					{
						throw new \Exception('当前文件不在系统指定范围内！');
					}
					else
					{
						return array_shift($get);
					}
				}
			}
			
			throw new \Exception('当前对象不是有效的文件！');
		}
	}
}