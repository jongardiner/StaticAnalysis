<?php namespace Scan;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;

class SymbolTable {
	private $classMethods = [];
	private $classes = [];

	function addClass($name, Class_ $class) {
		$this->classes[$name]=$class;
	}

	function getClass($name) {
		return array_key_exists($name,$this->classes) ? $this->classes[$name] : null;
	}

	function addMethod($className, $methodName, ClassMethod $method) {
		echo "\t$className::$methodName\n";
		$this->classMethods[$className][$methodName]=$method;
	}

	function getClasses() {
		return $this->classes;
	}

	function getClassMethod($className, $methodName) {
		return array_key_exists($className, $this->classMethods) && 
			array_key_exists($methodName, $this->classMethods[$className]) 
			?  $this->classMethods[$className][$methodName] : null;
	}

	function getClassMethods($className) {
		return $this->classMethods[$className];
	}
}
