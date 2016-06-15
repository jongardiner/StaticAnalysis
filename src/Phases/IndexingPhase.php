<?php

namespace Scan\Phases;

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeTraverser;
use Scan\SymbolTable\SymbolTable;
use Scan\NodeVisitors\SymbolTableIndexer;
use Scan\Util;
use Scan\Config;


class IndexingPhase
{

	function index(Config $config, \RecursiveIteratorIterator $it2, $stubs = false) {
		$baseDir = $config->getBasePath();
		$symbolTable = $config->getSymbolTable();
		$indexer = new SymbolTableIndexer($symbolTable);
		$traverser1 = new NodeTraverser;
		$traverser1->addVisitor(new NameResolver());
		$traverser2 = new NodeTraverser;
		$traverser2->addVisitor($indexer);
		$parser = (new ParserFactory)->create(ParserFactory::ONLY_PHP5);

		$configArr = $config->getConfigArray();

		$count = 0;
		foreach ($it2 as $file) {
			if (($file->getExtension() == "php" || $file->getExtension() =="inc") && $file->isFile()) {
				$name = Util::removeInitialPath($baseDir, $file->getPathname());
				try {
					if (!$stubs && isset($configArr['ignore']) && is_array($configArr['ignore']) && Util::matchesGlobs($baseDir, $file->getPathname(), $configArr['ignore'])) {
						continue;
					}
					++$count;
					$config->output(".", " - $count:" . $name);
					$fileData = file_get_contents($file->getPathname());
					$indexer->setFilename($file->getPathname());
					$stmts = $parser->parse($fileData);
					if ($stmts) {
						$traverser1->traverse($stmts);
						$traverser2->traverse($stmts);
					}
				} catch (Error $e) {
					$config->output('E', $name . ' : Parse Error: ' . $e->getMessage() . "\n");
				}
			}
		}
		return $count;
	}

	function run(Config $config) {
		$configArr = $config->getConfigArray();
		$indexPaths = $configArr['index'];

		foreach ($indexPaths as $directory) {
			$tmpDirectory = strpos($directory, "/") == 0 ? $directory : $config->getBasePath() . "/" . $directory;
			$config->outputVerbose("Indexing Directory: " . $tmpDirectory . "\n");
			$it = new \RecursiveDirectoryIterator($tmpDirectory, \FilesystemIterator::SKIP_DOTS);
			$it2 = new \RecursiveIteratorIterator($it);
			$this->index($config, $it2);
		}
		$it = new \RecursiveDirectoryIterator(dirname(__DIR__) . "/ExtraStubs");
		$it2 = new \RecursiveIteratorIterator($it);
		$this->index($config, $it2, true);

		$it = new \RecursiveDirectoryIterator(dirname(dirname(__DIR__)) . "/vendor/phpstubs/phpstubs/res");
		$it2 = new \RecursiveIteratorIterator($it);
		$this->index($config, $it2, true);

	}
}
