<?php

namespace Scan\Output;

use N98\JUnitXml;
use PhpParser\Node;


class XUnitOutput implements OutputInterface {

	/** @var \Scan\Config  */
	private $config;

	/** @var JUnitXml\TestSuiteElement[] */
	protected $suites;

	/** @var JUnitXml\Document  */
	protected $doc;

	private $files;

	private $emitErrors;

	function __construct(\Scan\Config $config) {
		$this->doc=new JUnitXml\Document();
		$this->doc->formatOutput=true;
		$this->config=$config;
		$this->emitErrors = $config->getOutputLevel()>1;
	}

	function getClass($className) {
		if(!isset($this->suites[$className])) {
			$suite = $this->doc->addTestSuite();
			$suite->setName($className);
			$this->suites[$className]=$suite;
		}
		return $this->suites[$className];

	}

	function incTests() {
		//$this->suite->addTestCase();
	}

	function emitError($className, $fileName, Node $node=null, $name, $message="") {
		$suite = $this->getClass($className);
		if(!isset($this->files[$className][$fileName])) {
			$case=$suite->addTestCase();
			$case->setName($fileName);
			$case->setClassname( $className );
			if(!isset($this->files[$className])) {
				$this->files[$className]=[];
			}
			$this->files[$className][$fileName]=$case;
		} else {
			$case=$this->files[$className][$fileName];
		}


		if($node) {
			$lineNumber = $node->getLine();
			$message.=" on line ".$lineNumber;
		}
		$case->addFailure($message , $name);
		if($this->emitErrors) {
			echo "E";
		}
		//echo "ERROR: $fileName $lineNumber: $message\n";
	}

	function output($verbose, $extraVerbose) {
		if($this->config->getOutputLevel()==1) {
			echo $verbose;flush();
		} else if($this->config->getOutputLevel()==2) {
			echo $extraVerbose."\n";flush();
		}
	}

	function outputVerbose($string) {
		if($this->config->getOutputLevel()>=1) {
			echo $string;flush();
		}
	}

	function outputExtraVerbose($string) {
		if($this->config->getOutputLevel()>=2) {
			echo $string;flush();
		}
	}

	function getErrorCount() {
		$failures = $this->doc->getElementsByTagName("failure");
		return $failures->length;
	}

	function renderResults() {
		echo "OUTPUT!";
		if($this->config->getOutputFile()) {
			echo "To file\n";
			$this->doc->save($this->config->getOutputFile());
		} else {
			echo "Save xml\n";
			echo $this->doc->saveXml();
		}
	}
}