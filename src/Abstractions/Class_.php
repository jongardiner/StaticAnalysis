<?php
namespace Guardrail\Abstractions;

use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Interface_;
use Guardrail\NodeVisitors\Grabber;
use Guardrail\Abstractions\ClassInterface;
use Guardrail\Abstractions\ClassMethod;

class Class_ implements ClassInterface {
	private $class;

	function __construct(\PhpParser\Node\Stmt\ClassLike $class) {
		$this->class = $class;
	}
	function getName() {
		return strval($this->class->namespacedName);
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

	function isInterface() {
		return $this->class instanceof \PhpParser\Node\Stmt\Interface_;
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

	function hasConstant($name) {
		$constants = Grabber::filterByType($this->class->stmts, ClassConst::class);
		foreach($constants as $constList) {
			foreach($constList->consts as $const) {
				if (strcasecmp($const->name, $name) == 0) {
					return true;
				}
			}
		}
		return false;
	}
}