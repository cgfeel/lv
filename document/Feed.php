<?php
namespace lv\document 
{
	class Feed extends \DOMDocument 
	{
		private $_dom;
		private $_items = array();
		private $_channel = array();
		private $_type = 1;
		
		public function __construct($xml) 
		{
			parent::loadXML($xml);
			$this->_dom = $this->_extractDOM($this->childNodes);
		}
		
		public function isRss() 
		{
			return $this->_type == 1;
		}
		
		public function getType() 
		{
			return $this->_type;
		}
		
		public function getRSS($includeAttributes = false) 
		{
			if($includeAttributes) 
			{
				return $this->_dom;
			}
			
			return $this->_valueReturner();
		}
		
		public function getChannel($includeAttributes = false) 
		{
			if($includeAttributes) 
			{
				return $this->_channel;
			}
			
			return $this->_valueReturner($this->_channel);
		}
		
		public function getItems($includeAttributes = false) 
		{
			if($includeAttributes) 
			{
				return $this->_items;
			}
			
			return $this->_valueReturner($this->_items);
		}
		
		private function _valueReturner($valueBlock = false, $num = 0) 
		{
			if($valueBlock === FALSE) 
			{
				$valueBlock = $this->_dom;
			}
			
			foreach ($valueBlock as $valueName => $values) 
			{
				if(isset($values['value'])) 
				{
					$values = $values['value'];
				}
				
				$valueBlock[$valueName] = is_array($values) ? $this->_valueReturner($values) : $values;
			}
			
			return $valueBlock;
		}
		
		private function _extractDOM($nodeList, $parentNodeName = false) 
		{
			$tempNode = array();
			$tempData = array();
			
			foreach ($nodeList as $values) 
			{
				if (substr($values->nodeName, 0 , 1) != '#') 
				{
					if ($values->nodeName == 'feed') 
					{
						$this->_type = 2;
					}
					
					$nodeName = $values->nodeName;
					if ($values->attributes) 
					{
						for($i = 0; $values->attributes->item($i); $i++)
						{
							$tempData['properties'][$values->attributes->item($i)->nodeName] = $values->attributes->item($i)->nodeValue;
						}
					}
					else 
					{
						$tempData['properties'] = array();
					}
					
					$tempData['value'] = $values->firstChild ? $this->_extractDOM($values->childNodes, $values->nodeName) : $values->textContent;					
					if (isset($tempNode[$nodeName]['value'])) 
					{
						$tempNode[$nodeName] = array($tempNode[$nodeName]);
						$tempNode[$nodeName][] = $tempData;
					}
					else 
					{
						$tempNode[$nodeName] = $tempData;
					}
					
					if (in_array($parentNodeName, array('channel', 'feed', 'rdf:RDF'))) 
					{
						if($values->nodeName == 'item' || $values->nodeName == 'entry') 
						{
							$this->_items[] = $tempData['value'];
						} 
						elseif(!in_array($values->nodeName, array('rss', 'channel', 'feed'))) 
						{
							$this->_channel[$values->nodeName] = $tempData;
						}
					}
				} 
				elseif (substr($values->nodeName, 1) == 'text') 
				{
					$tempValue = trim(preg_replace('/\s\s+/', ' ', str_replace("\n",' ', $values->textContent)));
					if($tempValue) 
					{
						$tempNode = $tempValue;
					}
				} 
				elseif (substr($values->nodeName, 1) == 'cdata-section')
				{
					$tempNode = $values->textContent;
				}
			}
			
			return $tempNode;
		}
	}
}