<?php
namespace Scan\Abstractions;

class ReflectedClass implements ClassInterface {
	/**
	 * @var \ReflectionClass
	 */
	private $refl;

	function __construct(\ReflectionClass $refl) {
		$this->refl = $refl;
	}

	function getParentClassName() {
		$parent = $this->refl->getParentClass();
		return $parent ? $parent->getName() : "";
	}

	function getInterfaceNames() {
		return $this->refl->getInterfaceNames();
	}

	function isDeclaredAbstract() {
		return $this->refl->isAbstract();
	}

	function getMethodNames() {
		$ret = [];
		foreach($this->refl->getMethods() as $method) {
			$ret[] = $method->name;
		}
		return $ret;
	}

	function getMethod($name) {
		try {
			$method = $this->refl->getMethod($name);
			if ($method) return new ReflectedClassMethod($method);
		}
		catch(\ReflectionException $e) {
			return null;
		}
		return null;
	}

	function getName() {
		return $this->refl->getName();
	}
}