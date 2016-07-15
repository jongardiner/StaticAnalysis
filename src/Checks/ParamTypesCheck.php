<?php

namespace Scan\Checks;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use Scan\Scope;

class ParamTypesCheck extends BaseCheck
{
	function getCheckNodeTypes() {
		return [
			\PhpParser\Node\Stmt\ClassMethod::class,
			\PhpParser\Node\Stmt\Function_::class,
			\PhpParser\Node\Expr\Closure::class
		];
	}

	function run($fileName, $method, ClassLike $inside=null, Scope $scope=null) {
		foreach ($method->params as $index => $param) {
			if($param->type) {
				$name1 = strval($param->type);
				$nameLower = strtolower($name1);
				if($nameLower=="self" && $inside instanceof Class_) {
					continue; // No need to consult the symbol table, we're in the class in question.
				}
				if ($nameLower != "" && $nameLower != "array" && $nameLower != "callable") {
					$class = $this->symbolTable->getAbstractedClass($name1);
					$this->incTests();
					if (!$class && !$this->symbolTable->ignoreType($name1)) {
						$this->emitError($fileName, $method, "Unknown type", "Reference to an unknown type $name1 in parameter $index of " . $method->name);
					}
				}
			}
		}
	}
}