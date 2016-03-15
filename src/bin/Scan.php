<?php namespace Scan;
set_time_limit(0);
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeTraverser;
use Webmozart\Glob\Glob;
use Scan\SymbolTable\SymbolTable;
use Scan\SymbolTable\SqliteSymbolTable;

function removeInitialPath($path, $name) {
	if(strpos($name,$path)===0) {
		return substr($name,strlen($path));
	} else {
		return $name;
	}
}

function phase1($config, $baseDir, \RecursiveIteratorIterator $it2, SymbolTable $symbolTable, $stubs=false) {
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
				if(!$stubs && isset($config['ignore']) && is_array($config['ignore']) && matchesGlobs($baseDir, $file->getPathname(), $config['ignore'])) {
					continue;
				}
				++$count;
				echo " - $count:" .$name. "\n";
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

function matchesGlobs($basePath, $path, $globArr) {
	foreach($globArr as $glob) {
		if(Glob::match($path, $basePath."/".$glob)) {
			return true;
		}
	}
	return false;
}

function getPhase2Files($config, $basePath,\RecursiveIteratorIterator $it2, SymbolTable $symbolTable, &$toProcess) {
	foreach ($it2 as $file) {
		if ($file->getExtension() == "php" && $file->isFile()) {
			if (isset($config['test-ignore']) && is_array($config['test-ignore']) && matchesGlobs($basePath, $file->getPathname(), $config['test-ignore'])) {
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
	foreach($toProcess as $file) {
		try {
			$name = removeInitialPath($basePath, $file);
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
	echo $analyzer->getResults();
}

if(count($_SERVER["argv"])<2) {
	echo "Usage: php -d memory_limit=500M Scan.php [config file]\n\n";
	exit();
}
$str = file_get_contents($_SERVER['argv'][1]);
$config = json_decode($str,true);
$basePath = dirname(realpath($_SERVER['argv'][1]));

$shouldIndex = !file_exists($basePath."/symbol_table.sqlite3");

//$symbolTable = new SymbolTable\InMemorySymbolTable();
$symbolTable = new SqliteSymbolTable($basePath);

if(!isset($_SERVER["argv"][2])) {
	echo "Indexing\n";
	$basePaths = $config['index'];

	if($shouldIndex) {
		foreach ($basePaths as $directory) {
			echo $basePath."/".$directory."\n";
			$it = new \RecursiveDirectoryIterator($basePath."/".$directory);
			$it2 = new \RecursiveIteratorIterator($it);
			phase1($config, $basePath, $it2, $symbolTable);
		}
		$it = new \RecursiveDirectoryIterator(dirname(dirname(__DIR__)) . "/vendor/phpstubs/phpstubs/res");
		$it2 = new \RecursiveIteratorIterator($it);
		phase1($config, $basePath, $it2, $symbolTable, true);
	}
	$toProcess=[];
	foreach($config['test'] as $directory) {
		$directory=$basePath."/".$directory;
		echo "Directory: $directory\n";
		$it = new \RecursiveDirectoryIterator($directory);
		$it2 = new \RecursiveIteratorIterator($it);
		getPhase2Files($config, $basePath, $it2, $symbolTable, $toProcess);
	}

	echo "Analyzing\n";
	$files = [];
	$groupSize=intval(count($toProcess)/4);
	for($i=0;$i<4;++$i) {
		$group= ($i==3) ? array_slice($toProcess, $groupSize*3) : array_slice($toProcess, $groupSize*$i, $groupSize);
		file_put_contents("scan.tmp.$i", implode("\n", $group));
		$files[]=popen("php -d memory_limit=500M Scan.php ".$_SERVER["argv"][1]." scan.tmp.$i","r");
	}
	while(count($files)>0) {
		$readFile=$files;
		$empty1=$empty2=null;
		$count=stream_select( $readFile,$empty1, $empty2, 5 );
		if($count>0) {
			foreach ($readFile as $index => $file) {
				echo fread($file, 1000 );
				if (feof($file)) {
					pclose($file);
					unset($files[$index]);
				}
			}
		}
	}


	echo "Done\n\n";
} else {
	$list=explode("\n",file_get_contents($_SERVER["argv"][2]));
	unlink($_SERVER["argv"][2]);
	phase2($basePath, $list, $symbolTable);
}




