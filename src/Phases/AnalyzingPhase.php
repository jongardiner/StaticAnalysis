<?php
namespace Scan\Phases;

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeTraverser;
use Scan\Config;
use Scan\Util;
use Scan\NodeVisitors\StaticAnalyzer;


class AnalyzingPhase
{
	function getPhase2Files(Config $config, \RecursiveIteratorIterator $it2, &$toProcess) {
		$configArr=$config->getConfigArray();
		foreach ($it2 as $file) {
			if ($file->getExtension() == "php" && $file->isFile()) {
				if (isset($configArr['test-ignore']) && is_array($configArr['test-ignore']) && Util::matchesGlobs($config->getBasePath(), $file->getPathname(), $configArr['test-ignore'])) {
					continue;
				}
				$toProcess[] = $file->getPathname();
			}
		}
	}

	function phase2(Config $config, $toProcess) {

		$output = new \N98\JUnitXml\Document;
		$traverser1 = new NodeTraverser;
		$traverser1->addVisitor(new NameResolver());
		$analyzer = new StaticAnalyzer($config->getBasePath(), $config->getSymbolTable(), $output);
		$traverser2 = new NodeTraverser;
		$traverser2->addVisitor($analyzer);
		$parseError = $output->addTestSuite();
		$parseError->setName(__CLASS__);

		$parser = (new ParserFactory)->create(ParserFactory::ONLY_PHP5);
		$processingCount = 0;
		foreach ($toProcess as $file) {
			try {
				$name = Util::removeInitialPath($config->getBasePath(), $file);
				$processingCount++;
				//echo " - $processingCount:" . $file . "\n";
				$fileData = file_get_contents($file);
				$stmts = $parser->parse($fileData);
				if ($stmts) {
					$analyzer->setFile($name);
					$traverser1->traverse($stmts);
					$traverser2->traverse($stmts);
				}
			} catch (Error $e) {
				$case=$parseError->addTestCase();
				$case->setName($name);
				$case->setClassname(__CLASS__);
				$case->addFailure($e->getMessage(),"Parse error");
			}
			echo ".";flush();
		}
		$analyzer->saveResults( $config );
		return ($analyzer->getErrorCount()>0 ? 1 : 0);
	}

	function getMultipartFileName(Config $config, $part) {
		$outputFileName=$config->getOutputFile();
		$lastPart = strrpos($outputFileName,".");
		if($lastPart>0) {
			$outputFileName=substr($outputFileName,0, $lastPart+1).$part.".xml";
		} else {
			$outputFileName=$outputFileName.$part;
		}
		return $outputFileName;
	}

	function runChildProcesses(Config $config, array $toProcess) {
		$error=false;
		$files = [];
		$groupSize = intval(count($toProcess) / $config->getProcessCount());
		for ($i = 0; $i < $config->getProcessCount(); ++$i) {
			$group = ($i == $config->getProcessCount()) ?
				array_slice($toProcess, $groupSize * $config->getProcessCount()) :
				array_slice($toProcess, $groupSize * $i, $groupSize);
			file_put_contents("scan.tmp.$i", implode("\n", $group));
			$cmd=escapeshellarg($GLOBALS['argv'][0]);
			$cmdLine = "php -d memory_limit=1G $cmd -a -s ";
			if($config->getOutputFile()) {
				$outputFileName=$this->getMultipartFileName($config, $i);
				$cmdLine.=" -o ".escapeshellarg($outputFileName)." ";
			}
			$cmdLine.= escapeshellarg($config->getConfigFileName()) . " ".escapeshellarg("scan.tmp.$i");
			echo $cmdLine."\n";
			$file = popen($cmdLine, "r");
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
						if(!$error) {
							$error = pclose($file) == 0;
						}
					}
				}
			}
		}
		for($i=0;$i<$config->getProcessCount();++$i) {
			unlink("scan.tmp.$i");
		}
		return $error ? 1 : 0;
	}

	function run(Config $config) {
		$basePath=$config->getBasePath();
		$toProcess=[];
		$configArray = $config->getConfigArray();
		foreach($configArray['test'] as $directory) {
			$directory=$basePath."/".$directory;
			echo "Directory: $directory\n";
			$it = new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS);
			$it2 = new \RecursiveIteratorIterator($it);
			$this->getPhase2Files($config, $it2, $toProcess);
		}

		// First we split up the files by partition.
		// If we're running multiple child processes, then we'll split the list again.
		$groupSize = intval(count($toProcess) / $config->getPartitions());
		$toProcess = ($config->getPartitionNumber() == $config->getPartitions())
			? array_slice($toProcess, $groupSize * ($config->getPartitionNumber()-1))
			: array_slice($toProcess, $groupSize * ($config->getPartitionNumber()-1), $groupSize);

		echo "Analyzing ".count($toProcess)." files\n";

		if($config->getProcessCount()>1) {
			return $this->runChildProcesses($config, $toProcess);
		} else {
			return $this->phase2($config, $toProcess);
		}
	}
}