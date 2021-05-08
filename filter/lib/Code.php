<?php
namespace lv\filter\lib
{
	trait Code 
	{
		private $_base62 = 'vPh7zZwA2LyU4bGq5tcVfIMxJi6XaSoK9CNp0OWljYTHQ8REnmu31BrdgeDkFs';
		private $_char = array('ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8');
		
		public function random($length = 8)
		{
			$key = '';
			$pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
				
			$max = strlen($pattern) - 1;
			for($i = 0; $i < $length; $i++)
			{
				$key .= $pattern{mt_rand(0, $max)};
			}
				
			return $this->setVal($key);
		}
		
		public function base62_encode()
		{
			$out = '';
			$val = $this->get(2);
			for ($t = floor(log10($val) / log10(62)); $t >= 0; $t--)
			{
				$a = floor($val / pow(62, $t));
				$out = $out.substr($this->_base62, $a, 1);
				$val = $val - ($a * pow(62, $t));
			}
				
			return $this->upVal($out);
		}
		
		public function base62_decode()
		{
			$out = 0;
			for($t = 0, $len = $this->length() - 1; $t <= $len; $t++)
			{
				$out = $out + strpos($this->_base62, substr($this->get(2), $t, 1)) * pow(62, $len - $t);
			}
				
			return $this->upVal(substr(sprintf("%f", $out), 0, -7));
		}
		
		public function getChar()
		{
			return mb_detect_encoding($this->get(2), $this->_char);
		}
		
		public function iconv($char = 'UTF-8')
		{
			$encode = $this->getChar();
			$data = $this->get(2);
			
			!in_array($char, $this->_char) && $char = 'UTF-8';
			if ($encode != $char)
			{
				$data = iconv($encode, $char.'//TRANSLIT//IGNORE', $data);
			}
			
			return $this->upVal($data);
				
// 			return $this->get(2);
		}
	}
}