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

	function getCheckNodeTypes() {
		return [\PhpParser\Node\Expr\ClassConstFetch::class];
	}

	/**
	 * @param ClassLike $class
	 * @param string    $constantName
	 * @return ClassConst
	 */
	function findConstant(\Scan\Abstractions\ClassInterface $class, $constantName) {
		if($class->hasConstant($constantName)) {
			return true;
		}

		if ($class->getParentClassName()) {
			$parentClass = $this->symbolTable->getAbstractedClass($class->getParentClassName());
			if ($parentClass && $this->findConstant($parentClass, $constantName)) {
				return true;
			}
		}

		foreach($class->getInterfaceNames() as $interfaceName) {
			$interface=$this->symbolTable->getAbstractedClass($interfaceName);
			if($interface && $this->findConstant($interface, $constantName)) {
				return true;
			}
		}

		return false;
	}

	function run($fileName, $node, ClassLike $inside=null, Scope $scope=null) {
		if ($node->class instanceof Name) {
			$name = $node->class->toString();
			$constantName = strval($node->name);
			if ($constantName == 'class') {
				return;
			}

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
			$class = $this->symbolTable->getAbstractedClass($name);
			if (!$class) {
				$this->emitError($fileName,$node,"Unknown class/interface", "That's not a thing.  Can't find class/interface $name");
				return;
			}

			if(!$this->findConstant($class, $constantName)) {
				$this->emitError($fileName, $node, "Unknown constant", "Reference to unknown constant $name::$constantName");
			}
		}
	}
}