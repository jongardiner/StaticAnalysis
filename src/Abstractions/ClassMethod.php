<?php
namespace Scan\Abstractions;

use PhpParser\Node\Stmt\Function_ as ParserFunction;
use Scan\NodeVisitors\VariadicCheckVisitor;
use Scan\Util;

class ClassMethod implements MethodInterface {
	private $method;

	function __construct(\PhpParser\Node\Stmt\ClassMethod $method) {
		$this->method = $method;
	}
	function getReturnType() {
		$type = $this->method->getAttribute("namespacedReturn");
		return ($type?$type:"");
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
	 * @return FunctionLikeParameter[]
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

	function isVariadic() {
		foreach($this->method->getParams() as $param) {
			if($param->variadic) {
				return true;
			}
		}
		if($this->method instanceof ParserFunction || $this->method instanceof \PhpParser\Node\Stmt\ClassMethod) {
			return VariadicCheckVisitor::isVariadic($this->method->getStmts());
		}
		return false;
	}
}