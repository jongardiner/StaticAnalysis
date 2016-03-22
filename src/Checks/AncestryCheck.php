<?php
namespace Scan\Checks;

use PhpParser\Node\Stmt\ClassLike;

class AncestryCheck extends BaseCheck {

	/**
	 * @param string $fileName
	 * @param \PhpParser\Node\Stmt\Class_ $node
	 */
	function run($fileName, $node, ClassLike $inside=null) {
		$current = $node;
		while ($node && $node->extends) {
			$parent = $node->extends->toString();
			if ($this->symbolTable->ignoreType($parent)) {
				return;
			} else {
				$node = $this->symbolTable->getClass($parent);
				$this->incTests();
				if (!$node) {
					$this->emitError($fileName,$current,"Unknown class", "Unable to find parent $parent");
				}
			}
		}
	}
}