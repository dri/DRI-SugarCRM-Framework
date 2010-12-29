<?php

/**
 * 
 * Runs sugar quick repair script - partially, for the timebeing
 * @author ddpc
 *
 */

require_once 'bob/libs/Builder.php';
class RepairConfig extends Builder
{
    /**
     * 
     * These variables specify when this class is to be 
     * @var unknown_type
     */
    protected $_production = 1;
    protected $_testing = 1;
    protected $_development = 1;
    
    // determines order. lower is executed first
    protected $_priority = 21;
    
    public function execute()
    {
	echo "RepairConfig is yet to be implemented...";
    }
}
