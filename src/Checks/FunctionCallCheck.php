<?php
namespace Scan\Checks;

use PhpParser\Node\Name;

class FunctionCallCheck extends BaseCheck
{
	/**
	 * @param string                        $fileName
	 * @param \PhpParser\Node\Expr\FuncCall $node
	 */
	function run($fileName, $node) {

		if ($node->name instanceof Name) {
			$name = $node->name->toString();
			$function = $this->symbolTable->getFunction($name);
			$this->incTests();
			if (!$function) {
				$this->emitError("Function",
					$fileName . " " . $node->getLine() . " call to unknown function $name"
				);
			} else {
				$minimumArgs = 0;
				foreach($function->params as $param) {
					if($param->default) break;
					$minimumArgs++;
				}
				if(count($node->args)<$minimumArgs) {
					$this->emitError("Function",
						$fileName." ".$node->getLine()." function call parameter count mismatch to function $name (passed ".count($node->args)." requires $minimumArgs)"
					);
				}
			}
		}
	}
}