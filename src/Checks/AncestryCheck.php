<?php
namespace Scan\Checks;

use \Scan\Util;

class AncestryCheck extends BaseCheck {

	/**
	 * @param string $fileName
	 * @param \PhpParser\Node\Stmt\Class_ $node
	 */
	function run($fileName, $node) {
		$current = $node;
		while ($node && $node->extends) {
			$parent = $node->extends->toString();
			if ($this->symbolTable->ignoreType($parent)) {
				return;
			} else {
				$node = $this->symbolTable->getClass($parent);
				$this->incTests();
				if (!$node) {
					$this->emitError('Unable to find parent',
						$fileName . " " . $current->getLine() . ":Unable to find parent $parent"
					);
				}
			}
		}
	}
}