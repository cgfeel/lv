<?php
namespace lv\event
{
	/**
	 * FILE_NAME : Event.php   FILE_PATH : lv/event/
	 * 检查字符串数据
	 *
	 * @copyright Copyright (c) 2006-2010 mailTo:levi@cgfeel.com
	 * @author Levi
	 * @package	lv.event.Event
	 * @subpackage
	 * @version 2013-04-21
	 */
	
	class Event
	{
		public $type = '';
		public $target = NULL;
		public $bubbles = TRUE;
		public $power = 0;
	    
	    /**
	     * 创建事件
	     * @param string $type
	     */
	    public function __construct($type)
	    {
	        $this->type = $type;
	    }
	    
	    /**
	     * 得到事件字符串
	     */
	    public function __toString()
	    {
	        return (String)$this->type;
	    }
	    
	    /**
	     * 阻止冒泡
	     */
	    public function stopPropagation()
	    {
	    	$this->bubbles = FALSE;
	    }
	    
	    public function isType($key) 
	    {
	        return $this->type == $key;
	    }
	}
}