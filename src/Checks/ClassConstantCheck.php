<?php
namespace Scan\Checks;

use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Trait_;
use Scan\NodeVisitors\Grabber;
use PhpParser\Node\Name;
use Scan\Scope;

class ClassConstantCheck extends BaseCheck {

	/**
	 * @param ClassLike $class
	 * @param string    $constantName
	 * @return ClassConst
	 */
	function findConstant(ClassLike $class, $constantName) {
		$constants = Grabber::filterByType($class->stmts, ClassConst::class);
		foreach($constants as $constList) {
			foreach($constList->consts as $const) {
				if (strcasecmp($const->name, $constantName) == 0) {
					return $const;
				}
			}
		}

		if ($class->extends) {
			if(is_array($class->extends)) {
				// It's an interface, look for the constant in parent interfaces.
				foreach($class->extends as $name) {
					$class=$this->symbolTable->getInterface($name);
					if($class) {
						$const=$this->findConstant($class, $constantName);
						if($const) {
							return $const;
						}
					}
				}
			} else {
				// It's a class.  Look for the constant in the parent class.
				$className = strval($class->extends);
				$parentClass = $this->symbolTable->getClass($className);
				if ($parentClass) {
					$const = $this->findConstant($parentClass, $constantName);
					if ($const) {
						return $const;
					}
				}
			}
		}

		// It's a class.  Look for the constant in parent interfaces
		if($class instanceof Class_ && $class->implements) {
			foreach($class->implements as $name) {
				$interface=$this->symbolTable->getInterface($name);
				if($interface) {
					$const = $this->findConstant($interface, $constantName);
					if($const) {
						return $const;
					}
				}
			}
		}
		return null;
	}

	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {
		if ($node->class instanceof Name) {
			$name = $node->class->toString();
			$constantName = strval($node->name);
			if($inside instanceof Trait_) {
				// We can't check constant references inside of traits.
				// Instead, we import them into the target class and check them there.
				return;
			}

			if ($this->symbolTable->ignoreType($name)) {
				return;
			}

			switch(strtolower($name)) {
				case 'self':
				case 'static':
					if(!$inside) {
						$this->emitError($fileName, $node, "Scope error", "Can't access using self:: outside of a class");
						return;
					}
					$name = $inside->namespacedName;
					break;
				case 'parent':
					if(!$inside) {
						$this->emitError($fileName, $node, "Scope error", "Can't access using parent:: outside of a class");
						return;
					}
					if ($inside->extends) {
						$name = strval($inside->extends);
					} else {
						$this->emitError($fileName, $node, "Scope error", "Can't access using parent:: in a class with no parent");
						return;
					}
					break;
			}

			$this->incTests();
			$class = $this->symbolTable->getClass($name);
			if (!$class) {
				$class = $this->symbolTable->getInterface($name);
				if (!$class) {
					$this->emitError($fileName,$node,"Unknown class/interface", "That's not a thing.  Can't find class/interface $name");
					return;
				}
			}

			if ($constantName != 'class') {
				if(!$this->findConstant($class, $constantName)) {
					$this->emitError($fileName, $node, "Unknown constant", "Reference to unknown constant $name::$constantName");
				}
			}
		}
	}
}