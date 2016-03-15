<?php

namespace Scan\Checks;

class ParamTypesCheck extends BaseCheck
{
	function run($fileName, $method) {
		foreach ($method->params as $index => $param) {
			$name1 = Util::implodeParts($param->type);
			$nameLower = strtolower($name1);
			if ($nameLower != "" && $nameLower != "array" && $nameLower != "callable") {
				$file = $this->symbolTable->getClassFile($name1) ?: $this->symbolTable->getInterfaceFile($name1);
				if (!$file && !$this->symbolTable->ignoreType($name1)) {
					echo $fileName . " " . $method->getLine() . ":reference to an unknown type $name1 in parameter of " . $method->name . "\n";
				}
			}
		}
	}
}