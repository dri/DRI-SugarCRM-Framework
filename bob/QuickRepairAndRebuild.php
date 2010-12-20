<?php

/**
 * 
 * Runs sugar quick repair script - partially, for the timebeing
 * @author ddpc
 *
 */

require_once 'bob/libs/Builder.php';
class QuickRepairAndRebuild extends Builder
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
    protected $_priority = 20;
    
    public function execute()
    {
        require_once ('include/entryPoint.php');
        require_once ('modules/Administration/QuickRepairAndRebuild.php');
        
        $GLOBALS['mod_strings'] = return_module_language('', 'Administration', true);
        
        $rac = new RepairAndClear();
        
        $rac->module_list = array(
            'All Modules'
        );
        
        $rac->execute = true;
        $rac->rebuildExtensions();
        $rac->clearTpls();
        $rac->clearJsLangFiles();
    }
}