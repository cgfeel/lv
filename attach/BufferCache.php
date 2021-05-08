<?php
/**
 * FILE_NAME : BufferCache.php   FILE_PATH : lv/attach/
 * 生成缩略图
 *
 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
 * @author Levi
 * @package	lv.attach.BufferCache
 * @subpackage
 * @version 2013-06-27
 */
namespace lv\attach
{
	use Exception;
	use	lv\attach\thumb\CreateThumb;
	use lv\file\Path;
// 	use	lv\file\down\Idown;
// 	use	lv\file\down\Reptile;
	use	lv\mysql\DB;
	use lv\file\save\Down;
	use lv\request\HTTP;
			
	class BufferCache 
	{
		const MAP = 'map';
		const DESC = 'desc';
		const SIZE = 'size';
		const THUMB = 'thumb';
		const RECORD = 'record';
		
		private $_memory = 104857600;
		private $_md5 = array();
		private $_info = array();
		private $_log = array('map' => '', 'size' => 1048576);
		private $_layout = array('gif', 'jpg', 'jpeg', 'png');
		
		public function __construct()
		{
			$this->_initLog();
		}
		
		public function set($key, $data)
		{
			switch ($key)
			{
				case self::MAP: $this->_log['map'] = (String)$data; break;
				case self::DESC: $this->_log['desc'] = (String)$data; break;
				case self::SIZE:
					$size = (Int)$data;
					$this->_log['size'] *= $size > 0 && $size < 10 ? $size : 1;
					break;
				case self::THUMB:
					$w = isset($data[0]) ? (Int)$data[0] : 0;
					$h = isset($data[1]) ? (Int)$data[1] : 0;
					$key = $w.'x'.$h;
					if ($w > 0 && $h > 0) $this->_log['thumb'][$key] = array($w, $h);
					break;
			}
		}
		
		public function get()
		{
			return (Array)$this->_info;
		}
		
		public function load($num, $url)
		{
			$num = (Int)$num > 0 ? (Int)$num : 1;
			$map = $this->_log['map'];
			if (empty($map)) return;
			
			$key = $map.'_'.$num;
			$this->_log['num'] = (Int)$num;
			$this->_log['key'] = (String)$key;
			$this->_log['url'] = (String)$url;
			$this->_info[$key] = (Array)$this->_log;
			
			try 
			{
				$down = (new Down(new HTTP($url)))->size($this->_log['size'])->load()->ext($this->_layout);
				$this->_setInfo('state', array('type' => 1, 'log' => '附件抓取成功。'));
				$this->_buffer($down->get(), $down->getRealExt(), $down->getSize());
				$this->_initLog();
				return TRUE;
				
				/*
				Reptile::$gzip = FALSE;
				$reptile = (Object)new Reptile($url);
				$pic = $reptile->get($this->_log['size']);
				
				$down = (Object)$reptile->down();
				$layout = (String)$down->state(Idown::TYPE);
				if (!in_array($layout, $this->_layout)) throw new Exception('远程附件格式不正确。');
				
				$this->_setInfo('state', array('type' => 1, 'log' => '附件抓取成功。'));
				$this->_buffer($pic, $layout == 'jpeg' ? 'jpg' : $layout, $down->state(Idown::SIZE));
				$this->_initLog();
				
				return TRUE;
				*/
			}
			catch (Exception $e)
			{
				$this->_setInfo('state', array('type' => 2, 'log' => $e->getMessage()));
				throw new Exception($e->getMessage());
			}
		}
		
		/**
		 * 刷新缓冲区，将图片入库
		 */
		public function rush()
		{
			if (!count($this->_md5)) return ;
			$sql = sprintf('SELECT `layout`, `path`, `time`, `md5`, `thumb` FROM `%sattach` WHERE `md5` = \'%s\';', 
							DB::$prefix, implode('\' OR `md5` = \'', array_keys($this->_md5)));
							
			$results = (Object)DB::exec($sql);
			if ($results->num_rows)
			{
				while (($row = $results->fetch_assoc()) != FALSE) $this->_setAttach($row);
			}
			
			$this->_put();
			$this->_mate();
		}
		
		private function _initLog()
		{
			$this->_log = array
			(
				'key' => '', 'desc' => '', 'url' => '', 'num' => 1, 'thumb' => array(), 
				'state' => array('type' => '0', 'log' => '等待抓取远程附件。'), 
				'map' => $this->_log['map'], 'size' => $this->_log['size']
			);
		}
		
		private function _setInfo($field, $value)
		{
			$key = (String)$this->_log['key'];
			if (isset($this->_info[$key]))
			{
				$this->_info[$key][$field] = $value;
			}
		}
		
		/**
		 * 设置文件记录缓冲区，记录文件的md5值、和二进制数据、缩略图，通过map关联info数据
		 * @param String $binary
		 */
		private function _buffer($binary, $layout, $size)
		{
			$md5 = md5($binary);
			$time = time();
			$this->_setInfo('md5', $md5);
			$this->_setInfo('time', $time);
			$this->_setInfo('layout', $layout);
			if (!isset($this->_md5[$md5]))
			{
				$this->_md5[$md5] = array
				(
					'layout' => (String)$layout, 
					'size' => (Int)$size,
					'time' => (Int)$time, 
					'thumb' => (Array)$this->_log['thumb'], 
					'data' => $binary, 
					'state' => 1
				);
			}
			else $this->_md5[$md5]['thumb'] += $this->_log['thumb'];
			if (memory_get_usage() >= $this->_memory) $this->rush();
		}
		
		/**
		 * 更新已存在的图片数据
		 * @param Array $attach
		 */
		private function _setAttach($attach)
		{
			$key = (String)$attach['md5'];
			$thumb = $this->_md5[$key]['thumb'];
			$attach['thumb'] = explode('|', $attach['thumb']);
			
			$diff = count($thumb) ? array_diff(array_keys($thumb), $attach['thumb']) : array();
			if (count($diff))
			{
				$path = 'attach.'.$attach['path'];
				$init = $attach['md5'].'_'.$attach['time'];
				try 
				{
					$create = (Object)new CreateThumb($init, $path, '.'.$attach['layout']);
					foreach ($diff as $size)
					{
						$pic = (Array)$thumb[$size];
						$create->exec(str_replace('attach.news.', 'attach.news.thumb.', $path), $pic[0], $pic[1]);
						$attach['thumb'][] = $size;
					}
				}
				catch (Exception $e) { continue; }
				$sql = sprintf('UPDATE `%sattach` SET `thumb` = \'%s\' WHERE `md5` = \'%s\';', 
								DB::$prefix, implode('|', $attach['thumb']), $attach['md5']);
								DB::exec($sql);
			}
			
			$this->_md5[$key]['state'] = 0;
		}
		
		/**
		 * 将未入库的图片保存在相应的目录中，释放所有图片内存
		 */
		private function _put()
		{
			$sql = array();
			foreach ($this->_md5 as $md5 => $value)
			{
				if (!$md5['state']) continue;
				$name = $md5.'_'.$value['time'];
				$dir = 'news.'.date('Y.m_d',time());
				$package = 'attach.'.$dir;
				$layout = '.'.$value['layout'];
				file_put_contents((String)(new Path($package))->open($name.$layout), $value['data']);
				
				$tb = '';
				if (count($value['thumb']))
				{
					try 
					{
						$create = (Object)new CreateThumb($name, $package, $layout);
						foreach ($value['thumb'] as $key => $thumb)
						{
							$pic = str_replace('attach.news.', 'attach.news.thumb.', $package);
							$create->exec($pic, $thumb[0], $thumb[1]);
							$tb .= $key.'_';
						}
						
						$tb = rtrim($tb, '_');
					}
					catch (Exception $e) { continue; }
				}
				
				$sql[] = "'{$value['layout']}', '{$dir}', '{$value['size']}', '{$value['time']}', '{$md5}', '{$tb}'";
			}
			
			if (count($sql))
			{
				$sql = sprintf('INSERT INTO `%sattach` (`layout`, `path`, `size`, `time`, `md5`, `thumb`) VALUES (%s);',
								DB::$prefix, implode('), (', $sql));
								DB::exec($sql);
			}
			
			$this->_md5 = array();
		}
		
		private function _mate()
		{
			$sql = '';
			foreach ($this->_info as $mate)
			{
				if ($mate['state']['type'] == 1)
				{
					$sql .= "('{$mate['map']}', '1', '{$mate['md5']}', '0', '{$mate['num']}', '{$mate['desc']}'), ";
				}
			}
			
			if (!empty($sql))
			{
				$sql = sprintf('INSERT INTO `%sattachmate` (`map`, `from`, `md5`, `author`, `num`, `desc`) VALUES %s;',
								DB::$prefix, trim($sql, ', '));
								DB::exec($sql);
			}
		}
	}
}