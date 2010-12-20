<?php
/**
 * 
 * Make sure all strings defined in a language exist in other languages as well
 * @author ddpc
 *
 */

require_once 'bob/libs/Builder.php';
class CompareModuleLanguages extends Builder
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
    // we want a high number on this, as we'll be using the cache
    protected $_priority = 10000;
    
    public $strings = array();
    
    public function execute()
    {
        require_once 'bob_config.php';
        global $bob_config;
        
        if (empty($bob_config) || empty($bob_config['CompareModuleLanguages'])) {
            throw new Exception('Missing bob config @ CompareModuleLanguages', 2);
        }
        
        $fallback_language = $bob_config['CompareModuleLanguages']['fallback_language'];
        
        // load all lang strings to memory, per module per language
        foreach ($bob_config['CompareModuleLanguages']['modules'] as $module) {
            foreach ($bob_config['CompareModuleLanguages']['languages'] as $language) {
                $this->strings[$module][$language] = return_module_language($language, $module, true);
            }
        }
        
        foreach ($this->strings as $module => $language) {
            foreach ($language as $lang => $labels) {
                if (empty($labels)) {
                    continue;
                }
                foreach ($labels as $label => $value) {
                    foreach ($bob_config['CompareModuleLanguages']['languages'] as $config_language) {
                        if (!isset($this->strings[$module][$config_language][$label])) {
                            echo " $module $config_language '$label' => '" . $this->strings[$module][$fallback_language][$label] . "';" . EOL;
                        }
                    }
                }
            }
        }
    }
}