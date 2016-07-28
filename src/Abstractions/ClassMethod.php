<?php
namespace Scan\Abstractions;

use Scan\Util;

class ClassMethod implements FunctionLikeInterface {
	private $method;

	function __construct(\PhpParser\Node\Stmt\ClassMethod $method) {
		$this->method = $method;
	}
	function getReturnType() {
		return "";
	}

	function getMinimumRequiredParameters() {
		$minimumArgs = 0;
		foreach ($this->method->params as $param) {
			if ($param->default) break;
			$minimumArgs++;
		}
		return $minimumArgs;
	}

	/**
	 * @return FunctionLikeParameter
	 */
	function getParameters() {
		$ret = [];
		/** @var \PhpParser\Node\Param $param */
		foreach ($this->method->params as $param) {
			$ret[] = new FunctionLikeParameter($param->type, $param->name, $param->default!=null, $param->byRef);
		}
		return $ret;
	}

	function getAccessLevel() {
		return Util::getMethodAccessLevel($this->method);
	}

	function isInternal() {
		return false;
	}

	function isAbstract() {
		return $this->method->isAbstract();
	}

	function isStatic() {
		return $this->method->isStatic();
	}

	function getName() {
		return $this->method->name;
	}

	function getStartingLine() {
		return $this->method->getLine();
	}
}