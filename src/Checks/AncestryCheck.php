<?php
namespace Scan\Checks;

use PhpParser\Node\Stmt\ClassLike;
use Scan\Abstractions\Class_;
use Scan\Abstractions\ClassMethod;
use Scan\Scope;

class AncestryCheck extends BaseCheck {

	function getCheckNodeTypes() {
		return [\PhpParser\Node\Stmt\Class_::class];
	}

	/**
	 * @param string $fileName
	 * @param \PhpParser\Node\Stmt\Class_ $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {
		$current = $node;
		$node = new Class_($node);

		while ($node && $node->getParentClassName()) {
			$parent = $node->getParentClassName();
			if ($this->symbolTable->ignoreType($parent)) {
				return;
			} else {
				$node = $this->symbolTable->getAbstractedClass($parent);
				$this->incTests();
				if (!$node) {
					$this->emitError($fileName,$current,"Unknown class", "Unable to find parent $parent");
				}
			}
		}
	}
}