<?php namespace Scan;

require_once __DIR__ . '/../../vendor/autoload.php';

use Scan\Phases\IndexingPhase;
use Scan\Phases\AnalyzingPhase;
use Scan\SymbolTable\SqliteSymbolTable;

set_time_limit(0);
date_default_timezone_set("UTC");
if(count($_SERVER["argv"])<2) {
	echo "Usage: php -d memory_limit=500M Scan.php [config file]\n\n";
	exit();
}
$str = file_get_contents($_SERVER['argv'][1]);
$config = json_decode($str,true);
$config['basePath']=dirname(realpath($_SERVER['argv'][1]));
$config['sqliteFile']=$config['basePath']."/symbol_table.sqlite3";
$config['singleProcess']=true;

if($config['singleProcess']) {
//	$shouldIndex = true;
//	$symbolTable = new SymbolTable\InMemorySymbolTable();
//} else {
	$shouldIndex = !file_exists($config['sqliteFile']);
	$symbolTable = new SqliteSymbolTable($config['sqliteFile']);
}


if(!isset($_SERVER["argv"][2])) {
	echo "Indexing\n";
	if($shouldIndex) {
		$indexer=new IndexingPhase();
		$indexer->run($config, $symbolTable);
	}
	echo "Analyzing\n";
	$analyzer=new AnalyzingPhase();
	exit($analyzer->run($config, $symbolTable));
	echo "Done\n\n";
} else {
	$list=explode("\n",file_get_contents($_SERVER["argv"][2]));
	unlink($_SERVER["argv"][2]);
	$analyzer=new AnalyzingPhase();
	exit($analyzer->phase2($config['basePath'], $list, $symbolTable));
}




