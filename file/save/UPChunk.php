<?php
namespace lv\file\save
{
	use lv\file\Path;
	use lv\file\text\SplFile;
	use lv\filter\Check;
	use lv\request\Header;
										
	class UPChunk extends Attach 
	{
		private $_config = array(
			'targetDir' => '\attach\tmp\upload',
			'nocache' => TRUE,
			'timeOut' => 300,
			'read' => 4096,
			'chunk' => 0,
			'chunks' => 0
		);
		
		public function targetDir($packet)
		{
			$this->_config['targetDir'] = $packet;
		}
		
		public function timeOut($num)
		{
			$time = (Int)$num;
			$this->_config['timeOut'] = $num < 0 ? 0 : $num;
		}
		
		public function nocache($set)
		{
			$this->_config['nocache'] = (Bool)$set;
		}
		
		public function action()
		{
			$this->_config['nocache'] && (new Header())->noCache()->init();
			set_time_limit($this->_config['timeOut']);
			
			try 
			{
				$this->_chunks();
				$in = $this->_getIn();
				$out = $this->_getOut();
			}
			catch (\RuntimeException $e)
			{
				$this->_die(101, 'Failed to open input stream.');
			}
			
			foreach ($in as $line)
			{
				$out->fwrite($in->current());
			}

			/*
			while (FALSE !== ($char = $in->fgetc())) 
			{
				$out->fwrite($char);
			}
			*/
			
			/*
			if (!$this->_config['chunks'] || $this->_config['chunk'] == $this->_config['chunks'] - 1) 
			{
// 				$this->move($out->getRealPath());
// 				echo $this;
// 				echo $out->getRealPath();
// 				(new Path())->cd($out->getRealPath())->move2($this);
			}
			*/
			
			// Return Success JSON-RPC response
			die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
		}
		
		private function _getFile()
		{
			$fileName = '';
			new Check(INPUT_REQUEST, 'name', function() use (&$fileName)
			{
				$fileName = $this->trim()->get();
			});
			
			if (empty($fileName))
			{
				$fileName = empty($_FILES) ? uniqid("file_") : $_FILES['file']['name'];
			}
			
			return $this->set($this->_config['targetDir'])->create()->open($fileName);
		}
		
		private function _chunks()
		{
			$chunk =& $this->_config['chunk'];
			new Check(INPUT_REQUEST, 'chunk', function() use (&$chunk)
			{
				$chunk = $this->get(1);
			});
			
			$chunks =& $this->_config['chunks'];
			new Check(INPUT_REQUEST, 'chunks', function() use (&$chunks)
			{
				$chunks = $this->get(1);
			});
		}
		
		public function _getIn()
		{
			if (!empty($_FILES)) 
			{
				if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) 
				{
					$this->_die(103, 'Failed to move uploaded file.');
				}
				
				$path = $_FILES['file']['tmp_name'];
			} 
			else 
			{
				$path = 'php://input';
			}
			
			return new SplFile((new Path())->cd($path), 'rb');
		}
		
		public function _getOut()
		{
			try 
			{
				//echo (new Path())->cd($this.'.part');
				return new SplFile($this->_getFile(), $this->_config['chunks'] ? 'ab' : 'wb');
			}
			catch (\RuntimeException $e)
			{
				$this->_die(102, 'Failed to open output stream.');
			}
		}
		
		private function _die($code, $msg)
		{
			$err = array(
					'jsonrpc' => '2.0',
					'error' => array(
						    'code' => $code,
							'message' =>  $msg
					),
					'id' => 'id'
			);
			
			die(json_encode($err));
		}
	}
}