<?php
ini_set('display_errors', 'On');
require_once 'bob/libs/helpers.php';
require_once 'bob/libs/BobStrap.php';
require_once 'bob_config.php';

// determine $env and if we're running from browser or command line 
$env = '';
$runFrom = '';
$args = $_SERVER['argv'];
if (empty($args)) {
    define('EOL', '<br />');
    $env = $_REQUEST['env'];
    $runFrom = 'browser';
} else if (count($args) > 1) {
    define('EOL', "\n");
    $env = $args[1];
    $runFrom = 'command-line';
}

/**
 * No environment found: print basic help and die
 */
if (empty($env) || $env == '') {
    require_once 'bob/libs/Documentation.php';
    Documentation::printHelp();
    die();
}

// Sugar init
if (!defined('sugarEntry'))
    define('sugarEntry', true);
require_once ('include/entryPoint.php');

// setup a fake admin session
BobStrap::setupUser();

$files = scandir('bob');

$toBeExecuted = array();

// see which classes are to be executed in this environment
foreach ($files as $file) {
    if ($file == '.' || $file == '..' || $file == 'libs') {
        continue;
    }
    
    require_once 'bob/' . $file;
    try {
        $className = str_replace('.php', '', $file);
        
        $builderClass = new $className();
        
        // make sure this is to be ran in this environment
        if (!$builderClass->checkEnv($env)) {
            continue;
        }
        
        $toBeExecuted[$className] = array(
            'priority' => $builderClass->getPriority(), 
            'object' => $builderClass
        );
    } catch (Exception $e) {
        echo "Failed: " . $e->getMessage() . EOL;
    }
}

if (empty($toBeExecuted)) {
    return true;
}

// sort the array so we run the classes in the right order
usort($toBeExecuted, 'comparePriorities');

foreach ($toBeExecuted as $builderA) {
    try {
        echo "executing ".get_class($builderA['object'])."...";
        $builderA['object']->execute();
        echo " done!".EOL;
    } catch (Exception $e) {
        echo "Failed: " . $e->getMessage() . EOL;
    }
}

echo "Bob has completed, sir. Please chown or chmod as appropriate. \n";

return true;