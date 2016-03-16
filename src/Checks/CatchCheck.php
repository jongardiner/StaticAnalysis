<?php
namespace Scan\Checks;

use Scan\Util;

class CatchCheck extends BaseCheck
{
	/**
	 * @param string $fileName
	 * @param PhpParser\Node\Stmt\Catch_ $node
	 */
	function run($fileName, $node) {

		$name = $node->type->toString();
		if ($this->symbolTable->ignoreType($name)) {
			return;
		}
		$this->incTests();
		if (!$this->symbolTable->getClassFile($name) && !$this->symbolTable->getInterfaceFile($name)) {
			$this->emitError('Unknown class',
				$fileName . " " . $node->getLine() . ": attempt to catch unknown type: $name"
			);
		}
	}
}