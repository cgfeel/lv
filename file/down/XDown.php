<?php
namespace lv\file\down
{
	use lv\file\MiniInfo;
	use lv\file\Path;
	use lv\request\Header;
			
	class XDown extends Path
	{
		// Nginx, Cherokee
		const X_NGINX = 'X-Accel-Redirect';
		
		// Apache, Lighttpd v1.5, Cherokee
		const X_APACH = 'X-Sendfile';

		// Lighttpd v1.4
		const X_LIGHTTPD = 'X-LIGHTTPD-send-file';
		
		private $_name = '';
		
		public function fileName($str)
		{
			$this->_name = $str;
			return $this;
		}
		
		public function getName()
		{
			return $this->_name;
		}
		
		public function down($type = NULL)
		{
			if (FALSE != ($file = $this->get())) 
			{
				$name = empty($this->_name) ? $this->getBasename() : $this->_name.'.'.$this->getExtension();
				if ($type) 
				{
					$opt = array(
						// 'application/octet-stream'
						'Content-type' => (new MiniInfo($file))->mini(),
						'Content-Disposition' => sprintf('attachment; filename="%s"', $name),
							
						// 让Xsendfile发送文件
						$type => $this->get()
					);
					
					(new Header($opt))->init();
				}
				else
				{
					(new To($file))->down($this->_name);
				}
			}
		}
	}
}