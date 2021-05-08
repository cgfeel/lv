<?php
namespace lv\filter\lib
{
	class CodeCheck extends StrCheck
	{
		private $_base62 = 'vPh7zZwA2LyU4bGq5tcVfIMxJi6XaSoK9CNp0OWljYTHQ8REnmu31BrdgeDkFs';
		
		public function base62_encode() 
		{
			$out = '';
			$val = $this->get();
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
			$len = strlen($this->get()) - 1;
			for($t = 0; $t <= $len; $t++) 
			{
				$out = $out + strpos($this->_base62, substr($this->get(), $t, 1)) * pow(62, $len - $t);
			}
			
			return $this->upVal(substr(sprintf("%f", $out), 0, -7));
		}
		
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
	}
}