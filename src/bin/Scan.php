<?php namespace Scan;

require_once __DIR__ . '/../../vendor/autoload.php';

$runner=new CommandLineRunner();
$runner->run($_SERVER["argv"]);