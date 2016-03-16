<?php
namespace Scan\Checks;

use Scan\NodeVisitors\Grabber;
use PhpParser\Node\Name;

class ClassConstantCheck extends BaseCheck {

	function run($fileName, $node) {
		if ($node->class instanceof Name) {
			$name = $node->class->toString();
			$constantName = strval($node->name);

			// Todo
			if ($name == 'self' || $name == 'static' || $name == 'parent') {
				//echo "Static fetch of $name::$constantName\n";
				return;
			}
			if ($this->symbolTable->ignoreType($name)) {
				return;
			}

			$this->incTests();
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


			if ($node->name != 'class') {
				while ($class) {
					$constants = Grabber::filterByType($class->stmts, \PhpParser\Node\Stmt\ClassConst::class);
					foreach($constants as $constList) {
						foreach($constList->consts as $const) {
							if (strcasecmp($const->name, $constantName) == 0) {
								return;
							}
						}
					}

					if ($class->extends) {
						$lastClass = get_class($class);
						$className = strval($class->extends);
						$class = null;
						$parentFileName = $this->symbolTable->getClassFile($className);
						if (!$parentFileName) {
							$parentFileName = $this->symbolTable->getInterfaceFile($className);
						}

						if ($fileName) {
							$class = Grabber::getClassFromFile($parentFileName, $className, $lastClass);
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