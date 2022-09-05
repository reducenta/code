<?php
require_once 'vendor/autoload.php';
require_once __DIR__ . '/Command/GrabCommand.php';

use Symfony\Component\Console\Application;

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../");

$application = new Application();
$application->add(new GrabCommand());


$application->run();
