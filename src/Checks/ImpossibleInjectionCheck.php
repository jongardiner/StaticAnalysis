<?php
namespace Scan\Checks;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use Scan\Scope;
use Scan\Util;

class ImpossibleInjectionCheck extends BaseCheck
{
	function getCheckNodeTypes() {
		return [FuncCall::class];
	}

	static function isNonClass($name) {
		static $types = ['array', 'callable', 'int', 'string', 'bool', 'float'];
		$name=strtolower($name);
		return in_array($name, $types);
	}

	function isInjectable($className, $available, $used=[]) {
		if(in_array($className, $used)) {
			// We've detected a loop.  Therefore, this is not injectable.
			return false;
		}
		if (in_array($className, $available)) {
			return true;
		} else {
			array_push($used, $className);
			$dependencies = $this->getConstructorDependencies($className);
			if (count($dependencies)==0) {
				return true;
			}

			foreach ($dependencies as $dependencyName) {
				if(empty($dependencyName) || self::isNonClass($dependencyName)) {
					return false;
				}
				$class = $this->symbolTable->getAbstractedClass($dependencyName);
				if(
					!$class ||
					$class->isDeclaredAbstract() ||
					($class->isInterface() && !in_array($className, $available)) ||
					!$this->isInjectable($dependencyName, $available, $used)
				) {
					return false;
				}
			}
			return true;
		}
	}

	function getConstructorDependencies($className) {
		$method = Util::findAbstractedMethod($className,"__construct", $this->symbolTable);
		$dependencies = [];
		foreach($method->getParameters() as $param) {
			$dependencies[]=$param->getType();
		}
		return $dependencies;
	}

	/**
	 * @param string                        $fileName
	 * @param \PhpParser\Node\Expr\FuncCall $node
	 */
	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {

		if ($node->name instanceof Name) {
			$name = $node->name->toString();

			$toLower = strtolower($name);
			$this->incTests();
			if($toLower=='inject') {
				if(count($node->args)==2) {
					$name = $node->args[0];
					$classes = $node->args[1];
					if($name instanceof ClassConstFetch && $classes instanceof Array_ && $name->class instanceof Name && strcasecmp($name->name,"class")==0) {
						$nameString = $name->class;
						$availableObjects=$classes->items;
						$available=[];
						foreach($availableObjects as $item) {
							$key=$item->key;
							if($key instanceof ClassConstFetch && strcasecmp($key->name,"class")==0 && $key->class instanceof Name) {
								$available[] = $key->name;
							}
						}
						if(!$this->isInjectable($nameString, $available)) {
							$this->emitError($fileName, $node, "BambooHR.Impossible.Inject", "Impossible call to inject() for $nameString::class");
						}
						return;
					}
				}
				$this->emitError($fileName, $node, "BambooHR.Impossible.Inject", "Can't analyze inject call");
			}
		}
	}
}