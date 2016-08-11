<?php
namespace Guardrail\Abstractions;

use PhpParser\Node\Stmt\Function_ as AstFunction;
use Guardrail\NodeVisitors\VariadicCheckVisitor;

class Function_ implements FunctionLikeInterface {
	private $function;

	function __construct(AstFunction $method) {
		$this->function = $method;
	}
	function getReturnType() {
		return "";
	}

	function getMinimumRequiredParameters() {
		$minimumArgs = 0;
		foreach ($this->function->params as $param) {
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
		foreach ($this->function->params as $param) {
			$ret[] = new FunctionLikeParameter($param->type, $param->name, $param->default!=null, $param->byRef);
		}
		return $ret;
	}

	function isInternal() {
		return false;
	}


	function getName() {
		return $this->function->name;
	}

	function getStartingLine() {
		return $this->function->getLine();
	}


	function isVariadic() {
		foreach($this->function->getParams() as $param) {
			if($param->variadic) {
				return true;
			}
		}
		if($this->function instanceof Function_ || $this->function instanceof \PhpParser\Node\Stmt\ClassMethod) {
			return VariadicCheckVisitor::isVariadic($this->function->getStmts());
		}
		return false;
	}
}