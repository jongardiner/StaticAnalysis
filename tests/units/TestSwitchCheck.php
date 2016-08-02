<?php


class TestSwitchCheck extends \PHPUnit_Framework_TestCase {

	static function parseText($txt) {
		$parser = (new \PhpParser\ParserFactory)->create(\PhpParser\ParserFactory::PREFER_PHP7);
		return $parser->parse($txt);
	}

	function testMissingBreak() {
		$code = '<?
			switch($foo) {
				case 0:
				case 5: // Empty, but with comment (This is ok)
				case 1:
					echo "Error!\n";
					// Comment
				case 2:
					echo "Another error, but no comment\n";
				case 2:
					echo "Not error\n";
					break;
				case 3:
					// Last case, also not an error
			}
		?>';


		$builder = $this->getMockBuilder(Scan\Output\OutputInterface::class);
		$output = $builder
			->setMethods(["emitError"])
			->getMockForAbstractClass();

		$output->expects($this->exactly(2))->method("emitError")->withConsecutive(
			[
				$this->anything(), $this->anything(),
				$this->equalTo(5),
				$this->stringContains( \Scan\Checks\SwitchCheck::TYPE_MISSING_BREAK ),
				$this->anything()
			],
			[
				$this->anything(), $this->anything(),
				$this->equalTo(8),
				$this->stringContains( \Scan\Checks\SwitchCheck::TYPE_MISSING_BREAK ),
				$this->anything()
			]
		);

		$emptyTable = new \Scan\SymbolTable\InMemorySymbolTable(__DIR__);

		$stmts = self::parseText($code);
		$check = new \Scan\Checks\SwitchCheck($emptyTable, $output);
		$check->run(__FILE__, $stmts[0], null, null);
	}

	function testGoodSwitch() {
		$code = '<?
			switch($size) {
				case \'small\': $size=1; $originalWidth=$originalHeight=$width=$height=150; break;
				case \'tiny\' : $size=1; $originalWidth=$originalHeight=150; $width=$height=20; break;
				case \'original\': $size=0; break;
				case \'large\': $size=2; break;
				case \'xs\': $size=3; break; // xs is our tiny of 50 by 50
				case \'medium\': $size=4; break;
				default:
					$response->responseCodeHeader(404,"Not found");
					$response->errorMessage("size not found must be small or tiny");
					return true;
			}
			';


		$builder = $this->getMockBuilder(Scan\Output\OutputInterface::class);
		$output = $builder
			->setMethods(["emitError"])
			->getMockForAbstractClass();

		$output->expects($this->never())->method("emitError");

		$emptyTable = new \Scan\SymbolTable\InMemorySymbolTable(__DIR__);

		$stmts = self::parseText($code);
		$check = new \Scan\Checks\SwitchCheck($emptyTable, $output);
		$check->run(__FILE__, $stmts[0], null, null);
	}

}