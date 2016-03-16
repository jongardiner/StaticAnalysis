<?php

namespace Scan\Phases;

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeTraverser;
use Scan\SymbolTable\SymbolTable;
use Scan\NodeVisitors\SymbolTableIndexer;
use Scan\Util;


class IndexingPhase
{

	function index($config, $baseDir, \RecursiveIteratorIterator $it2, SymbolTable $symbolTable, $stubs = false) {
		$indexer = new SymbolTableIndexer($symbolTable);
		$traverser = new NodeTraverser;
		$traverser->addVisitor(new NameResolver());
		$traverser->addVisitor($indexer);
		$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

		$count = 0;
		foreach ($it2 as $file) {
			if ($file->getExtension() == "php" && $file->isFile()) {
				$name = Util::removeInitialPath($baseDir, $file->getPathname());
				try {
					if (!$stubs && isset($config['ignore']) && is_array($config['ignore']) && Util::matchesGlobs($baseDir, $file->getPathname(), $config['ignore'])) {
						continue;
					}
					++$count;
					echo " - $count:" . $name . "\n";
					$fileData = file_get_contents($file->getPathname());
					$indexer->setFilename($file->getPathname());
					$stmts = $parser->parse($fileData);
					if ($stmts) {
						$traverser->traverse($stmts);
					}
				} catch (Error $e) {
					echo $name . ' : Parse Error: ' . $e->getMessage() . "\n";
				}
			}
		}
		return $count;
	}

	function run($config, $symbolTable) {
		$basePath = $config['basePath'];
		$basePaths = $config['index'];

		foreach ($basePaths as $directory) {
			echo $basePath . "/" . $directory . "\n";
			$it = new \RecursiveDirectoryIterator($basePath . "/" . $directory);
			$it2 = new \RecursiveIteratorIterator($it);
			$this->index($config, $basePath, $it2, $symbolTable);
		}
		$it = new \RecursiveDirectoryIterator(dirname(dirname(__DIR__)) . "/vendor/phpstubs/phpstubs/res");
		$it2 = new \RecursiveIteratorIterator($it);
		$this->index($config, $basePath, $it2, $symbolTable, true);
	}
}
