<?php
namespace Scan\Checks;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use Scan\Scope;

class FunctionCallCheck extends BaseCheck
{
	static private $dangerous = ["exec"=>true,"shell_exec"=>true, "proc_open"=>true, "passthru"=>true, "popen"=>true, "system"=>true];
	/**
	 * @param string                        $fileName
	 * @param \PhpParser\Node\Expr\FuncCall $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {

		if ($node->name instanceof Name) {
			$name = $node->name->toString();

			$toLower = strtolower($name);
			$this->incTests();
			if(array_key_exists($toLower, self::$dangerous)) {
				// $this->emitError($fileName, $node, "Security", "Call to dangerous function $name()");
			}

			$minimumArgs = $this->getMinimumParams($name);
			if($minimumArgs<0) {
				$this->emitError($fileName,$node,"Unknown function", "Call to unknown function $name");
			}
			if(count($node->args)<$minimumArgs) {
				$this->emitError($fileName,$node,"Signature mismatch", "Function call parameter count mismatch to function $name (passed ".count($node->args)." requires $minimumArgs)");
			}
		}
	}

	function getMinimumParams($name) {
		$symbolMin = $this->getSymbolMinimumParams($name);
		return $symbolMin >= 0 ? $symbolMin : $this->getReflectedMinimumParams($name);
	}

	function getSymbolMinimumParams($name) {
		$function = $this->symbolTable->getFunction($name);
		if(!$function) {
			return -1;
		}
		$minimumArgs = 0;
		foreach($function->params as $param) {
			if($param->default) break;
			$minimumArgs++;
		}
		return $minimumArgs;
	}

	function getReflectedMinimumParams($name) {
		try {
			$func=new \ReflectionFunction($name);
			$func->getParameters();
			$minimumArgs = 0;
			foreach($func->getParameters() as $param) {
				if($param->isOptional()) break;
				$minimumArgs++;
			}
			return $minimumArgs;
		}
		catch(\ReflectionException $e) {
			return -1;
		}
	}
}