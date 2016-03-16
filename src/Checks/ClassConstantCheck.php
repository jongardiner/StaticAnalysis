<?php
namespace Scan\Checks;

use Scan\NodeVisitors\Grabber;

class ClassConstantCheck extends BaseCheck {

	function run($fileName, $node) {
		if ($node->class instanceof Name) {
			$name = Util::implodeParts($node->class);
			// Todo
			if ($name == 'self' || $name == 'static' || $name == 'parent') {
				//echo "Static fetch to $name\n";
				return;
			}
			if ($this->symbolTable->ignoreType($name)) {
				return;
			}

			$class = $this->symbolTable->getClass($name);
			if (!$class) {
				$class = $this->symbolTable->getInterface($name);
				if (!$class) {
					$this->emitError("Missing classs",
						"$fileName " . $node->getLine() . ": That's not a thing.  Can't find class/interface $name"
					);
					return;
				}
			}
			$line = $node->getLine();
			$constantName = $node->name;

			if ($node->name != 'class') {
				while ($class) {
					$const = Grabber::getClassFromStmts($class->stmts, $constantName, \PhpParser\Node\Stmt\Class_::class, Grabber::FROM_NAME);
					if ($const) {
						return;
					}

					if ($class->extends) {
						$lastClass = get_class($class);
						$className = Util::implodeParts($class->extends);
						$class = null;
						$fileName = $this->symbolTable->getClassFile($className);
						if (!$fileName) {
							$fileName = $this->symbolTable->getInterfaceFile($className);
						}

						if ($fileName) {
							$class = Grabber::getClassFromFile($fileName, $className, $lastClass);
						}
					} else {
						break;
					}
				}
				$this->emitError("Constant",
					$fileName . " " . $line . ": reference to unknown constant $name::$constantName"
				);
			}
		}
	}
}