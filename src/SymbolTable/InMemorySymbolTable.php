<?php namespace Scan\SymbolTable;

use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Function_;

class InMemorySymbolTable extends BaseSymbolTable implements SymbolTableInterface {
	private $classes = [];
	private $functions = [];
	private $interfaces = [];

	function __construct() {

	}

	function addFunction($name, Function_ $function, $file) {
		$this->functions[strtolower($name)]=$file;
	}

	function addClass($name, Class_ $class, $file) {
		$this->classes[strtolower($name)]= $file;
	}

	function addInterface($name, Interface_ $interface, $file) {
		$this->interfaces[strtolower($name)]=$file;
	}

	function getInterfaceFile($name) {
		return $this->interfaces[strtolower($name)];
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
}
