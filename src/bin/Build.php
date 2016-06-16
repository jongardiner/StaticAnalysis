<?php

// Usage: php -d phar.readonly=false Build.php

if(file_exists("scan.phar")) {
	unlink("scan.phar");
}
$phar = new Phar('scan.phar');

$baseDir=dirname(dirname(__DIR__));
echo "Building relative to $baseDir\n";
$it = new \RecursiveDirectoryIterator($baseDir,  \FilesystemIterator::SKIP_DOTS);
$it2 = new \RecursiveIteratorIterator($it);

$phar->buildFromIterator($it2, $baseDir);

//$phar->compressFiles( Phar::GZ );
$phar->stopBuffering();
$phar->setStub($phar->createDefaultStub('src/bin/Scan.php'));

