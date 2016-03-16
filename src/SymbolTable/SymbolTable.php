<?php
namespace Scan\SymbolTable;

use Scan\ObjectCache;
use Scan\NodeVisitors\Grabber;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\ClassMethod;

abstract class SymbolTable  {

	/**
	 * @var ObjectCache
	 */
	protected $cache;

	function __construct() {
		$this->cache=new ObjectCache();
	}

	function getClass($name) {
		$file=$this->getClassFile($name);
		if(!$file) {
			return null;
		}
		$ob=$this->cache->get("Class:".$name);
		if(!$ob) {
			$ob = Grabber::getClassFromFile($file, $name, Class_::class);
			if($ob) {
				$this->cache->add("Class:".$name, $ob);
			}
		}
		return $ob;
	}

	function getInterface($name) {
		$file=$this->getInterfaceFile($name);
		if(!$file) {
			return null;
		}
		$ob=$this->cache->get("Interface:".$name);
		if(!$ob) {
			$ob = Grabber::getClassFromFile($file, $name, Interface_::class);
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
			$ob = Grabber::getClassFromFile($this->functions[$name], $name, Function_::class);
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

	function ignoreType($name) {
		$name=strtolower($name);
		return $name=='exception' || $name=='stdclass';
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

}