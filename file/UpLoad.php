<?php
/**
 * FILE_NAME : UpLoad.php   FILE_PATH : lv\file\Upload
 * 上传文件操作
 *
 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
 * @author Levi
 * @package	lv.file.UpLoad
 * @subpackage
 * @version 2013-06-27
 */
namespace lv\file
{
	use Exception;
	use lv\mysql\DB;
	use lv\url\PathURL;
	
	class UpLoad extends Path
	{
		const ATTACH = 'attach.';
		
		/*
		public static $mini = array
		(
			'image/gif' => 'gif',
			'image/jpeg' => 'jpg',
			'image/pjpeg' => 'jpg',
			'image/png' => 'png',
			'image/x-png' => 'png'
		);
		*/
		
		private $_fileSize = 2097152;
		private $_tmp = '';
		
		private $_upExt = array();
		private $_info = array
		(
			'author' => 0,
			'from' => 0, 
			'num' => 1,
			'size' => 0, 
			
			'desc' => '', 
			'from' => '',
			'layout' => '',
			'map' => '',
			'md5' => '', 
			'path' => '', 
			'thumb' => ''
		);
		
		private $_attach = array();
		
		/**
		 * 设置上传控件
		 * @param String $inputName	控件名称，为空则不上传
		 */
		public function __construct($inputName = '')
		{
			!empty($inputName) && $this->_exec($inputName);
		}
		
		/**
		 * 限制上传文件上传格式
		 * @param Array $upExt	文件格式，为空则返回当前限制的格式集合
		 * @return Array
		 */
		public function upExt($upExt = array())
		{
			if (count($upExt) > 0) $this->_upExt = (Array)$upExt + $this->_upExt;
			return (Array)$this->_upExt;
		}
		
		/**
		 * 限制上传文件大小
		 * @param Int $size	单位bytes，为空则返回当前限制的文件大小
		 * @return Int
		 */
		public function fileSize($size = 0)
		{
			if ($size > 0) $this->_fileSize = (Int)$size;
			return (Int)$this->_fileSize;
		}
		
		/**
		 * 设置附件文件
		 * @param unknown_type $file
		 */
		public function attach($file = '')
		{
			if (!empty($file))
			{
				if (strstr($file, INDEX)) $file = str_replace(INDEX, ROOT, $file);
				if (file_exists($file))
				{
					$md5 = md5_file($file);
					$this->_attach[$md5] = array('path' => (String)$file, 'md5' => (String)$md5);
				}
			}
			
			return (Array)$this->_attach;
		}
		
		/**
		 * 设置临时文件
		 * @param String $value
		 * @throws Exception
		 * @return String
		 */
		public function setTmp($value)
		{
			if (strstr($value, INDEX)) $value = str_replace(INDEX, ROOT, $value);
			if (file_exists($value))
			{
				$this->_checkExt($value);
				$this->_tmp = (String)$value;
				
				return (String)$this->_tmp;
			}
			
			throw new Exception(sprintf('您给出的路径“%s”不是一个有效的系统路径', $value));
		}
		
		/**
		 * 获取临时文件
		 * @throws Exception
		 * @return String
		 */
		public function tmp()
		{
			try 
			{
				$this->_checkInfo();
				
				$package = self::ATTACH.'tmp_up.'.date('Y.m_d').date('YmdHis', time()).mt_rand(10000, 99999);
				$this->_tmp = (new Path($package, $this->_info['layout']))->create()->copy(new Path($this->_tmp))->get();
				
				return (String)$this->_tmp;
			}
			catch (Exception $e)
			{
				$this->_removeTmp();
				throw new Exception($e->getMessage());
			}
		}
		
		/**
		 * 格式化文件大小
		 * @param Int $bytes
		 * @return String
		 */
		public function formatBytes($bytes)
		{
			if($bytes >= 1073741824)
			{
				$bytes = round($bytes / 1073741824 * 100) / 100 . 'GB';
			} 
			elseif($bytes >= 1048576) 
			{
				$bytes = round($bytes / 1048576 * 100) / 100 . 'MB';
			} 
			elseif($bytes >= 1024) 
			{
				$bytes = round($bytes / 1024 * 100) / 100 . 'KB';
			} 
			else $bytes = $bytes . 'Bytes';
			
			return (String)$bytes;
		}
		
		/**
		 * 保存文件、并入库
		 * @param Stirng $target
		 * @param Array $info
		 * @throws Exception
		 * @return String
		 */
		public function save($target, $info)
		{
			try 
			{
				$this->_setInfo($info);
				$this->_checkInfo();
				
				$this->_info['md5'] = md5_file($this->_tmp);
				$this->_info['path'] = sprintf('%s.%s',$target, date('Y.m_d'));
				
				$this->_DB();
				$info = (Array)$this->_info;
				$path = (new PathURL())->path(self::ATTACH.$info['path'], '.'.$info['layout']);
				
				$sql = sprintf('INSERT INTO `#@__attachmate` (`map`, `from`, `md5`, `author`, `num`, `desc`) 
								VALUES (\'%s\', \'%s\', \'%s\', \'%d\', \'%d\', \'%s\');',
								$info['map'], $info['from'], $info['md5'], $info['author'], 
								$info['num'], $info['desc']);
					
				if (DB::exec($sql))
				{
					$this->_removeTmp();
					return (String)$path->get();
				}
	
				throw new Exception('关联文件查找或入库失败。');
			}
			catch (Exception $e)
			{
				throw new Exception($e->getMessage());
				$this->_removeTmp();
			}
		}
		
		/**
		 * 根据文件的MD5值，删除文件
		 * @param String $file
		 */
		public function del($file)
		{
			if (!file_exists($file)) return ;
			$md5 = md5_file($file);
			$sql = 'SELECT md5 FROM `#@__attachmate` WHERE `md5`=\'%s\';';
			$mate = sprintf('DELETE FROM `#@__attachmate` WHERE `md5`=\'%s\';', $md5);
			$result = (Object)DB::exec($sql);
			if ($result == 1)
			{
				DB::exec($mate);
				DB::exec(sprintf('DELETE FROM `#@__attach` WHERE `md5`=\'%s\';', $md5));
				chmod($file, 0777);
				unlink($file);
			}
			else DB::exec($mate);
		}
		
		private function _exec($inputName)
		{
			$package = self::ATTACH.'tmp.'.date('YmdHis', time()).mt_rand(10000, 99999);
			$tempPath = new Path($package, 'tmp');
			
			//HTML5
			if (isset($_SERVER['HTTP_CONTENT_DISPOSITION']) && 
				preg_match('/attachment;\s+name="(.+?)";\s+filename="(.+?)"/i', $_SERVER['HTTP_CONTENT_DISPOSITION'], $info))
				{
					file_put_contents($tempPath, file_get_contents('php://input'));
					$fileInfo = pathinfo($info[2]);
					$this->_info['layout'] = (String)$fileInfo['extension'];
					$this->_tmp = $tempPath->get();
					
					return ;
				}
			
			if (!isset($_FILES[$inputName])) 
			{
				throw new Exception('文件域的name错误');
			}
			
			$upfile = $_FILES[$inputName];
			if (!empty($upfile['error']))
			{
				switch ($upfile['error'])
				{
					case 1: $err = '文件大小超过了系统定义的最大值'; break;
					case 2: $err = '文件大小超过了HTML定义的最大值'; break;
					case 3: $err = '文件上传不完全'; break;
					case 4: $err = '无文件上传'; break;
					case 6: $err = '缺少临时文件夹'; break;
					case 7: $err = '写文件失败'; break;
					case 8: $err = '上传被其他扩展中断'; break;
					default: $err = '无有效错误代码';
				}
				
				throw new Exception($err);
			}
			elseif (empty($upfile['tmp_name']) || $upfile['tmp_name'] == 'none')
			{
				throw new Exception('无文件上传');
			}
			
			move_uploaded_file($upfile['tmp_name'], $tempPath);
			$this->_checkExt($tempPath);
			$this->_tmp = (String)$tempPath;
		}
		
		private function _checkExt($tmp)
		{
			$fp = fopen($tmp, 'rb');
			$bin = fread($fp, 2);
			fclose($fp);
			
			$info = unpack('C2chars', $bin);
			$code = intval($info['chars1'].$info['chars2']);
			switch ($code)
			{
				case 6677: $type = 'bmp'; break;
				case 7173: $type = 'gif'; break;
				case 7784: $type = 'midi'; break;
				case 7790: $type = 'exe'; break;
				case 8075: $type = 'zip'; break;
				case 8297: $type = 'rar'; break;
				case 13780: $type = 'png'; break;
				case 255216: $type = 'jpg'; break;
				default: $type = '';
			}
			
			if (!empty($type))
			{
				$this->_info['layout'] = (String)$type;
				return;
			}
			
			throw new Exception('您上传的文件格式超出了系统指定范围。');
		}
		
		private function _setInfo($info)
		{
			if (isset($info['layout'])) unset($info['layout']);
			$this->_info = $info + $this->_info;
			
			if (!isset($this->_info['map']) || !isset($this->_info['from']))
			{
				throw new Exception('附件信息映射地址、关联不能为空。');
			}
		}
		
		private function _checkInfo()
		{
			if (count($this->_upExt) > 0 && !in_array($this->_info['layout'], $this->_upExt))
			{
				throw new Exception('上传文件扩展名必需为：'.implode(',', $this->_upExt));
			}
			
			$this->_info['size'] = filesize($this->_tmp);
			if($this->_info['size'] > $this->_fileSize)
			{
				throw new Exception('请不要上传大小超过'.$this->formatBytes($this->_fileSize).'的文件');
			}
		}
		
		private function _DB()
		{
			$info = (Array)$this->_info;
			$sql = sprintf('SELECT `layout`, `path` FROM `#@__attach` WHERE `md5` = \'%s\';', $info['md5']);
			$result = (Object)DB::exec($sql);
			if (!$result->num_rows) 
			{
				$name = date("YmdHis").mt_rand(1000,9999);
				$sql = sprintf('INSERT INTO `#@__attach` (`layout`, `path`, `size`, `time`, `md5`, `thumb`) VALUES 
								(\'%s\', \'%s.%s\', \'%d\', \'%d\', \'%s\', \'\');', 
								$info['layout'], $info['path'], $name, $info['size'], time(), $info['md5']);
				
				if (!DB::exec($sql)) 
				{
					throw new Exception('附件入库失败。');
				}
				
				(new Path(self::ATTACH.$info['path'].$name, $info['layout']))->create()->copy(new Path($this->_tmp));
				$this->_info['path'] .= '.'.$name;
			}
			else $this->_info = $result->fetch_assoc() + $info;
		}
		
		private function _removeTmp()
		{
			if (file_exists($this->_tmp))
			{
				chmod($this->_tmp, 0777);
				unlink($this->_tmp);
			}
		}
	}
}