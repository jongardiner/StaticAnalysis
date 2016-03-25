<?php
namespace Scan\Checks;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Trait_;
use Scan\Util;


class MethodCall extends BaseCheck
{

	/**
	 * @param                                    $fileName
	 * @param \PhpParser\Node\Expr\MethodCall $node
	 */
	function run($fileName, $node, ClassLike $inside=null) {
		if($node->var instanceof Variable) {
			//echo "Call ".$node->var->name." ".$node->name."()\n";
			if($node->var->name=='this') {
				if($inside instanceof Trait_) {
					return;
				}
				if(!$inside) {
					$this->emitError($fileName, $node, "Scope error", "Can't use \$this outside of a class");
					return;
				}
				if(!($node->name instanceof Name)) {
					// Variable method name.  Yuck!
					return;
				}
				$method = Util::findMethod($inside,$node->name, $this->symbolTable);
				if(!$method) {
					$this->emitError($fileName, $node, "Unknown method", "Call to unknown method of $inside->namespacedName: \$this->".$node->name);
					return;
				}
				if($method->isStatic()) {
					$this->emitError($fileName, $node, "Unknown method", "Call to call static method of $inside->namespacedName: \$this->".$node->name." non-statically");
					return;
				}
				$minimumArgs = 0;
				foreach($method->params as $param) {
					if($param->default) break;
					$minimumArgs++;
				}
				if(count($node->args)<$minimumArgs) {
					$this->emitError($fileName,$node,"Signature mismatch", "Function call parameter count mismatch to method ".$method->name." (passed ".count($node->args)." requires $minimumArgs)");
				}
			}
		}
	}
}
