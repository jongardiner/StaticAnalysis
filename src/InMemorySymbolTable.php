<?php namespace Scan;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;

class InMemorySymbolTable implements SymbolTableInterface {
	private $classes = [];
	private $files = [];
	private $interfaces;
	private $cache;

	function __construct() {
		$this->cache=new ObjectCache();
	}

	function addClass($name, Class_ $class, $file) {
		echo "\tAdding $name\n";
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
			$ob = Grabber::getClassFromFile($this->classes[$name], $name);
			if($ob) {
				$this->cache->add($name, $ob);
			}
		}
		return $ob;
	}

	function getClassFile($name) {
		return $this->classes[strtolower($name)];
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
		foreach( $class->stmts as $stmt) {
			if($stmt instanceof ClassMethod) {
				$ret[]=$stmt;
			}
		}
		return $ret;
	}

	function ignoreType($name) {
		$name=strtolower($name);
		return
			$name=='datetime' ||
			$name=='domelement' ||
			$name=='mail_mime' ||
			$name=='datetimezone' ||
			$name=='dateinterval' ||
			$name=='exception' ||
			$name=='splmaxheap' ||
			$name=='xmlwriter' ||
			$name=='html_quickform2_element' ||
			$name=='reflectionclass' ||
			$name=='stdclass' ||
			$name=='invalidargumentexception' ||
			$name=='domainexception' ||
			$name=='intldateformatter' ||
			$name=='xmlreader' ||
			$name=='recursivedirectoryiterator' ||
			$name=='recursiveiteratoriterator' ||
			$name=='regexiterator' ||
			$name=='simplexmlelement' ||
			$name=='runtimeexception';

	}
}
