<?php namespace Scan;
require_once '../../vendor/autoload.php';

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeTraverser;

function phase1($baseDir, \RecursiveIteratorIterator $it2, SymbolTableInterface $symbolTable) {
	$indexer = new SymbolTableIndexer($symbolTable);
	$traverser = new NodeTraverser;
	$traverser->addVisitor(new NameResolver());
	$traverser->addVisitor($indexer);

	$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);


	$count = 0;
	foreach ($it2 as $file) {
		if ($file->getExtension() == "php" && $file->isFile()) {
			++$count;
			$name=str_replace($baseDir,"",$file->getPathname());
			try {
				//echo " - $count:" .$name. "\n";
				$fileData = file_get_contents($file->getPathname());
				$indexer->setFilename($file->getPathname());
				$stmts = $parser->parse($fileData);
				if ($stmts) {
					$traverser->traverse($stmts);
				}
			} catch (Error $e) {
				echo $name.' : Parse Error: ' . $e->getMessage() . "\n";
			}
		}
	}
	return $count;
}

function phase2($config, $basePath,\RecursiveIteratorIterator $it2, SymbolTableInterface $symbolTable) {

	$traverser = new NodeTraverser;
	$traverser->addVisitor(new NameResolver());
	$analyzer=new StaticAnalyzer($basePath,$symbolTable);
	$traverser->addVisitor($analyzer);

	$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
	$processingCount = 0;
	foreach ($it2 as $file) {
		if ($file->getExtension() == "php" && $file->isFile()) {
			$processingCount++;
			$name=str_replace($basePath,"",$file->getPathname());
			try {
				//echo " - $processingCount/$count:" . $file->getPathname() . "\n";
				if(isset($config['test-ignore']) && in_array($file->getFilename(), $config['test-ignore'])) {
					continue;
				}
				$fileData = file_get_contents($file->getPathname());
				$stmts = $parser->parse($fileData);
				if ($stmts) {
					$analyzer->setFile($name);
					$traverser->traverse($stmts);
				}
			} catch (Error $e) {
				echo $name.' Parse Error: ' . $e->getMessage() . "\n";
			}
		}
	}
}

$str = file_get_contents($_SERVER['argv'][1]);
$config = json_decode($str,true);

echo "Phase 1\n";
$symbolTable = new InMemorySymbolTable();
$basePaths = $config['index'];
array_unshift($basePaths, dirname(dirname(__DIR__))."/vendor/phpstubs/phpstubs" );
foreach($basePaths as $basePath) {
	$it = new \RecursiveDirectoryIterator($basePath);
	$it2 = new \RecursiveIteratorIterator($it);
	phase1($basePath, $it2, $symbolTable);
}

echo "Phase 2\n";
foreach($config['test'] as $basePath) {
	$it = new \RecursiveDirectoryIterator($basePath);
	$it2 = new \RecursiveIteratorIterator($it);
	phase2($config, $basePath, $it2, $symbolTable);
}
echo "Done\n\n";

