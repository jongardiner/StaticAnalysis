<?php

namespace Scan;

use Scan\Phases\IndexingPhase;
use Scan\Phases\AnalyzingPhase;

class CommandLineRunner
{
	function run(array $argv) {

		set_time_limit(0);
		date_default_timezone_set("UTC");

		try {
			$config=new Config($argv);
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
Usage: php -d memory_limit=500M Scan.php [-a] [-i] [-n #] [-o output_file_name] [-p #/#] config_file

where: -p #/#                 = Define the number of partitions and the current partition.
                                Use for multiple hosts. Example: -p 1/4

       -n #                   = number of child process to run.
                                Use for multiple processes on a single host.

       -a                     = run the \"analyze\" operation

       -i                     = run the \"index\" operation.
                                Defaults to yes if using in memory index.

       -s                     = prefer sqlite index

       -m                     = prefer in memory index (only available when -n=1 and -p=1/1)

       -o output_file_name    = Output results in junit format to the specified filename

";
			exit();
		}
	}
}