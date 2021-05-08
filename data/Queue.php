<?php
namespace lv\data 
{
	class Queue
	{
		public $met = 1;
		
		private $_num = 1;
		private $_max = 5;
		private $_point = 0;
		private $_roll = FALSE;
		private $_tree = array();
		private $_action = array();
		
		private $_power = 0;
		private $_sort = FALSE;
		
		/**
		 * 添加截点
		 * @param string $name	截点名称
		 * @param int $num		截点优先级
		 */
		public function __set($name, $num) 
		{
			$this->append($name, $num);
		}
		
		/**
		 * 获取截点优先级
		 * @param string $name	截点名称
		 * @return Ambigous <NULL, multitype:>
		 */
		public function __get($name) 
		{
			return isset($this->_tree[$name]) ? $this->_tree[$name] : NULL;
		}
		
		/**
		 * 设置最大嵌套数
		 * @param int $num
		 * @return \lv\data\Queue
		 */
		public function max($num)
		{
			$this->_max = max(0, (int)$num);
			return $this;
		}
		
		/**
		 * 返回最大嵌套数
		 * @return number
		 */
		public function getMax() 
		{
			return $this->_max;
		}
		
		/**
		 * 获取当前指针位置
		 * @return number
		 */
		public function point() 
		{
			return $this->_point;
		}
		
		/**
		 * 设置、获取优先级最低(大)的数字
		 * @param number $num
		 * @return number
		 */
		public function power($num = NULL) 
		{
			if (is_int($num)) 
			{
				($num > $this->_power) && ($this->_power = $num);
				return $num;
			}
			
			return $this->_power;
		}
		
		/**
		 * 以数字索引获取截点名称，若没有提供参数则返回整个队列
		 * @param int|NULL $point
		 * @return boolean|multitype:
		 */
		public function tree($point = NULL) 
		{
			if (is_numeric($point))
			{
				$key = array_keys($this->_tree);
				return isset($key[$point]) ? $key[$point] : FALSE;
			}
			else
			{
				return $this->_tree;
			}
		}
		
		/**
		 * 以截点名称获取数字索引
		 * @param string $name
		 * @return mixed|NULL
		 */
		public function searchKey($name) 
		{
			if (isset($this->_tree[$name])) 
			{
				return array_search($name, array_keys($this->_tree));
			}
			
			return FALSE;
		}
		
		/**
		 * 插入一个优先级最低的截点
		 * @param string $name	截点名称
		 * @return \lv\data\Queue
		 */
		public function low($name)
		{
			if (!is_numeric($name))
			{
				if (isset($this->_tree[$name]))
				{
					unset($this->_tree[$name]);
				}
		
				$tree = array($name => sprintf('%d_%d', $this->_power, $this->_num++));
				$this->_tree += $tree;
			}
				
			return $this;
		}
		
		/**
		 * 插入一个优先级最高的截点
		 * @param string $name	截点名称
		 * @return \lv\data\Queue
		 */
		public function high($name)
		{
			if (!is_numeric($name) && 0 !== ($key = $this->searchKey($name)))
			{
				$add = $this->_roll && (!$key || $key > $this->_point);
				$this->_tree = array($name => '0_'.$this->_num++) + $this->_tree;
				$this->_sort = TRUE;
		
				if ($add)
				{
					$this->_point++;
					$this->sort();
				}
			}
				
			return $this;
		}
		
		/**
		 * 在队列头部添加一个自定义优先级的截点
		 * @param string $name
		 * @param number $num
		 * @return Ambigous <\lv\data\Queue, \lv\data\Queue>
		 */
		public function prepend($name, $num = 10, $isset = FALSE) 
		{
			// 需要根据情况排序
			if (!is_numeric($name) && (!$isset || !$this->_tree[$name])) 
			{
			    $sort = $this->power(max(0, (int)$num));
			    $add = TRUE;
			    
			    // 只有在队列进行中，且添加的截点是已存在的截点
			    if ($this->_roll && isset($this->_tree[$name])) 
			    {
			        // 获取当前执行队列的截点
			        $current = $this->tree($this->_point - 1);
			        if (FALSE !== $current && (int)$this->_tree[$name] < (int)$this->_tree[$current]) 
			        {
			            /*
			             * 如果添加的优先级小于当前队列截点的优先级，指针就不再递增1
			             * 因为添加的截点本身就排在队列当前截点之前
			             */
			            $add = FALSE;
			        }
			    }
			    
				$tree = array($name => sprintf('%d_%d', $this->power(max(0, (int)$num)), $this->_num++));
				
				$this->_tree = $tree + $this->_tree;
				$this->_sort = TRUE;
				if ($add) 
				{
					$this->_point++;
				}
			}
			
			return $this->_roll ? $this->sort() : $this;
		}
		
		/**
		 * 在队列尾部添加一个自定义优先级的截点
		 * @param string $name		截点名称
		 * @param number $num		优先级
		 * @param boolean $isset	设置为true，若截点存在就不再更新截点
		 * @return \lv\data\Queue
		 */
		public function append($name, $num = 10, $isset = FALSE) 
		{
			/*
			 * 如果设置了isset，并且存在截点，就不再插入了
			 * 注意：如果添加的截点已存在队列中，且循环相互添加截点，会造成死循环
			 */
			if ($isset && isset($this->_tree[$name])) 
			{
				return $this;
			}
			
			if (!is_numeric($name)) 
			{
				$this->_tree[$name] = sprintf('%d_%d', $this->power(max(0, (int)$num)), $this->_num++);
			}

			$this->_roll && $this->sort();
			if ($this->_roll && $name == $this->tree($this->_point - 1))
			{
			    // 在正在执行队列的时候，若添加的截点正好在预备执行截点的前面，指针递减
			    $this->_point--;
			}
			
			return $this;
		}
		
		/**
		 * 插入一个截点，和指定截点优先级一致，并且排在指定优先级列中最后执行
		 * 若没有指定截点，则优先级和当前截点一致
		 * @param string $node	新截点
		 * @param string $name	对应截点
		 * @return \lv\data\Queue|Ambigous <\lv\data\Queue, \lv\data\Queue>
		 */
		public function node($node, $name = NULL) 
		{
			// 如果没有给出截点名称，或者截点名称不存在，获取当前指针对应的截点
			if (!$name || !isset($this->_tree[$name]))
			{
				// 如果在运行中减少1，因为每次执行都递增1
				$sort = $this->_roll ? $this->_point - 1 : $this->_point;
				$name = $this->tree($sort);
			}
			
			// 如果是在执行中，追加的截点是不允许和对应的截点名称一致（死循环）
			if ($this->_roll && $node == $name) 
			{
				return $this;
			}
			
			return $this->append($node, (int)$this->_tree[$name]);
		}
		
		/**
		 * 在指定截点后面插入新截点，并且优先级和指定截点一致，执行顺序排在指定截点之后
		 * 若没有指定截点，则优先级和当前截点一致
		 * @param string $node	新截点名称
		 * @param string $name	对应截点名称，若不存在插入当前截点后面
		 * @return \lv\data\Queue
		 */
		public function after($node, $name = NULL) 
		{
			return $this->_insert($node, $name, 1);
		}
		
		/**
		 * 在指定截点前面插入新截点，并且优先级和指定截点一致，执行顺序排在指定截点之前
		 * 若没有指定截点，则优先级和当前截点一致
		 * @param string $node	新截点名称
		 * @param string $name	对应截点名称，若不存在插入队列最前端
		 * @return \lv\data\Queue
		 */
		public function before($node, $name = NULL) 
		{
			return $this->_insert($node, $name);
		}
		
		/**
		 * 清空数据，若清空数据时，传入数组参数，则设置新的队列
		 * @param array $set	新的队列
		 * @return \lv\data\Queue
		 */
		public function flush(Array $set = array()) 
		{
			$this->met = 1;
			$this->_roll = FALSE;
			
			$this->_point = 0;
			$this->_power = 0;
			
			$this->_action = array();
			if (empty($set)) 
			{
				return $this;
			}
			
			$this->_tree = array();
			foreach ($set as $key => $val) 
			{
				if (!is_numeric($key)) 
				{
					$this->$key = $val;
				}
			}
			
			return $this;
		}
		
		/**
		 * 删除截点
		 * @param string $node
		 * @return \lv\data\Queue
		 */
		public function delete($node) 
		{
			if (isset($this->_tree[$node])) 
			{
				/*
				 * 只要是正在进行队列，删除排在下一个队列以上的截点，point要递减
				 * 为什么是下一个截点，因为在队列过程中point已经++，
				 */
				if ($this->_roll && $this->searchKey($node) < $this->_point) 
				{
					$this->_point--;
				}
				
				unset($this->_tree[$node]);
			}
			
			return $this;
		}
		
		/**
		 * 若没有提供指定截点，返回当前截点名称
		 * 若提供指定截点参数，当前截点指向指定截点位置，并返回截点名称
		 * 若截点不存在，会返回FALSE
		 * @param string $name	截点名称
		 * @return string|Ambigous <boolean, \lv\data\multitype:, multitype:>
		 */
		public function current($name = NULL) 
		{
			if ($name && FALSE !== ($point = $this->searchKey($name))) 
			{
				$this->_point = $point;
				return $name;
			}
			
			return $this->tree($this->_point);
		}
		
		/**
		 * 若没有提供指定截点，返回对应当前截点的上一个截点名称
		 * 若提供指定截点参数，当前截点指向指定截点上一个位置，并返回截点名称
		 * 若截点不存在，会返回FALSE
		 * @param string $name	截点名称
		 * @return Ambigous <boolean, \lv\data\multitype:, multitype:>
		 */
		public function prev($name = NULL) 
		{
			if ($name && FALSE !== ($point = $this->searchKey($name)))
			{
				$point--;
			}
			else
			{
				$point = $this->_point - 1;
			}
			
			$this->_point = max(0, $point);
			return ($point < 0) ? FALSE : $this->tree($point);
		}
		
		/**
		 * 
		 * 若没有提供指定截点，返回对应当前截点的下一个截点名称
		 * 若提供指定截点参数，当前截点指向指定截点下一个位置，并返回截点名称
		 * 若截点不存在，会返回FALSE
		 * @param string $name	截点名称
		 * @return Ambigous <boolean, \lv\data\multitype:, multitype:>
		 */
		public function next($name = NULL) 
		{
			if ($name && FALSE !== ($point = $this->searchKey($name))) 
			{
				$this->_point = $point + 1;
			}
			else 
			{
				$this->_point++;
			}
			
			return $this->tree($this->_point);
		}
		
		/**
		 * 将指针定向当前队列中最后一个截点，并返回截点名称
		 * 若队列没有截点，返回false
		 * @return Ambigous <boolean, \lv\data\multitype:, multitype:>|boolean
		 */
		public function end() 
		{
			if ($this->_tree)
			{
				$this->_point = count($this->_tree) - 1;
				return $this->tree($this->_point);
			}
			
			return FALSE;
		}
		
		/**
		 * 将指针定向当前队列中第一个截点，并返回截点名称
		 * 若队列没有截点，返回false
		 * @return Ambigous <boolean, \lv\data\multitype:, multitype:>|boolean
		 */
		public function reset() 
		{
			if ($this->_tree) 
			{
				$this->_point = 0;
				return $this->tree($this->_point);
			}
			
			return FALSE;
		}
		
		/**
		 * 执行队列
		 * @param \Closure $method
		 * @return Ambigous <\lv\data\Queue, \lv\data\Queue>
		 */
		public function action(\Closure $method, $point = 0) 
		{
			$this->_point = $point;
			$this->sort();
			
			$this->_roll = TRUE;
			while (FALSE != ($key = $this->tree($this->_point++)))
			{
				$this->_count($key);
				
				$bind = \Closure::bind($method, $this);
				$bind($key, (int)$this->_tree[$key]);
				
				if ($this->met == 0) 
				{
					break;
				}
				elseif ($this->met > 1) 
				{
					continue;
				}
			}

			return $this->flush();
		}
		
		/**
		 * 队列排序
		 * @return \lv\data\Queue
		 */
		public function sort() 
		{
			// 如果是在执行队列中，这里的point永远之下下一个
			$node = $this->_roll ? $this->tree($this->_point) : FALSE;
			if ($this->_sort) 
			{
				$tree = array();

				reset($this->_tree);
				while(list($key, $val) = each($this->_tree))
				{
					$tree[$key] = sprintf('%d_%d', (int)$val, $this->_num++);
				}
					
				$this->_tree = $tree;
				$this->_sort = FALSE;
			}
			
			natsort($this->_tree);

			$node && FALSE !== ($key = $this->searchKey($node)) && $this->_point = $key;
			return $this;
		}
		
		private function _insert($node, $name = NULL, $move = 0) 
		{
			// 如果没有提供截点名称，或者截点不存在，根据当前指针后者截点
			if (!$name || !isset($this->_tree[$name]))
			{
				// 如果正在队列，指针-1，因为每次队列时都递增1
				$sort = $this->_roll ? $this->_point - 1 : $this->_point;
				$name = $this->tree($sort);
			}
			
			// 如果插入的截点和当前截点同名则不需要排序
			if ($node == $name)
			{
				return $this;
			}
			
			// 提前删除已存在截点
			$this->delete($node);
			$new = array($node => sprintf('%d_%d', (int)$this->_tree[$name], $this->_num++));
			$end = array_splice($this->_tree, $this->searchKey($name) + $move);
			
			$this->_tree += ($new + $end);
			$this->_sort = TRUE;

			// 如果是向前插入，要将指针后移一个位置
			if ($this->_roll) 
			{
				!$move && $this->_point++;
				$this->sort();
			}
			
			return $this;
		}
		
		private function _count($key) 
		{
			if (isset($this->_action[$key])) 
			{
				if ($this->_max < $this->_action[$key]++) 
				{
					throw new \Exception('已超出了最大嵌套循环次数。');
				}
			}
			else 
			{
				$this->_action[$key] = 1;
			}
		}
	}
}