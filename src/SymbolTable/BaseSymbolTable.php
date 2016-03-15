<?php
namespace Scan\SymbolTable;

use Scan\ObjectCache;
use Scan\Grabber;

abstract class BaseSymbolTable implements SymbolTableInterface {

	/**
	 * @var ObjectCache
	 */
	protected $cache;

	function __construct() {
		$this->cache=new ObjectCache();
	}

	function getClass($name) {
		$name=strtolower($name);
		if(!isset($this->classes[$name])) {
			return null;
		}
		$ob=$this->cache->get($name);
		if(!$ob) {
			$ob = Grabber::getClassFromFile($this->classes[$name], $name, Class_::class);
			if($ob) {
				$this->cache->add($name, $ob);
			}
		}
		return $ob;
	}

	function getInterface($name) {
		$name=strtolower($name);
		if(!isset($this->interfaces[$name])) {
			return null;
		}
		$ob=$this->cache->get($name);
		if(!$ob) {
			$ob = Grabber::getClassFromFile($this->interfaces[$name], $name, Interface_::class);
			if($ob) {
				$this->cache->add($name, $ob);
			}
		}
		return $ob;
	}

	function getFunction($name) {
		$name=strtolower($name);
		if(!isset($this->functions[$name])) {
			return null;
		}
		$ob=$this->cache->get($name);
		if(!$ob) {
			$ob = Grabber::getClassFromFile($this->functions[$name], $name, Function_::class);
			if($ob) {
				$this->cache->add($name, $ob);
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
}