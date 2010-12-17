<?php

/**
 * 
 * what every builder class must have
 * @author ddpc
 *
 */
abstract class Builder
{
    
    public $exclusion_paths = array(
        '.',
        '..',
        '.svn',
        '.git'
    );
    
    /**
     * 
     * this function should be:
     * - non-destructive: if we run it in a production environment, it wont take anything out, even if it 
     * 		has already been executed
     * - smart: it should realize it has been ran before, or certain conditions relevant for it's execution are not present
     * 		not just die with a fatal error
     * 
     * Example: if the builder class creates a table in the db, it should check if this table already exists, not throw an SQL error
     * 
     * The idea is that whenever you put something in an environment you can safely run the build script for that env, confident that
     * everything will set itself up and nothing will be destroyed
     */
    abstract public function execute();
    
    /**
     * check if execute is to be called in this environment
     */
    public function checkEnv($environment)
    {
        if (!isset($this->{'_' . $environment})) {
            throw new Exception('No enviromnent variables defined for ' . get_class($this) . " in $environment".EOL, 1);
        }
        
        return $this->{'_' . $environment};
    }

    /**
     * 
     * determines run priority: lower values are ran first
     */
    public function getPriority() {
        if(!isset($this->_priority) || !is_numeric($this->_priority)) {
            throw new Exception('Unset or invalid priority for ' . get_class($this).EOL, 1);
        }
        
        return $this->_priority;
    }
}