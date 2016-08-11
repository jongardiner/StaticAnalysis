<?php

namespace Guardrail\Checks;

use PhpParser\Node\Stmt\ClassLike;
use Guardrail\Checks\BaseCheck;
use Guardrail\Scope;
use Guardrail\Util;

class BacktickOperatorCheck extends BaseCheck
{
	function getCheckNodeTypes() {
		return [\PhpParser\Node\Expr\ShellExec::class];
	}

	/**
	 * @param string $fileName
	 * @param \PhpParser\Node\Stmt\Catch_ $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {
		$this->incTests();
		$this->emitError($fileName,$node,self::TYPE_SECURITY_BACKTICK, "Unsafe operator (backtick)");
	}
}