<?php namespace Scan;

class Config {
	const MEMORY_SYMBOL_TABLE=1;
	const SQLITE_SYMBOL_TABLE=2;

	/** @var int Number of analyzer processes to run.  If 1 then we don't run a child process.  */
	private $processes=1;

	/** @var string Directory containing the config file.  All files are relative to this directory */
	private $basePath = "";

	/** @var array nested array with the settings for what files to import */
	private $config = [];

	/** @var string  */
	private $symbolTableFile = "symbol_table.sqlite3";

	/** @var int The number of partitions */
	private $partitions=1;

	/** @var int Which partition this server is running  */
	private $partitionNumber=1;

	/** @var string  */
	private $outputFile = "";

	/** @var int MEMORY_SYMBOL_TABLE | SQLITE_SYMBOL_TABLE */
	private $preferredTable = self::MEMORY_SYMBOL_TABLE;

	/** @var \Scan\SymbolTable\SymbolTable */
	private $symbolTable = null;

	/** @var string[]|false The list of files to process */
	private $fileList=false;


	/** @var bool  */
	private $forceIndex=false;

	/** @var bool  */
	private $forceAnalysis=false;

	/** @var string */
	private $configFileName = "";

	/**
	 * @param string $file File to import.
	 */
	function __construct($argv) {

		$this->parseArgv($argv);

		$this->basePath=dirname(realpath($this->configFileName));
		$this->config=json_decode(file_get_contents($this->configFileName),true);

		if($this->processes>1) {
			$this->preferredTable = self::SQLITE_SYMBOL_TABLE;
		}

		if($this->forceIndex) {
			unlink($this->getSymbolTableFile());
		}

		if($this->preferredTable==self::SQLITE_SYMBOL_TABLE) {
			if(!file_exists($this->getSymbolTableFile())) {
				$this->shouldIndex = true;
			}

			$this->symbolTable = new \Scan\SymbolTable\SqliteSymbolTable( $this->getSymbolTableFile() );
		} else {
			$this->shouldIndex=true;
			$this->symbolTable = new \Scan\SymbolTable\InMemorySymbolTable();
		}
	}

	/**
	 * @param array $argv
	 * @return array
	 * @throws InvalidConfigException
	 */
	private function parseArgv(array $argv) {
		$nextArg=0;
		$forceIndex=false;
		for($i=1;$i<count($argv);++$i) {
			switch ($argv[$i]) {
				case '-a':
					$this->forceAnalysis=true;
					break;
				case '-i':
					$this->forceIndex=true;
					break;
				case '-s':
					$this->preferredTable=self::SQLITE_SYMBOL_TABLE;
					break;
				case '-m':
					$this->preferredTable=self::MEMORY_SYMBOL_TABLE;
					break;
				case '-p':
					$params = [];
					if ($i+1 >= count($argv) || !preg_match('/^([0-9]+)\\/([0-9]+)$/', $argv[$i+1], $params) ) {
						throw new InvalidConfigException;
					}
					++$i;
					list($wholeMatch, $this->partitionNumber, $this->partitions) = $params;
					if($this->partitionNumber<1 || $this->partitionNumber>$this->partitions) {
						throw new InvalidConfigException;
					}
					echo "Partition: ".$this->partitionNumber." of ".$this->partitions."\n";
					break;
				case '-n':
					if ($i + 1 >= count($argv)) throw new InvalidConfigException;
					$this->processes = intval($argv[++$i]);
					break;
				case '-f':
					if ($i + 1 >= count($argv)) throw new InvalidConfigException;
					$this->shouldIndex=true;
					$this->preferredTable=self::SQLITE_SYMBOL_TABLE;
					$this->fileList=[ $argv[++$i] ];
					break;
				case '-o':
					if ($i + 1 >= count($argv)) throw new InvalidConfigException;
					$this->outputFile = $argv[++$i];
					break;
				default:
					switch($nextArg) {
						case 0:
							$this->configFileName = $argv[$i];
							break;
						case 1:
							$this->fileList = explode("\n", file_get_contents($argv[$i]));
							break;
						default:
							throw new InvalidConfigException;
					}
					$nextArg++;
			}
		}
		if($this->preferredTable==self::MEMORY_SYMBOL_TABLE) {
			$this->forceIndex = true;
		}
	}

	function getProcessCount() {
		return $this->processes;
	}

	function getConfigArray() {
		return $this->config;
	}

	function hasFileList() {
		return $this->fileList !== false;
	}

	function getFileList() {
		return $this->fileList;
	}

	function getConfigFileName() {
		return $this->configFileName;
	}

	function getPartitions() {
		return $this->partitions;
	}

	function getPartitionNumber() {
		return $this->partitionNumber;
	}

	function getBasePath() {
		return $this->basePath;
	}

	function getSymbolTable() {
		return $this->symbolTable;
	}

	function shouldIndex() {
		return $this->forceIndex;
	}

	function showAnalyze() {
		return $this->forceAnalysis;
	}

	private function getSymbolTableFile() {
		return $this->basePath."/".$this->symbolTableFile;
	}

	function processCount() {
		return $this->processes;
	}

	function getOutputFile() {
		return $this->outputFile;
	}
}