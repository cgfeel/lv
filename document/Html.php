<?php
namespace lv\document
{
	use \DOMDocument;
	
	class Html extends DOMDocument
	{
		private $_nodeNames = array(
				'abbr', 'article', 'aside', 'audio', 
				'bdi', 'canvas', 'data', 'datalist', 'details', 
				'figcaption', 'figure', 'footer', 'header', 'hgroup', 
				'mark', 'meter', 'nav', 'output', 'progress', 
				'section', 'summary', 'time', 'video'
		);
		
		private $_wrapMap = array(
				'option' => array(1, '<select multiple="multiple">', '</select>'),
				'legend' => array(1, '<fieldset>', '</fieldset>'),
				'area' => array(1, '<map>', '</map>'),
				'param' => array(1, '<object>', '</object>'),
				'thead' => array(1, '', '<table></table>'),
				'tr' => array(2, '', '<table><tbody></tbody></table>'),
				'col' => array(2, '', '<table><tbody></tbody><colgroup></colgroup></table>'),
				'td' => array(3, '', '<table><tbody><tr></tr></tbody></table>')
				
		);
		
		public function __construct($select, $context = NULL)
		{
			// HANDLE: '', null, false
			if (!$select) 
			{
				return $this;
			}
			
			// Handle HTML strings
			if (is_string($select))
			{
				$match = array();
				if ($select{0} == '<' && substr($select, -1) && strlen($select) >= 3) 
				{
					$match = array(NULL, $select, NULL);
				}
				else
				{
					preg_match('/^(?:\s*(<[\w\W]+>)[^>]*|#([\w-]*))$/', $select, $match);
				}
				
				if ($match && ($match[1] || !$context)) 
				{
					if ($match[1]) 
					{
						$context = $context instanceof Html ? $context[0] : $context;
					}
				}
			}
		}
		
		public function parseHTML($data, $content = TRUE, $keepScripts = FALSE)
		{
			$rsingleTag = '/^<(\w+)\s*\/?>(?:<\/\1>|)$/';
			if (!$data || !is_string($data)) 
			{
				return NULL;
			}
			
			if (is_bool($content)) 
			{
				$keepScripts = $content;
				$content = FALSE;
			}
			
			!$content = $this;
			if (preg_match('/^<(\w+)\s*\/?>(?:<\/\1>|)$/', $data, $parsed))
			{
				// 单标签 创建一个单标签节点，并返回
				return [$this->createElement($parsed[1])];
			}

			// 多标签  执行并创建一个文档碎片
			$scripts = $keepScripts ? FALSE : [];
		}
		
		public function buildFragment($elems, DOMDocument $context)
		{
			$tmp = NULL;
			$node = array();
			$safe = $this->_createSafeFragment($context);
			foreach ($elems as $elem)
			{
				if ($elem || $elem === 0) 
				{
					if (is_object($elem)) 
					{
						method_exists($elem, 'nodeType') && $node = array($elem) + $node;
					}
					elseif (!preg_match('/<|&#?\w+;/', $elem))
					{
						$node[] = $context->createTextNode($elem);
					}
					else
					{
						is_null($tmp) && $tmp = $safe->appendChild($context->createElement('div'));
						
						$tag = preg_match('/<([\w:]+)/', $elem, $match) ? strtolower($match[1]) : '';
					}
				}
			}
		}
		
		private function _createSafeFragment(\DOMDocument $document)
		{
			if (FALSE != ($safeFrag = $document->createDocumentFragment()))
			{
				foreach ($this->_nodeNames as $node)
				{
					$safeFrag->createElement($node);
				}
			}
			
			return $safeFrag;
		}
		
		public function children()
		{
			$elem = $this->documentElement;
			return $elem->childNodes;
			/*
			echo '<pre>';
			print_r($elem);
			foreach ($elem->childNodes as $item)
			{
				print $item->nodeName . " = " . $item->nodeValue . "<br />";
			}
			*/
		}
	}
}