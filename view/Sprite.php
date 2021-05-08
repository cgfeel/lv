<?php
namespace lv\view
{
	use ArrayObject;
	use lv\event\EventDispatcher;
	use lv\file\Path;
	use lv\mysql\Cache;
	use lv\request\Header;
	use lv\url\PathURL;
				
	/**
	 * FILE_NAME : Sprite.php   FILE_PATH : lv/view/
	 * 缓存输出文件
	 *
	 * @copyright Copyright (c) 2006-2010 mail:levi@cgfeel.com
	 * @author Levi
	 * @package	lv.view.Sprite
	 * @subpackage
	 * @version 2013-07-17
	 */
	class Sprite extends EventDispatcher
	{
		const TAG = '{__TP_warp_data%s}';
		
		public static $tips = array();
		private $_template = '';
		
		public function __construct($data = array())
		{
			parent::__construct($data, ArrayObject::ARRAY_AS_PROPS);
		}
		
		/**
		 * 设置数据
		 * @param string $context
		 * @return \lv\view\Sprite
		 */
		public function set($context)
		{
			$this->_template = $context;
			return $this;
		}
		
		/**
		 * 返回数据
		 * @return string
		 */
		public function get()
		{
			return $this->_template;
		}
		
		public function change($package, $data = array(), $display = TRUE, $type = Path::HTM)
		{
			$copy = clone $this;
			$copy->exchangeArray($data);
			($copy->add($package, $type) && $display) && $copy->display();
			
			return (Object)$copy;
		}
		
		/**
		 * 临时会话
		 * @param string $key
		 * @param string $msg
		 * @param number $time
		 * @return string
		 */
		public function tips($key, $msg = '', $time = 60)
		{
			$cache = new Cache();
			$msg && $cache->set($key, $msg)->expire($time);
			$data = $cache->get($key);
			
			empty($msg) && $cache->del($key);
			return $data;
		}
		
		/**
		 * 打印数据
		 * @return \lv\view\Sprite
		 */
		public function display($stop = FALSE)
		{
			(new Header())->init();
			echo $this->_template;
			
			$stop && exit();
			
			return $this->set('');
		}
		
		/**
		 * 获取缓存数据，不及时输出
		 * @param string $package
		 * @param string $type
		 * @return string
		 */
		public function reander($package, $type = Path::HTM)
		{
			ob_start();
			include (new Path($package, $type))->get();
			return ob_get_clean();
		}
		
		/**
		 * 按照路径在数据末尾添加信息
		 * @param string $package
		 * @param int $type
		 * @return \lv\view\Sprite
		 */
		public function add($package, $type = Path::HTM)
		{
			$this->_template .= $this->reander($package, $type);
			return $this;
		}
		
		/**
		 * 在开头添加数据
		 * @param string $content
		 * @return \lv\view\Sprite
		 */
		public function first($content)
		{
			$this->_template = $content.$this->_template;
			return $this;
		}
		
		/**
		 * 在末尾追加数据
		 * @param string $content
		 * @return \lv\view\Sprite
		 */
		public function end($content)
		{
			$this->_template .= $content;
			return $this;
		}
		
		/**
		 * 包裹数据
		 * @param string $tpl
		 * @return \lv\view\Sprite
		 */
		public function warp($tpl, $target = '')
		{
			$emp = empty($target);
			$tag = is_array($tpl) ? FALSE : sprintf(self::TAG, ($emp ? '' : '_'.$target));
			
			$this->_template = $this->_warp($tag, $this->_template, $tpl);
			return $this;
		}
		
		/**
		 * 嵌入数据
		 * @param string $tpl
		 * @return \lv\view\Sprite
		 */
		public function warpTo($tpl, $target = '')
		{
			$emp = empty($target);
			$tag = is_array($tpl) ? FALSE : sprintf(self::TAG, ($emp ? '' : '_'.$target));
			
			$this->_template = $this->_warp($tag, $tpl, $this->_template);
			return $this;
		}
		
		private function _warp($tag, $tpl, $subject)
		{
			if (FALSE === $tag) 
			{
				$replace = array_values($tpl);
				$search = array_map(function($n) 
				{
					return sprintf(Sprite::TAG, '_'.$n);
				}, array_keys($tpl));
				
				return str_replace($search, $replace, $subject);
			}
			else
			{
				return str_replace($tag, $tpl, $subject);
			}
		}
		
		/**
		 * 加载JS
		 * @param string $link
		 * @param string $prv
		 */
		public function js($link, Array $attr = array())
		{
			$file = explode('?', $link);
			$arg = isset($file[1]) ? '?'.$file[1] : '';
			
			$url = $this->res($file[0], 'js')->get().$arg;
			$tmp = '<script type="text/javascript" src="%s"%s></script>'.PHP_EOL;
			printf($tmp, $url, empty($attr) ? '' : $this->_attr($attr));
		}
		
		/**
		 * 加载CSS
		 * @param string $link
		 * @param string $prv
		 */
		public function css($link, Array $attr = array())
		{
			$file = explode('?', $link);
			$arg = isset($file[1]) ? '?'.$file[1] : '';
			
			$url = $this->res($file[0], 'css')->get().$arg;
			$tmp = '<link href="%s" rel="stylesheet" type="text/css"%s />'.PHP_EOL;
			
			printf($tmp, $url, empty($attr) ? '' : $this->_attr($attr));
		}
		
		/**
		 * 添加图片资源
		 * @param string $link
		 * @param string $ext
		 * @param array $p
		 */
		public function img($link, $ext = 'jpg', $p = [])
		{
			$alt = '';
			foreach ((['alt' => ''] + $p) as $key => $val)
			{
				$alt .= sprintf('%s="%s" ', $key, $val);
			}
			
			$file = explode('?', $link);
			$arg = isset($file[1]) ? '?'.$file[1] : '';
			
			$url = $this->res($file[0], $ext)->get().$arg;
			printf('<img src="%s" %s/>', $url, $alt);
		}
		
		/**
		 * 加载静态资源
		 * @param string $package	路径
		 * @param string $ext		文件格式
		 * @param array $set		参数
		 * @return Ambigous <\lv\url\PathURL, \lv\url\URL, \lv\url\PathURL>
		 */
		public function res($package, $ext, $set = [])
		{
			return (new PathURL())->path(new Path($package, $ext), $set);
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
		
		/**
		 * 表单下拉框
		 * @param Array $data	选择内容
		 * @param Mixed $selected	默认选中项的值
		 * @param String $name	名称(name)
		 * @param Array $attr	属性
		 */
		public function selectTpl($data, $selected, $name, $attr = array())
		{
			$set = array();
			$attr['name'] = $name;
			!isset($attr['id']) && $attr['id'] = $name;
			foreach ($attr as $key => $val) $set[] = sprintf('%s="%s"', $key, $val);
			
			printf('<select%s>', ' '.implode(' ', $set));
			foreach ($data as $val => $name)
			{
				printf('<option value="%s"%3$s>%s</option>', $val, $name, $val == $selected ? ' selected="selected"' : '');
			}
			
			echo '</select>';
		}
		
		/**
		 * 表单单选、多选框
		 * @param Array $data	选择内容
		 * @param String $name	名称(name)
		 * @param Mixed $checked	默认选中项的值（可以传入一个外部方法）
		 * @param String $type	表单类型(单选、多选)
		 * @param Array $attr	属性
		 */
		public function checked($data, $name, $checked, $type = 'checkbox', $attr = array())
		{
			$set = array();
			if (!empty($attr))
			{
				foreach ($attr as $key => $val)
				{
					$set[] = sprintf(' %s="%s"', $key, $val);
				}
			}
			
			foreach ($data as $val => $text)
			{
				$iName = strstr($name, '%s')  ? sprintf($name, $val) : $name;
				$bool = is_object($checked) ? (Bool)$checked($val) : ($val == $checked);
				
				printf('<input type="%s" name="%s" id="%2$s_%s" value="%3$s"%5$s%6$s /> <label for="%2$s_%3$s">%s</label> ',
						$type, $iName, $val, $text, $bool ? ' checked="checked"' : '', implode('', $set));
			}
		}
	}
}