<?php
namespace Scan\Abstractions;

use PhpParser\Node\Stmt\Interface_;

class Class_ implements ClassInterface {
	private $class;

	function __construct(\PhpParser\Node\Stmt\ClassLike $class) {
		$this->class = $class;
	}
	function getName() {
		return $this->class->name;
	}

	function isDeclaredAbstract() {
		return ($this->class instanceof Class_ ? $this->class->isAbstract() : false);
	}

	function getMethodNames() {
		$ret = [];
		foreach($this->class->getMethods() as $method) {
			$ret[] = $method->name;
		}
		return $ret;
	}

	function getParentClassName() {
		return $this->class instanceof \PhpParser\Node\Stmt\Class_ ? strval($this->class->extends) : "";
	}

	function getInterfaceNames() {
		$ret = [];
		if($this->class instanceof Interface_) {
			foreach ($this->class->extends as $extend) {
				$ret[] = strval($extend);
			}
		} else {
			foreach ($this->class->implements as $implement) {
				$ret[] = strval($implement);
			}
		}
		return $ret;
	}

	function getMethod($name) {
		$method = $this->class->getMethod($name);
		return $method ?  new ClassMethod($method) : null;
	}
}