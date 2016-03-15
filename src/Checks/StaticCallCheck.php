<?php
namespace Scan\Checks;

class StaticCallCheck extends BaseCheck
{
	function run($fileName, $call) {
		if ($call->class instanceof Name) {
			$name = Util::implodeParts($call->class);
			// Todo
			if ($name == 'self' || $name == 'static' || $name == 'parent') {
				//echo "Static call to $name\n";
				return;
			}
			if ($this->symbolTable->ignoreType($name)) {
				return;
			}


			$class = $this->symbolTable->getClass($name);
			if (!$class) {
				if (!$this->symbolTable->ignoreType($name)) {
					$this->primaryCase->addError('Unknown interface')->appendChild(new DomText(
						$fileName . $call->getLine() . ": Static call to unknown class " . Util::implodeParts($call->class) . "::" . $call->name
					));
				}
			} /*else {
					$method=$this->symbolTable->getClassMethod($name, $call->name);
					if (!$method) {
						$method=$this->findParentWithMethod($class, $name);
						if(!$method) {
							echo "$fileName ".$call->getLine()." : Unable to find method.  $name::".$call->name."\n";
						} else {

						}
					}
				}*/
		}
	}
}