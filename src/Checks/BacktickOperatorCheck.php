<?php

namespace Scan\Checks;

use PhpParser\Node\Stmt\ClassLike;
use Scan\Scope;
use Scan\Util;

class BacktickOperatorCheck extends BaseCheck
{
	/**
	 * @param string $fileName
	 * @param \PhpParser\Node\Stmt\Catch_ $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {
		$this->incTests();
		$this->emitError($fileName,$node,"Security", "Unsafe operator (backtick)");
	}
}