<?php
/** 
 * FILE_NAME : Caller.php   FILE_PATH : lv/call/
 * 调用包中的类文件
 *
 * @internal const	INC	用include的方式请求调用文件
 * @internal const	REQ	用require的方式请求调用文件
 * 
 * @internal const	CACHE	缓存类常量
 * @internal const	LIB	类库常量类
 * @internal const	LANG	语言包常量类
 * 
 * @internal public static	(array)$globals	记录所有请求的文件集合
 * 
 * @internal public	$import	调用文件方式：INC、REQ，默认是INC
 * @internal public $package	作用域申明，有两种：LIBRARY调用类库、LANG对象内部，存储被请求文件中的变量
 * 
 * @internal private	$_tpl_vars	array	存储请求文件中的变量
 * 
 * @see (public)getAssign()	(array)$this->_tpl_vars	返回请求解析的数据
 * @param public	call()	Boolean	执行调用文件，成功返回TRUE，失败返回False;
 * @see (public)setAssign()	void	设置请求解析的变量
 * @param string	$var	需要解析的变量字段，例如：var或var1, var2, var...
 * @version	2010-11-24 
 * @author	Levi
 */

class Caller
{
	const INC = 'include';
	const REQ = 'require';
	
	const LV = 'lv';
	
	const CACHE = 'CacheConst';
	const LIB = 'LibraryConst';
	const LANG = 'LangConst';
	const DB = 'DBDefine';
	
	public static $globals = array();
	
	public $import = self::INC;
	public $package = LIBRARY;
	
	private $_tpl_vars = array();
	private $_deConst = array(LV);
	
	public function __construct($__package=LIBRARY, $__import=self::INC)
	{
		$this->package = $__package;
		$this->import = $__import;
	}
	
	public function getAssign()
	{
		return (array)$this->_tpl_vars;
	}
	
	public function setAssign($var)
	{
		if(isset($var) && trim($var) != '')
		{
			if (strpos($var, ','))
			{
				$var = explode(',', $var);
				foreach ($var as $value)
				{
					$value = trim($value);
					$this->_tpl_vars[$value] = '';
				}
			}
			else $this->_tpl_vars[$var] = '';
		}
	}
	
	public function import($package=NULL)
	{
		$path = strstr($package, '.') ? ROOT : getcwd().'/';
		if (strstr($path, '\\')) $path = str_replace('\\', '/', $path);
		if (strstr($package, '.')) $package = str_replace('.', '/', $package);
		
		$path .= $package;
		if (strstr($path, '*') || empty($package))
		{
			$this->_getDir(trim($path, '*'));
		}
		else
		{
			$this->call($path);
		}
	}
	
	public function call($file)
	{
		$file = trim($file).'.php';
		if (!is_file($file)) return FALSE;
		if ($this->package != LANG && in_array($file, self::$globals)) return FALSE; 
		if ($this->import == self::INC)
		{
			include $file;
		}
		else require $file;
		array_push(self::$globals, $file);
		
		if ($this->package != LANG || count($this->_tpl_vars) <= 0) return TRUE;
		foreach ($this->_tpl_vars as $key=>$value)
		{
			if (isset($$key))
			{
				$this->_tpl_vars[$key] = $$key;
			}
		}
		
		return TRUE;
	}
	
	private function _getDir($dir=NULL)
	{
		if (($handle = opendir($dir)) != FALSE)
		{
			while(($file = readdir($handle)) != FALSE)
			{
				if (is_file($dir.$file) && $file != '.' && 
					$file != '..' && substr($file, -4, 4) == '.php' && !stristr($_SERVER['PHP_SELF'], $file))
				{
					self::call($dir.trim($file, '.php'));
				}
			}
		}
		else throw new Exception('当前页面调用的文件不存在。或者不可读取。');
		
		return TRUE;
	}
}
?>