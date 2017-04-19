<?php
namespace Guardrail\Checks;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;
use Guardrail\Scope;
use Guardrail\Util;

class ImpossibleInjectionCheck extends BaseCheck
{
	function getCheckNodeTypes() {
		return [FuncCall::class];
	}

	static function isNonClass($name) {
		static $types = ['array', 'callable', 'int', 'string', 'bool', 'float'];
		$name = strtolower($name);
		return in_array($name, $types);
	}

	static private function getInjectableDependencies() {
		static $arr = [
			\Memcache::class                                      => [],
			\BambooHR\Domain\DB\CommonDb::class                   => [],
			\BambooHR\Domain\DB\MainDB::class                     => [],
			\Company::class                                       => [\BambooHR\Domain\DB\CompanyDb::class],
			\CompanyMemcache::class                               => [\Company::class],
			\CompanyMaster::class                                 => [\Company::class],
			\BambooHR\Repository\BLocale::class                   => [\BLocale::class],
			\BambooHR\Common\DataObjects\AuthenticatedUser::class => [],
			\BambooHR\Domain\DB\TrackingDb::class                 => [\BambooHR\Domain\DB\CompanyDb::class]
		];
		return $arr;
	}

	// These don't take parameters, but they still can't be instantiated directly.
	static $injectionBlackList = [
		\Company::class,
		\Memcache::class,
		\DB::class,
	];

	// These will be associated with some class or another.  We just trust that they will inject correctly.
	static $knownBoundInstances = [
		\BambooHR\Common\DataObjects\AuthenticatedUser::class => true,
		\BambooHR\Controller\ClientState\PermissionsClientStateInterface::class => true
	];


	function isAutoInjectable( $className, $available) {
		if(array_key_exists($className, self::$knownBoundInstances)) {
			return true;
		}
		$deps = self::getInjectableDependencies();

		if(array_key_exists($className, $deps)) {
			if(count($deps[$className])>0) {
				foreach($deps[$className] as $dependency) {
					if(
						!in_array($dependency, $available) &&
						!$this->isAutoInjectable($dependency, $available)
					) {
						return false;
					}
				}
			}
			return true;
		}
		return false;
	}

	function isInjectable($className, $available, $autoMode, $used = []) {
		if (in_array($className, $used)) {
			// We've detected a loop.  Therefore, this is not injectable.

			throw new ImpossibleInjectionException("Dependency loop detected trying to inject $className\n");
		}

		if (in_array($className, $available) || ($autoMode && $this->isAutoInjectable($className, $available))) {
			return true;
		} else {
			array_push($used, strval($className));
			$dependencies = $this->getConstructorDependencies($className);
			if (count($dependencies) == 0 && in_array($className, self::$injectionBlackList)) {
				throw new ImpossibleInjectionException("$className is explicitly required here.  Please pass an instance");
			}

			foreach ($dependencies as $dependencyName) {
				$dependencyName = strval($dependencyName);
				if (empty($dependencyName) || self::isNonClass($dependencyName)) {
					if($autoMode && $this->isAutoInjectable($dependencyName, $available)) {
						continue;
					} else {
						throw new ImpossibleInjectionException("Constructor for $className doesn't type hint a parameter");
					}
				}
				if(strcasecmp($dependencyName,'DB')==0) {
					throw new ImpossibleInjectionException("$className is uninjectable because it requires a DB instead of a CompanyDb");
				}
				$class = $this->symbolTable->getAbstractedClass($dependencyName);
				if (!$class) {
					throw new ImpossibleInjectionException("Unknown class");
				}
				if ($class->isDeclaredAbstract() && !in_array($dependencyName, $available)) {
					throw new ImpossibleInjectionException("Abstract class $className is not available");
				}
				if ($class->isInterface() && !in_array($dependencyName, $available) && (!$autoMode || !array_key_exists($dependencyName, self::$knownBoundInstances))) {
					throw new ImpossibleInjectionException("Interface $className is not available");
				}

				if (!$this->isInjectable($dependencyName, $available, $autoMode, $used)) {
					// Something else will throw an exception, so we don't need to in the recursive case.
					return false;
				}
			}
			return true;
		}
	}

	function getConstructorDependencies($className) {
		$method = Util::findAbstractedMethod($className, "__construct", $this->symbolTable);
		$dependencies = [];
		if($method) {
			foreach ($method->getParameters() as $param) {
				$dependencies[] = strval($param->getType());
			}
		}
		return $dependencies;
	}

	/**
	 * @param string                        $fileName
	 * @param \PhpParser\Node\Expr\FuncCall $node
	 * @param ClassLike                     $inside
	 * @param Scope                         $scope
	 */
	function run($fileName, $node, ClassLike $inside = null, Scope $scope = null) {
		if ($node->name instanceof Name) {
			$name = $node->name->toString();

			$toLower = strtolower($name);
			$this->incTests();
			if ($toLower == 'inject' || $toLower=='autoinject') {
				$autoMode = $toLower=='autoinject';
				if (count($node->args) == 2) {
					$name = $node->args[0]->value;
					$classes = $node->args[1]->value;
					if (
						$name instanceof ClassConstFetch &&
						$name->class instanceof Name &&
						strcasecmp($name->name, "class") == 0 &&
						$classes instanceof Array_

					) {
						$nameString = strval($name->class);
						$availableObjects = $classes->items;
						$available = [];
						foreach ($availableObjects as $item) {
							$key = $item->key;
							if ($key instanceof ClassConstFetch && strcasecmp($key->name, "class") == 0 && $key->class instanceof Name) {
								$available[] = strval($key->class);
								if (strval($key->class) == 'BLocale') {
									$available[] = 'BambooHR\Repository\BLocale';
								}
							}
						}
						try {
							if (!$this->isInjectable($nameString, $available, $autoMode)) {
								$this->emitError($fileName, $node, "BambooHR.Impossible.Inject", "Impossible call to inject() for $nameString:");
							}
						} catch (ImpossibleInjectionException $ex) {
							$this->emitError($fileName, $node, "BambooHR.Impossible.Inject", "Impossible call to inject for $nameString: " . $ex->getMessage());
						}
						return;
					}
				}
				$this->emitError($fileName, $node, "BambooHR.Impossible.Inject", "Can't analyze inject call");
			}
		}
	}
}


class ImpossibleInjectionException extends \Exception { }

