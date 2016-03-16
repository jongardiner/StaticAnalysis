<?php
namespace Scan\Phases;

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeTraverser;
use Scan\SymbolTable\SymbolTable;
use Scan\Util;
use Scan\NodeVisitors\StaticAnalyzer;


class AnalyzingPhase
{
	function getPhase2Files($config, $basePath, \RecursiveIteratorIterator $it2, &$toProcess) {
		foreach ($it2 as $file) {
			if ($file->getExtension() == "php" && $file->isFile()) {
				if (isset($config['test-ignore']) && is_array($config['test-ignore']) && Util::matchesGlobs($basePath, $file->getPathname(), $config['test-ignore'])) {
					continue;
				}
				$toProcess[] = $file->getPathname();
			}
		}
	}

	function phase2($basePath, $toProcess, $symbolTable) {
		$traverser = new NodeTraverser;
		$traverser->addVisitor(new NameResolver());
		$analyzer = new StaticAnalyzer($basePath, $symbolTable);
		$traverser->addVisitor($analyzer);

		$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
		$processingCount = 0;
		foreach ($toProcess as $file) {
			try {
				$name = Util::removeInitialPath($basePath, $file);
				$processingCount++;
				//echo " - $processingCount:" . $file->getPathname() . "\n";
				$fileData = file_get_contents($file);
				$stmts = $parser->parse($fileData);
				if ($stmts) {
					$analyzer->setFile($name);
					$traverser->traverse($stmts);
				}
			} catch (Error $e) {
				echo $name . ' Parse Error: ' . $e->getMessage() . "\n";
			}
		}
		//echo $analyzer->getResults();
	}

	function runChildProcesses(array $toProcess) {
		$files = [];
		$groupSize = intval(count($toProcess) / 4);
		for ($i = 0; $i < 4; ++$i) {
			$group = ($i == 3) ? array_slice($toProcess, $groupSize * 3) : array_slice($toProcess, $groupSize * $i, $groupSize);
			file_put_contents("scan.tmp.$i", implode("\n", $group));
			$file = popen("php -d memory_limit=500M Scan.php " . $_SERVER["argv"][1] . " scan.tmp.$i", "r");
			$files[] = $file;
		}
		while (count($files) > 0) {
			$readFile = $files;
			$empty1 = $empty2 = null;
			$count = stream_select($readFile, $empty1, $empty2, 5);
			if ($count > 0) {
				foreach ($readFile as $index => $file) {
					echo fread($file, 1000);
					if (feof($file)) {
						unset($files[ array_search($file, $files) ]);
						echo "Exit status: ".pclose($file)."\n";
					}
				}
			}
		}
	}

	function run($config, SymbolTable $symbolTable) {
		$basePath=$config['basePath'];
		$toProcess=[];
		foreach($config['test'] as $directory) {
			$directory=$basePath."/".$directory;
			echo "Directory: $directory\n";
			$it = new \RecursiveDirectoryIterator($directory);
			$it2 = new \RecursiveIteratorIterator($it);
			$this->getPhase2Files($config, $basePath, $it2, $toProcess);
		}

		if($config['singleProcess']) {
			$this->phase2($config['basePath'], $toProcess, $symbolTable);
		} else {
			$this->runChildProcesses($toProcess);
		}
	}
}