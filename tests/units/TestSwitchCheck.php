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

		$output = new \N98\JUnitXml\Document;
		$emptyTable = new \Scan\SymbolTable\InMemorySymbolTable(__DIR__);

		$stmts = self::parseText($code);
		$check = new \Scan\Checks\SwitchCheck($emptyTable, $output);
		$check->run(__FILE__, $stmts[0], null, null);

		$failures=$output->getElementsByTagName("failure");

		// Confirm there are only 2 errors and they occur on lines 3 & 6.
		$this->assertEquals( 2, $failures->length );
		$this->assertStringEndsWith(" on line 5", $failures->item(0)->textContent );
		$this->assertStringEndsWith(" on line 8", $failures->item(1)->textContent );
	}

}