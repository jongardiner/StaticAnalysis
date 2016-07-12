<?php
namespace Scan\SymbolTable;

use Scan\ObjectCache;
use Scan\NodeVisitors\Grabber;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Expr\FuncCall;

abstract class SymbolTable  {

	/**
	 * @var ObjectCache
	 */
	protected $cache;

	protected $basePath;

	function __construct($basePath) {
		$this->cache=new ObjectCache();
		$this->basePath = $basePath;
	}

	function getClass($name) {
		$cacheName=strtolower($name);
		$file=$this->getClassFile($name);
		if(!$file) {
			return null;
		}
		$ob=$this->cache->get("Class:".$cacheName);
		if(!$ob) {
			$ob = Grabber::getClassFromFile($this, $file, $name, Class_::class);
			if($ob) {
				$this->cache->add("Class:".$cacheName, $ob);
			}
		}
		return $ob;
	}

	function getAbstractedClass($name) {
		$cacheName=strtolower($name);
		$ob=$this->cache->get("AClass:".$cacheName);
		$tmp = $this->getClassOrInterface($name);
		if ($tmp) {
			$ob=new \Scan\Abstractions\Class_($tmp);
		} else if(strpos($name,"\\")===false) {
			try {
				$refl = new \ReflectionClass($name);
				if ($refl->isInternal()) {
					$ob = new \Scan\Abstractions\ReflectedClass($refl);
				}
			} catch (\ReflectionException $e) {
				$ob=null;
			}
		}
		if($ob) {
			$this->cache->add("AClass:".$cacheName, $ob);
		}
		return $ob;
	}

	function getAbstractedMethod($className, $methodName) {
		$cacheName=strtolower($className."::".$methodName);
		$ob=$this->cache->get("AClass:".$cacheName);
		$tmp = $this->getClassOrInterface($className);
		if ($tmp) {
			$ob=new \Scan\Abstractions\Class_($tmp);
		} else if(strpos($className,"\\")===false) {
			try {
				$refl = new \ReflectionMethod($className, $methodName);
				if ($refl->isInternal()) {
					$ob = new \Scan\Abstractions\ReflectedClassMethod($refl);
				}
			} catch (\ReflectionException $e) {
				$ob=null;
			}
		}
		if($ob) {
			$this->cache->add("AClass:".$cacheName, $ob);
		}
		return $ob;
	}

	function getTrait($name) {
		$file=$this->getTraitFile($name);
		if(!$file) {
			return null;
		}
		$ob=$this->cache->get("Trait:".$name);
		if(!$ob) {
			$ob = Grabber::getClassFromFile( $this, $file, $name, Trait_::class);
			if($ob) {
				$this->cache->add("Trait:".$name, $ob);
			}
		}
		return $ob;
	}

	function isDefined($name) {
		$file=$this->getDefineFile($name);
		return boolval($file);
	}

	/**
	 * Converts phar:// psuedo-paths to relative paths.
	 * Converts relative paths to paths relative to $this->basePath
	 * Leaves absolute paths unchanged
	 * @param $fileName
	 * @return string
	 */
	function adjustBasePath($fileName) {
		if(strpos($fileName, "phar://")===0) {
			$fileName = substr($fileName, 7);
		} else if(!empty($fileName) && strpos($fileName,"/")!==0) {
			$fileName = $this->basePath."/".$fileName;
		}
		return $fileName;
	}

	function getInterface($name) {
		$file=$this->getInterfaceFile($name);
		if(!$file) {
			return null;
		}
		$ob=$this->cache->get("Interface:".$name);
		if(!$ob) {
			$ob = Grabber::getClassFromFile($this, $file, $name, Interface_::class);
			if($ob) {
				$this->cache->add("Interface:".$name, $ob);
			}
		}
		return $ob;
	}

	function getFunction($name) {
		$file=$this->getFunctionFile($name);
		if(!$file) {
			return null;
		}
		$ob=$this->cache->get("Function:".$name);
		if(!$ob) {
			$ob = Grabber::getClassFromFile($this, $file, $name, Function_::class);
			if($ob) {
				$this->cache->add("Function:".$name, $ob);
			}
		}
		return $ob;
	}

	function getClassMethod($className, $methodName) {
		$classMethods = $this->getClassMethods($className);
		foreach($classMethods as $method) {
			if(strcasecmp($method->name,$methodName)==0) {
				return $method;
			}
		}
		return null;
	}

	function getClassMethods($className) {
		$ret = [];
		$class = $this->getClass($className);
		if(is_array($class->stmts)) {
			foreach( $class->stmts as $stmt) {
				if ($stmt instanceof ClassMethod) {
					$ret[] = $stmt;
				}
			}
		}
		return $ret;
	}

	function getClassOrInterface($name) {
		return $this->getClass($name) ?: $this->getInterface($name);
	}

	function ignoreType($name) {
		$name=strtolower($name);
		return $name=='exception' || $name=='stdclass' || $name=='iterator';
	}

	abstract function addClass($name, Class_ $class, $file);

	abstract function addInterface($name, Interface_ $interface, $file);

	/**
	 * @param string      $className  Full namespace path to a class name
	 * @param string      $methodName Name of the method
	 * @param ClassMethod $method     Class method
	 * @return void
	 */
	function addMethod($className, $methodName, ClassMethod $method) {
		// Do nothing.
	}

	/**
	 * @param $className
	 * @return string
	 */
	abstract function getClassFile($className);

	abstract function getTraitFile($name);

	abstract function addTrait($name, Trait_ $trait, $file);

	/**
	 * @param $interfaceName
	 * @return string
	 */
	abstract function getInterfaceFile($interfaceName);

	/**
	 * @param $methodName
	 * @return string
	 */
	abstract function getFunctionFile($methodName);

	abstract function getDefineFile($defineName);

	abstract function addDefine($name, \PhpParser\Node $define, $file);

}