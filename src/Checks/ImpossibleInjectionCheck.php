<?php
namespace Scan\Checks;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use Scan\Scope;
use Scan\Util;

class ImpossibleInjectionException extends \Exception { }

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
			throw new ImpossibleInjectionException("Dependency loop detected trying to inject $className\n");
		}
		if (in_array($className, $available)) {
			return true;
		} else {
			array_push($used, strval($className));
			$dependencies = $this->getConstructorDependencies($className);
			if (count($dependencies)==0) {
				return true;
			}

			foreach ($dependencies as $dependencyName) {
				if(empty($dependencyName) || self::isNonClass($dependencyName)) {
					throw new ImpossibleInjectionException("Constructor for $className doesn't type hint a parameter");
				}
				$class = $this->symbolTable->getAbstractedClass($dependencyName);
				if(!$class) {
					throw new ImpossibleInjectionException("Unknown class");
				}
				if($class->isDeclaredAbstract() && !in_array($className, $available)) {
					throw new ImpossibleInjectionException("Abstract class $className is not available");
				}
				if($class->isInterface() && !in_array($className, $available)) {
					throw new ImpossibleInjectionException("Interface $className is not available");
				}
				if(!$this->isInjectable($dependencyName, $available, $used)) {

					// Something else will throw an exception, so we don't need to in the recursive case.
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
					$name = $node->args[0]->value;
					$classes = $node->args[1]->value;
					if(
						$name instanceof ClassConstFetch &&
						$name->class instanceof Name &&
						strcasecmp($name->name,"class")==0 &&
						$classes instanceof Array_

					) {
						$nameString = $name->class;
						$availableObjects=$classes->items;
						$available=[];
						foreach($availableObjects as $item) {
							$key=$item->key;
							if($key instanceof ClassConstFetch && strcasecmp($key->name,"class")==0 && $key->class instanceof Name) {
								$available[] = strval($key->class);
							}
						}
						try {
							if (!$this->isInjectable($nameString, $available)) {
								$this->emitError($fileName, $node, "BambooHR.Impossible.Inject", "Impossible call to inject() for $nameString:");
							}
						} catch(ImpossibleInjectionException $ex) {
							$this->emitError($fileName, $node,"BambooHR.Impossible.Inject", "Impossible call to inject for $nameString: ".$ex->getMessage());
						}
						return;
					}
				}
				$this->emitError($fileName, $node, "BambooHR.Impossible.Inject", "Can't analyze inject call");
			}
		}
	}
}