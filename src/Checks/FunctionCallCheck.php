<?php
namespace Scan\Checks;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;

class FunctionCallCheck extends BaseCheck
{
	/**
	 * @param string                        $fileName
	 * @param \PhpParser\Node\Expr\FuncCall $node
	 */
	function run($fileName, $node, ClassLike $inside=null) {

		if ($node->name instanceof Name) {
			$name = $node->name->toString();
			$function = $this->symbolTable->getFunction($name);
			$this->incTests();
			if (!$function) {
				$this->emitError($fileName,$node,"Unknown function", "Call to unknown function $name");
			} else {
				$minimumArgs = 0;
				foreach($function->params as $param) {
					if($param->default) break;
					$minimumArgs++;
				}
				if(count($node->args)<$minimumArgs) {
					$this->emitError($fileName,$node,"Signature mismatch", "Function call parameter count mismatch to function $name (passed ".count($node->args)." requires $minimumArgs)");
				}
			}
		}
	}
}