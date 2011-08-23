<?php

/**
 * 
 * Counts comments in **PHP** files inside the custom/ tree
 * 
 * excludes a bunch of known dirs, see @exclusion_dirs 
 * 
 * @author ddpc
 *
 */

require_once 'bob/libs/Builder.php';

class CountComments extends Builder
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
    
    protected $_files = array();
    protected $_totalLines = 0;
    protected $_totalCommentLines = 0;
    
    // paths starting from sugar root and inside custom
    protected $exclusion_dirs = array(
        'custom/Extension', // vardefs, layoudefs, et al, no comments req'd
        'custom/application', // auto generated stuff
        'custom/metadata', // metadata, no comments required
        'custom/history', // studio shit
        'custom/modulebuilder', //mb shit
        'custom/working'
    );
    
    protected $global_exclusions = array(
        '.',
        '..',
        '.svn',
        '.git',
        'Ext',
        'metadata',
        'language',
        'logic_hooks.php'
    );

    /**
     * (non-PHPdoc)
     * @see Builder::execute()
     */
    public function execute()
    {
        // 1. locate all files
        $this->_getFiles('custom');
        
        // 2. foreach file, scan and sum
        foreach ($this->_files as $file) {
            //echo $file . "\n";
            //echo $this->_totalCommentLines . " ";
            $this->_parseFile($file);
            //echo $this->_totalCommentLines . "\n";
        }
    
        // 3. display results and humiliate when needed
        $rate = round($this->_totalCommentLines / $this->_totalLines, 2);
        echo "\nComment Rate is " . $this->_totalCommentLines . "/" . $this->_totalLines . "=" . $rate . "\n";
    }

    private function _parseFile($file)
    {
        $text = file_get_contents($file);
        $trash = array();
        $this->_totalLines += preg_match_all("!\n!s", $text, $trash);
        
        // count comments
        $matches = array();
        // multi lines comments
        preg_match_all('!/\*.*?\*/!s', $text, $matches);
        $file_comments = $matches[0];
        // single liners
        $oneliners = preg_match_all('!\/\/.*!', $text, $matches2);
        $this->_totalCommentLines += $oneliners;
        
        foreach ($file_comments as $comment) {
            $buffer = preg_match_all("!\n!s", $comment, $trash);
            $this->_totalCommentLines += ($buffer + 1); // +1 is for the 1st line
        }
    }

    private function _getFiles($rootdir)
    {
        $inodes = scandir($rootdir);
        
        foreach ($inodes as $inode) {
            // skip excluded dirs
            if (in_array($rootdir, $this->exclusion_dirs)) {
                continue;
            }
            
            // skip common exclusions
            if (in_array($inode, $this->global_exclusions)) {
                continue;
            }
            
            // recursively call self for subdirs
            if (is_dir($rootdir . '/' . $inode)) {
                $this->_getFiles($rootdir . '/' . $inode);
            }
            
            // store php files for later processing
            $info = pathinfo($inode);
            if (@$info['extension'] == 'php') {
                array_push($this->_files, $rootdir . "/" . $inode);
            }
        }
    
    }
}