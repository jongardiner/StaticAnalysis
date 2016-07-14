<?php
namespace Scan\Checks;

use PhpParser\Node\Stmt\ClassLike;
use Scan\Scope;
use Scan\Util;

class CatchCheck extends BaseCheck
{
	/**
	 * @param string $fileName
	 * @param \PhpParser\Node\Stmt\Catch_ $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {

		$name = $node->type->toString();
		if ($this->symbolTable->ignoreType($name)) {
			return;
		}
		$this->incTests();
		if (!$this->symbolTable->getAbstractedClass($name)) {
			$this->emitError($fileName,$node,"Unknown class/interface", "Attempt to catch unknown type: $name");
		}
	}
}