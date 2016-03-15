<?php namespace Scan;
set_time_limit(0);
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeTraverser;
use Webmozart\Glob\Glob;
use Scan\SymbolTable\SymbolTableInterface;

function removeInitialPath($path, $name) {
	if(strpos($name,$path)===0) {
		return substr($name,strlen($path));
	} else {
		return $name;
	}
}

function phase1($config, $baseDir, \RecursiveIteratorIterator $it2, SymbolTableInterface $symbolTable) {
	$indexer = new SymbolTableIndexer($symbolTable);
	$traverser = new NodeTraverser;
	$traverser->addVisitor(new NameResolver());
	$traverser->addVisitor($indexer);

	$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);


	$count = 0;
	foreach ($it2 as $file) {
		if ($file->getExtension() == "php" && $file->isFile()) {

			$name=removeInitialPath($baseDir,$file->getPathname());
			try {

				if(isset($config['ignore']) && is_array($config['ignore']) && matchesGlobs($file->getPathname(), $config['ignore'])) {
					continue;
				}
				++$count;
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

function matchesGlobs($path, $globArr) {
	foreach($globArr as $glob) {
		if(Glob::match($path, $glob)) {
			return true;
		}
	}
	return false;
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

			$name=removeInitialPath($basePath,$file->getPathname());
			try {

				if(isset($config['test-ignore']) && is_array($config['test-ignore']) && matchesGlobs($file->getPathname(), $config['test-ignore'])) {
					continue;
				}
				$processingCount++;
				//echo " - $processingCount:" . $file->getPathname() . "\n";
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
print_r($config);

echo "Phase 1\n";
$symbolTable = new SymbolTable\InMemorySymbolTable();
$basePaths = $config['index'];
array_unshift($basePaths, dirname(dirname(__DIR__))."/vendor/phpstubs/phpstubs" );
foreach($basePaths as $basePath) {
	$it = new \RecursiveDirectoryIterator($basePath);
	$it2 = new \RecursiveIteratorIterator($it);
	phase1($config,$basePath, $it2, $symbolTable);
}

echo "Phase 2\n";
foreach($config['test'] as $basePath) {
	$it = new \RecursiveDirectoryIterator($basePath);
	$it2 = new \RecursiveIteratorIterator($it);
	phase2($config, $basePath, $it2, $symbolTable);
}
echo "Done\n\n";

