<?php

chdir(__DIR__);

include('../vendor/autoload.php');

use Language\Config;
use Language\ApiCall;

$config  = new Config();
$apiCall = new ApiCall();

$languageBatchBo = new \Language\LanguageBatchBo($config, $apiCall);
$languageBatchBo->generateLanguageFiles();
$languageBatchBo->generateAppletLanguageXmlFiles();