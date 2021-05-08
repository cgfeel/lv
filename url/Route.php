<?php
namespace lv\url
{
	use \Exception;
	use \lv\load\L;
	
	class Route extends URL
	{
		private $_route;
		
		public function __construct()
		{
			parent::__construct();
			$this->_init();
		}
		
		public function routing() 
		{
			
		}
		
		private function _init() 
		{
			if (ROUTE) 
			{
				$parse = parse_url($this->url);
				$host = array_chunk(array_reverse(explode('.', $parse['host'])), 2);
				$pack = isset($host[1]) ? implode('\\', array_reverse($host[1])) : '';
				
				$pock = explode('/', $parse['path']);
				$path = str_replace('_', '\\', $pock[1]);
				
				$first = empty($path) ? array() : NULL;
				array_map(function($n) use(&$first)
				{
					if ($n) 
					{
						$set = explode('_', $n);
						if (!isset($_GET[$set[0]])) 
						{
							$_GET[$set[0]] = isset($set[1]) ? $set[1] : '';
						}
						
						is_null($first) && $first = array($set[0] => $_GET[$set[0]]);
					}
				}, array_splice($pock, 1));
				
				if (empty($path)) 
				{
					if (empty($pack)) 
					{
						throw new Exception('系统没有找到你要请求的对象。');
					}
					
					L::loadObj(sprintf('item\\%s\\index', $pack));
				}
				else 
				{
					try 
					{
						$key = '';
						if ($first) 
						{
							$key = array_keys($first)[0];
							unset($_GET[$key]);
						}
						
						L::loadObj(sprintf('item\\%s\\%s', $pack, $path));
					}
					catch (\Exception $e) 
					{
						$key && $_GET[$key] = $first[$key];
						L::loadObj(sprintf('item\\%s\\index', $pack));
					}
				}
			}
		}
	}
}