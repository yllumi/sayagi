<?php

use Yllumi\Sayagi\libraries\Migration;

$pluginPath = getenv('PLUGIN_PATH') ?: '';
$migration = new Migration($pluginPath);
return $migration->getConfig();