<?php
/**
 * 
 * Make sure all strings defined in a language exist in other languages as well
 * @author ddpc
 *
 */

require_once 'bob/libs/Builder.php';
class CompareLanguages extends Builder {
    
    /**
     * 
     * These variables specify when this class is to be 
     * @var unknown_type
     */
    protected $_production = 0;
    protected $_testing = 1;
    protected $_development = 1;
    
    // determines order. lower is executed first
    // we want a high number on this, as we'll be using the cache
    protected $_priority = 10000;
    
    public function execute() {
        
    }
}