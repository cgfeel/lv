<?php
namespace lv\document
{
	class Dom extends \DOMDocument
	{
		private static $_context = '';
		private $_document = NULL;
		private $_length = 0;
		private $_xpath = NULL;
		
		public function __construct($context)
		{
			parent::__construct();
			$this->_document = $this->ownerDocument;
			$this->_xpath = new \DOMXPath($this);
			$this->init($context);
		}
		
		public function init($context)
		{
			if ($this->hasChildNodes())
			{
				foreach ($this->childNodes as $node)
				{
					$this->removeChild($node);
				}
			}
			
			if (is_string($context))
			{
				$dom = new \Domdocument;
				$dom->loadHTML($context);
				return $this->init($dom->getElementsByTagName('body')->item(0)->childNodes);
			}
			elseif ($context instanceof \DOMNodeList || $context instanceof \DOMElement)
			{
				if ($context instanceof \DOMNodeList)
				{
					foreach ($context as $node)
					{
						$this->appendChild($this->importNode($node, TRUE));
					}
				}
				else
				{
					$this->appendChild($this->importNode($context, TRUE));
				}
			}
			elseif ($context instanceof \Closure)
			{
				$html = $context();
				(!is_string($html) || !is_numeric($html)) && $html = '';
				
				return $this->html($context);
			}
			
			$this->_document = $this->_xpath->query('/');
			return $this;
		}
		
		public function id($name)
		{
			
			$node = array();
			$xpath = $this->_xpath;
			$this->each(function($dom) use ($xpath, $name, &$node)
			{
				$node = $xpath->query('//*[@id="'.$name.'"]', $dom);
			});

			$this->_document = $node;
			return $this;
		}
		
		public function find($context)
		{
			if (is_string($context)) 
			{
				$path = '/';
				foreach(split(' ', $context) as $lit)
				{
					
				}
			}
		}
		
		public function attr($name, $value = NULL)
		{
			$bool = array(
					'checked', 'selected',
					'async', 'autofocus', 'autoplay', 'controls',
					'defer', 'disabled', 'hidden', 'ismap', 'loop',
					'multiple', 'open', 'readonly', 'required', 'scoped'
			);
			
			$attr = function($elem, $name = '', $val = FALSE) use ($bool)
			{
				if (!isset($elem->nodeType) || in_array($elem->nodeType, array(3, 8, 2))) 
				{
					return ;
				}
				
				if (FALSE === $val) 
				{
					return $elem->getAttribute($name);
				}
				elseif ($name)
				{
					if (is_null($val)) 
					{
						$elem->removeAttribute($name);
						return '';
					}
					else
					{
						$name = strtolower($name);
						if (in_array($name, $bool)) 
						{
							$elem->setAttribute($name, $name);
							$val = $name;
						}
						elseif (preg_match('/^[a-z_]+([\w-_]*[\w_])*$/i', $name))
						{
							$elem->setAttribute($name, (String)$val);
						}
						
						return $val;
					}
				}
			};
			
			return $this->_access($attr, $name, $value, func_num_args() > 1);
		}
		
		public function text()
		{
			$self = $this;
			$text = function($value) use ($self)
			{
				return is_null($value) ? '' : $self->clear();
			};
		}
		
		public function clear()
		{
			$group = array();
			$this->each(function ($dom) use (&$group)
			{
				foreach ($dom->childNodes as $node)
				{
					$group[] = $node;
				}
			});
			
			$this->each(function($item)
			{
				$item->parentNode->removeChild($item);
			}, $group);
			
			return $this;
		}
		
		public function each($fn, $data = NULL, $i = 0)
		{
			!$data && $data = $this->_document;
			foreach ($data as $dom)
			{
				if ($dom instanceof \DOMNodeList) 
				{
					$this->each($fn, $dom, $i++);
				}
				else
				{
					$fn($dom, $i++);
				}
			}
			
			return $this;
		}
		
		public function length()
		{
			return $this->_document ? (
					isset($this->_document->length) ? $this->_document->length : count($this->_document)
			) : 0;
		}
		
		public function append()
		{
			
		}
		
		public function prepend()
		{
			
		}
		
		private function _access($fn, $key, $value, $chinable = FALSE, $emptyGet = NULL, $raw = FALSE)
		{
			$length = $this->length();
			$bulk = is_null($key);
			
			if (is_array($key)) 
			{
				// 处理多个设置
				$chinable = TRUE;
				foreach ($key as $i => $attr)
				{
					$this->_access($fn, $i, $attr, TRUE, $emptyGet, $raw);
				}
			}
			elseif (!is_null($value))
			{
				// 处理单个设置
				$chinable = TRUE;
				
				// $value 不是函数
				$raw = !($value instanceof \Closure);
				if ($bulk) 
				{
					if ($raw) 
					{
						// $value不是一个函数
					};
				}
				
				if ($fn instanceof \Closure) 
				{
					$this->each(function ($dom, $num) use ($fn, $key, $value, $raw)
					{
						$set = $raw ? NULL : \Closure::bind($value, $dom);
						$fn($dom, $key, ($set ? $set($fn($dom, $key), $num) : $value));
					});
				}
			}
			
			$document = $this;
			$get = function() use ($document, $key, $fn)
			{
				$attr = array();
				$document->each(function($dom) use ($key, $fn)
				{
					FALSE != ($val = $fn($dom, $key)) && $attr[] = $val;
				});
			};
			
			return $chinable ? $this : $get();
		}
		
		private function _domManip($value)
		{
			$isFunc = '';
		}
		
		public function html($context = '')
		{
// 			$elem = $this->_document;
// 			method_exists($elem, 'parentNode') && $elem = $elem->parentNode;
			$elem = $this->_document;
			isset($elem->parentNode) && ($elem->parentNode == $elem->ownerDocument) && $elem = $elem->parentNode;
			
			
// 			$elem = $this->_document ? $this->_document->parentNode : $this;
			!$elem && $elem = $this;
			if (!empty($context)) 
			{
				if ($elem->hasChildNodes()) 
				{
// 					print_r($this->_document);
					foreach ($elem->childNodes as $node)
					{
						$elem->removeChild($node);
					}
				}
				
				if (is_string($context))
				{
					$dom = new \Domdocument;
					$dom->loadHTML($context);
					return $this->html($dom->getElementsByTagName('body')->item(0)->childNodes);
				}
				elseif ($context instanceof \Closure)
				{
					$html = $context();
					(!is_string($html) || !is_numeric($html)) && $html = '';
				
					return $this->html($context);
				}
				elseif ($context instanceof \DOMNodeList || $context instanceof \DOMElement)
				{
					if ($context instanceof \DOMNodeList) 
					{
						foreach ($context as $node)
						{
							$elem->appendChild($this->importNode($node, TRUE));
						}
					}
					else
					{
						$elem->appendChild($this->importNode($context, TRUE));
					}
				}
			}
			
// 			$this->_document = $elem == $this ? $elem->documentElement : $elem;
			$this->_document = $elem;
			return $this;
		}
		
		public function docer()
		{
// 			print_r($this->_document);
		}
	}
}


?>