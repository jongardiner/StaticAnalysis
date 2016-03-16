<?php
namespace Scan\Checks;

use PhpParser\Node\Param;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;

class StaticCallCheck extends BaseCheck
{
	protected function findMethod(Class_ $node, $name) {
		while ($node) {
			$methods = \Scan\NodeVisitors\Grabber::filterByType($node->stmts, \PhpParser\Node\Stmt\ClassMethod::class);
			foreach($methods as $method) {
				if(strcasecmp($method->name,$name)==0) {
					return $method;
				}
			}
			if ($node->extends) {
				$parent = $node->extends->toString();
				$node = $this->symbolTable->getClass($parent);
			} else {
				return null;
			}
		}
		return null;
	}

	/**
	 * @param $fileName
	 * @param \PhpParser\Node\Expr\StaticCall $call
	 */
	function run($fileName, $call) {
		if ($call->class instanceof Name && $call->name instanceof Name) {
			$name = $call->class->toString();
			// Todo
			if ($name == 'self' || $name == 'static' || $name == 'parent') {
				//echo "Static call to $name\n";
				return;
			}
			if ($this->symbolTable->ignoreType($name)) {
				return;
			}


			$this->incTests();
			$class = $this->symbolTable->getClass($name);
			if (!$class) {
				if (!$this->symbolTable->ignoreType($name)) {
					$this->emitError('Static call',
						$fileName . $call->getLine() . ": Static call to unknown class $name::" . $call->name
					);
				}
			} else {

				$method=$this->findMethod($class, $call->name);

				if(!$method) {
					$this->emitError('Static call',
						$fileName . $call->getLine() . ": Unable to find method.  $name::".$call->name
					);
				} else {
					$minimumParams=0;
					/** @var \PhpParser\Node\Param $param */
					foreach($method->params as $param) {
						if($param->default) break;
						$minimumParams++;
					}
					if(count($call->args)<$minimumParams) {
						$this->emitError("Static call",
							$fileName." ".$call->getLine().": static call to method $name::".$call->name." does not pass enough parameters (".count($call->args)." passed $minimumParams required)"
						);
					}
				}
			}
		}
	}
}