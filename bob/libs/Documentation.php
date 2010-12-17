<?php
class Documentation {
    static function printHelp() {
        echo<<<EOQ
From the command line, type in 'php -f build.php <env>'
From a browser, go to http://<sugar-instance-url>/build.php?env=<env>

Where env should be one of:
. development
. testing
. production

Each class in the bob directory should have a variable saying wether it is to be exec'd in the specified environment.

EOQ;

    }
}