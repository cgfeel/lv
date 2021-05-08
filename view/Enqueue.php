<?php
namespace lv\view
{
	use lv\file\Path;
	use lv\url\PathURL;
		
	class Enqueue
	{
		private $_group = array();
		private $_printJS = array();
		public function regJS($key, $url, Array $deps = array()) 
		{
			$this->_group[$key] = array('url' => $url, 'deps' => $deps, 'localize' => array());
			return $this;
		}
		
		public function printJS($key, $ver = FALSE, Array $meta = array()) 
		{
			if ($key) 
			{
				isset($this->_group[$key]) && $this->printOnceJS($key, $ver, $meta);
			}
			else 
			{
				foreach ($this->_group as $key => $group) 
				{
					$this->printOnceJS($key, $ver, $meta);
				}
			}
			
			return $this;
		}
		
		public function printOnceJS($key, $ver = FALSE, Array $meta = array()) 
		{
			if (!isset($this->_printJS[$key])) 
			{
				$group = &$this->_group[$key];
				if (FALSE != ($deps = $group['deps'])) 
				{
					foreach ($deps as $dep) 
					{
						$this->printOnceJS($dep);
					}
				}
				
				if (FALSE != ($localize = $group['localize'])) 
				{
					foreach ($localize as $put) 
					{
						echo $put;
					}
				}
				
				$ver = $ver ? array('req' => is_bool($ver) ? time() : $ver) : array();
				$url = $this->res($this->_group[$key]['url'], 'js', $ver)->get();
				
				$tmp = '<script type="text/javascript" src="%s"%s></script>'.PHP_EOL;
				printf($tmp, $url, empty($meta) ? '' : $this->_attr($meta));
				
				$this->_printJS[$key] = $group;
				return $this;
			}
		}
		
		/**
		 * 加载静态资源
		 * @param string $package	路径
		 * @param string $ext		文件格式
		 * @param array $set		参数
		 * @return Ambigous <\lv\url\PathURL, \lv\url\URL, \lv\url\PathURL>
		 */
		public function res($package, $ext, Array $set = array()) 
		{
			return (new PathURL())->path(new Path($package, $ext), $set);
		}
		
		public function localize_script($key, $name, $data) 
		{
			if (isset($this->_group[$key])) 
			{
				$script = "var $name = " . json_encode($data) . ';';
				$this->_group[$key]['localize'][] = sprintf('<script type="text/javascript">%s</script>', $script);
			}
		}
		
		private function _attr(Array $data)
		{
			$attr = ' ';
			foreach ($data as $key => $val)
			{
				$attr .= sprintf('%s="%s"', $key, $val);
			}
				
			return $attr;
		}
	}
}