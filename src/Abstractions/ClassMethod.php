<?php
namespace Scan\Abstractions;

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

	function getParameters() {
		$ret = [];
		/** @var PhpParser\Node\Param $param */
		foreach ($this->method->params as $param) {
			$ret[] = new FunctionLikeParameter($param->type, $param->name, $param->default!=null);
		}
		return $ret;
	}

	function isInternal() {
		return false;
	}

	function isStatic() {
		return $this->method->isStatic();
	}

	function getName() {
		return $this->method->name;
	}
}