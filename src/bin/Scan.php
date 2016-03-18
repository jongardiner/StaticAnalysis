<?php namespace Scan;

require_once __DIR__ . '/../../vendor/autoload.php';

use Scan\Phases\IndexingPhase;
use Scan\Phases\AnalyzingPhase;

set_time_limit(0);
date_default_timezone_set("UTC");

try {
	$config=new Config($_SERVER["argv"]);
	if(!$config->hasFileList()) {
		echo "Indexing\n";
		if($config->shouldIndex()) {
			$indexer=new IndexingPhase();
			$indexer->run($config);
		}

		echo "Analyzing\n";
		$analyzer=new AnalyzingPhase();
		$exitCode=$analyzer->run($config);
		echo "\nDone\n\n";
		exit($exitCode);
	} else {
		$list=$config->getFileList();
		$analyzer=new AnalyzingPhase();
		exit( $analyzer->phase2($config, $list) );
	}
}
catch(InvalidConfigException $exception) {
	echo "
Usage: php -d memory_limit=500M Scan.php [-a] [-i] [-n processes] [-o output_file_name] config_file

where: -n processes        = number of child process to run
       -a                  = analyze only, don't re-index.  (Will still index if no index exists.)
       -i                  = force re-index
       -s                  = prefer sqlite index
       -m                  = prefer in memory index (only available when -n=1)
       -o output_file_name = Output results in junit format to the specified filename

";
	exit();
}
