<?php

namespace Scan\Output;

use PhpParser\Node;

interface OutputInterface
{
	function emitError($className, $file, Node $node=null, $type, $message="");
	function output($verbose, $extraVerbose);
	function outputVerbose($string);
	function outputExtraVerbose($string);
	function incTests();
	function getErrorCount();
}