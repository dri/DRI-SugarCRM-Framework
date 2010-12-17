<?php

/**
 * 
 * Checks for same labels with different values across sugar language files in same directory
 * @author ddpc
 *
 */

require_once 'bob/libs/Builder.php';
class CheckExtensionLanguageSanity extends Builder
{
    
    /**
     * 
     * These variables specify when this class is to be 
     * @var unknown_type
     */
    protected $_production = 0;
    protected $_testing = 1;
    protected $_development = 1;
    
    // determines order. lower is executed first
    protected $_priority = 1;
    
    protected $directories = array();
    protected $strings = array();
    protected $notifications = array();
    
    public function execute()
    {
        echo "executing CheckExtensionLanguageSanity...";
        
        // 1. find which directories we need to check
        $this->getDirectories();
        
        // 2. for each directory
        foreach ($this->directories as $directory) {
            // 3. read one file, populate $strings[$filename][$key] = $value
            $this->readFiles($directory);
        }
        
        $this->displayNotifications();
        
        echo " done!".EOL;
    }
    
    /**
     * 
     * populate $this->directories with paths we will check for inconsistent labels
     * if we want this script to scan more places, this would be the place to say so
     */
    public function getDirectories()
    {
        $extendedModules = scandir('custom/Extension/modules/');
        foreach ($extendedModules as $extendedModule) {
            if (in_array($extendedModule, $this->exclusion_paths)) {
                continue;
            }
            
            if (file_exists('custom/Extension/modules/' . $extendedModule . '/Ext/Language')) {
                $this->directories[] = 'custom/Extension/modules/' . $extendedModule . '/Ext/Language';
            }
        }
        
        if (file_exists('custom/Extension/application/Ext/Language/')) {
            $this->directories[] = 'custom/Extension/application/Ext/Language/';
        }
    }
    
    /**
     * 
     * pupulate $this->strings with contents of a file in $directory
     * before inserting, checks for duplicate keys with different values
     * duplicate notification go to $this->notifications so we can display them in the correct order
     * 
     * @param unknown_type $directory
     */
    public function readFiles($directory)
    {
        require_once 'bob/libs/helpers.php';
        
        $files = scandir($directory);
        foreach ($files as $file) {
            if (in_array($file, $this->exclusion_paths)) {
                continue;
            }
            
            // get the en_us part of en_us.customxpto.php
            $file_lang = mb_strcut($file, 0, mb_strpos($file, '.'));
            
            if (!isset($this->strings[$file_lang])) {
                $this->strings[$file_lang] = array();
            }
            if (!isset($this->strings[$file_lang][$directory])) {
                $this->strings[$file_lang][$directory] = array();
            }
            
            $mod_strings = array();
            require_once $directory . '/' . $file;
            
            foreach ($mod_strings as $key => $value) {
                
                // if this key exists with a different value, add it to the notification array
                if (isset($this->strings[$file_lang][$directory][$key])) {
                    if ($this->strings[$file_lang][$directory][$key] != $value) {
                        // insert "original" value
                        if(empty($this->notifications[$file_lang][$directory][$key])) {
                            $this->notifications[$file_lang][$directory][$key][] = array(
                                // no way to know where we got that from
                                'unknown file' => $this->strings[$file_lang][$directory][$key],
                            );
                        }
                        
                        // insert "new" value
                        $this->notifications[$file_lang][$directory][$key][] = array(
                            $file => $value,
                        );
                    }
                } else {
                    // no conflicts: just add the string         
                    $this->strings[$file_lang][$directory][$key] = $value;
                }
            }
        }
    }
    
    public function displayNotifications() {
        echo EOL;

        foreach($this->notifications as $lang => $directories) {
            echo " ".$lang . " conflicts: " .EOL;
            
            foreach($directories as $directory => $hits) {
                echo "\t-- " . $directory .EOL;
                
                foreach($hits as $key => $hit) {
                    echo "\t\t key ". $key .EOL;
                    
                    // extra level to pick up conflicts within the same file
                    foreach($hit as $conflict) {
                        echo "\t\t\t* ".key($conflict). ' says "'.array_pop($conflict) . '"' . EOL;
                    }
                }
            }
        }
        
        echo EOL;
    }
}