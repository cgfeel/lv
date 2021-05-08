<?php
namespace lv\view
{
	use lv\url\URL;
	
	/**
	 * FILE_NAME : Page.php   FILE_PATH : lv/view/
	 * 分页类，属性“p”，“total”，“current”是保留字段
	 *
	 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
	 * @author Levi
	 * @package	lv.view.Sprite
	 * @subpackage
	 * @version 2013-04-30
	 */
	class Page extends Sprite 
	{
		public $total = 1, $current = 1, $p = array();
		
		private $_url, $_name = 'page';
		
		public function __construct($data = array())
		{
			parent::__construct($data);
			$this->_url = (Object)new URL();
		}
		
		/**
		 * 配置核心参数
		 * 
		 * @param int $count
		 * @param int $size
		 * @param string $page
		 * @return \lv\view\Page
		 */
		public function config($count, $size = 50, $page = 'page')
		{
			if (isset($_GET[$page]))
			{
				$page = (Int)trim($_GET[$page]);
				$this->_name = $page;
			}
			
			$this->total = ceil($count / $size);
			$this->current = $page > 1 ? $page : 1;
			
			return $this;
		}

		/**
		 * 生成分页，特殊键：0.上一页；-1.下一页；-10.开始页；-11.结束页
		 *
		 * @param int $num
		 * @param int $span
		 * @return \lv\view\Page
		 */
		public function create($num = 5, $span = 2)
		{
			$this->p = array();
			$start = $this->current - $num + $span;
			$start < 1 && $start = 1;
			
			$end = $start + $num;
			$end > $this->total && $end = $this->total;
			
			$this->current > 1 && $this->p[0] = $this->_grab($this->current - 1);
			$start > 1 && $this->p[-10] = $this->_grab(NULL);
			for ($i = $start; $i <= $end; $i++) 
			{
				$this->p[$i] = $this->_grab($i);
			}
			
			$end < $this->total && $this->p[-11] = $this->_grab($this->total);
			$this->current < $this->total && $this->p[-1] = $this->_grab($this->current + 1);
			
			return $this;
		}
		
		private function _grab($num)
		{
			return array(
				'url' => $this->_url->set(array($this->_name => $num), TRUE)->get(),
				'on' => $num == $this->current
			);
		}
	}
}