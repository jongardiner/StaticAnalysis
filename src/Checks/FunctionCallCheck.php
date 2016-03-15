<?php
namespace Scan\Checks;

class FunctionCallCheck extends BaseCheck
{
	/**
	 * @param string                       $fileName
	 * @param PhpParser\Node\Expr\FuncCall $node
	 */
	function run($fileName, $node) {
		if ($node->name instanceof Name) {
			$name = Util::implodeParts($node->name);
			$function = $this->symbolTable->getFunction($name);
			if (!$function) {
				$this->emitError("Function",
					$fileName . " " . $node->getLine() . " call to unknown function $name"
				);
			}
		}
	}
}