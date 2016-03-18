<?php

namespace Scan\Checks;

class ParamTypesCheck extends BaseCheck
{
	function run($fileName, $method) {
		foreach ($method->params as $index => $param) {
			$name1 = $param->type->toString();
			$nameLower = strtolower($name1);
			if ($nameLower != "" && $nameLower != "array" && $nameLower != "callable") {
				$file = $this->symbolTable->getClassFile($name1) ?: $this->symbolTable->getInterfaceFile($name1);
				$this->incTests();
				if (!$file && !$this->symbolTable->ignoreType($name1)) {
					$this->emitError($fileName,$method,"Unknown type", "Reference to an unknown type $name1 in parameter of " . $method->name);
				}
			}
		}
	}
}