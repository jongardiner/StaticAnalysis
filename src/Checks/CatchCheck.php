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

		$name = Util::implodeParts($node->type);
		if ($this->symbolTable->ignoreType($name)) {
			return;
		}
		if (!$this->symbolTable->getClassFile($name) && !$this->symbolTable->getInterfaceFile($name)) {
			$this->emitError('Unknown class',
				$fileName . " " . $node->getLine() . ": attempt to catch unknown type: $name"
			);
		}
	}
}