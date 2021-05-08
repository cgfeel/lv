<?php
namespace lv\filter
{
	use lv\event\Event;
	
	class FilterEvent extends Event
	{
		private $_target;
		
		public function __construct($type, $target)
		{
			parent::__construct($type);
			$this->_target = $target;
		}
		
		public function get()
		{
			return $this->_target;
		}
	}
}