<?php

// Usage: php -d phar.readonly=false Build.php

$phar = new Phar('scan.phar');
chdir(__DIR__);
$phar->buildFromDirectory("../../",'/\.php$/');

//$phar->compressFiles( Phar::GZ );
$phar->stopBuffering();
$phar->setStub($phar->createDefaultStub('src/bin/Scan.php'));

