<?php namespace Scan;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Function_;

class InMemorySymbolTable implements SymbolTableInterface {
	private $classes = [];
	private $files = [];
	private $functions = [];
	private $interfaces;
	private $cache;

	function __construct() {
		$this->cache=new ObjectCache();
	}

	function addFunction($name, Function_ $function, $file) {
		$this->functions[strtolower($name)]=$file;
		$this->files[$file]=true;
	}

	function addClass($name, Class_ $class, $file) {
		$this->classes[strtolower($name)]= $file;
		$this->files[ $file ] = true;
	}

	function addInterface($name, Interface_ $interface, $file) {
		$this->interfaces[strtolower($name)]=$file;
		$this->files[$file]=true;
	}

	function getInterfaceFile($name) {
		return $this->interfaces[strtolower($name)];
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

	function getClassFile($name) {
		return $this->classes[strtolower($name)];
	}

	function getFunctionFile($name) {
		return $this->functions[strtolower($name)];
	}

	function addMethod($className, $methodName, ClassMethod $method) {
		// Do nothing.
	}

	function getAllClassNames() {
		return array_keys($this->classes);
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
