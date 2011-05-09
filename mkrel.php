<?php
if (!defined('sugarEntry'))
    define('sugarEntry', true);
require_once ('include/entryPoint.php');

$args = $_SERVER['argv'];

try {
    $rel = new MakeRelationship($args);
    $rel->execute();
} catch (Exception $e) {
    echo $e->getMessage();
}

class MakeRelationship
{
    private $_relationship_type;
    private $_relationship_name;
    private $_lhs_module;
    private $_rhs_module;
    private $_lhs_table;
    private $_rhs_table;
    private $_lhs_key;
    private $_rhs_key;
    
    private $_metadata;
    private $_lhs_vardefs;
    private $_rhs_vardefs;
    private $_lhs_layoutdefs;
    private $_rhs_layoutdefs;
    private $_lhs_langs;
    private $_rhs_langs;
    
    private $args_template = array(
        //  0 is for script name
        1 => array(
            'name' => 'relationship_type', 
            'null' => false
        ), 
        2 => array(
            'name' => 'relationship_name', 
            'null' => false
        ), 
        3 => array(
            'name' => 'lhs_module', 
            'null' => false
        ), 
        4 => array(
            'name' => 'rhs_module', 
            'null' => false
        ), 
        5 => array(
            'name' => 'lhs_table', 
            'null' => false
        ), 
        6 => array(
            'name' => 'rhs_table', 
            'null' => false
        ), 
        7 => array(
            'name' => 'lhs_key', 
            'null' => false
        ), 
        8 => array(
            'name' => 'rhs_key', 
            'null' => false
        )
    );
    
    public $supported_types = array(
        'one-to-many', 
        'many-to-many'
    );
    
    function __construct($args)
    {
        try {
            $this->_checkArgs($args);
            $this->_checkExists();
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * validates arguments -- can't be null so far 
     * populate meta fields 
     * 
     * @param array $args
     */
    private function _checkArgs($args)
    {
        foreach ($this->args_template as $order => $meta) {
            if (empty($args[$order]) && !$meta['null']) {
                throw new Exception($meta['name'] . " can't be null.\n " . $this->_printusage());
            }
            
            // populate class properties 
            $this->{'_' . $meta['name']} = $args[$order];
        }
        
        if (!in_array($this->_relationship_type, $this->supported_types)) {
            throw new Exception("Unsupported relationship type: " . $this->_relationship_type);
        }
    }
    
    /**
     * print usage help message
     */
    private function _printusage()
    {
        return "usage: php -f mkrel.php relationship_type relationship_name lhs_module rhs_module lhs_table rhs_table lhs_key rhs_key \n";
    }
    
    /**
     * checks we're not creating a duplicate relationship
     */
    private function _checkExists()
    {
        $query = "SELECT count(*) FROM relationships WHERE relationship_name='$this->_relationship_name' AND deleted=0";
        if ($GLOBALS['db']->getOne($query)) {
            // existing relationship
            throw new Exception($this->_relationsip_name . " already exists in this sugar instance");
        }
    }
    
    private function _writeRelationshipVardef()
    {
        if ($this->_relationship_type == 'many-to-many') {
            $this->_metadata = array(
                'table' => $this->_relationship_name, 
                'fields' => array(
                    array(
                        'name' => 'id', 
                        'type' => 'varchar', 
                        'len' => '36'
                    ), 
                    array(
                        'name' => $this->_lhs_key, 
                        'type' => 'varchar', 
                        'len' => '36'
                    ), 
                    array(
                        'name' => $this->_rhs_key, 
                        'type' => 'varchar', 
                        'len' => '36'
                    ), 
                    array(
                        'name' => 'date_modified', 
                        'type' => 'datetime'
                    ), 
                    array(
                        'name' => 'deleted', 
                        'type' => 'bool', 
                        'len' => '1', 
                        'required' => false, 
                        'default' => '0'
                    )
                ), 
                'indices' => array(
                    array(
                        'name' => $this->_relationship_name . 'pk', 
                        'type' => 'primary', 
                        'fields' => array(
                            'id'
                        )
                    ), 
                    array(
                        'name' => 'idx_' . $this->_relationship_name, 
                        'type' => 'alternate_key', 
                        'fields' => array(
                            $this->_lhs_key, 
                            $this->_rhs_key
                        )
                    ), 
                    array(
                        'name' => 'idx_all', 
                        'type' => 'index', 
                        'fields' => array(
                            $this->_rhs_key, 
                            'deleted', 
                            $this->_lhs_key
                        )
                    )
                ), 
                
                'relationships' => array(
                    $this->_relationship_name => array(
                        'lhs_module' => $this->_lhs_module, 
                        'lhs_table' => $this->_lhs_table, 
                        'lhs_key' => 'id', 
                        'rhs_module' => $this->_rhs_module, 
                        'rhs_table' => $this->_rhs_table, 
                        'rhs_key' => 'id', 
                        'relationship_type' => 'many-to-many', 
                        'join_table' => $this->_relationship_name, 
                        'join_key_lhs' => $this->_lhs_key, 
                        'join_key_rhs' => $this->_rhs_key
                    )
                )
            );
        } else {
            $this->_lhs_vardefs = array(
                'relationships' => array(
                    $this->_relationship_name => array(
                        'lhs_module' => $this->_lhs_module, 
                        'lhs_table' => $this->_lhs_table, 
                        'lhs_key' => 'id', 
                        'rhs_module' => $this->_rhs_module, 
                        'rhs_table' => $this->_rhs_table, 
                        'rhs_key' => $this->_rhs_key, 
                        'relationship_type' => 'one-to-many'
                    )
                )
            );
        }
    }
    
    /**
     * Write vardef file for rhs
     */
    private function _writeRhsVardef()
    {
        $this->_rhs_vardefs['fields'][$this->_relationship_name] = array(
            'name' => $this->_relationship_name, 
            'type' => 'link', 
            'relationship' => $this->_relationship_name, 
            'source' => 'non-db'
        );
        
        if ($this->_relationship_type == 'one-to-many') {
            $this->_rhs_vardefs['fields'][$this->_rhs_key] = array(
                'name' => $this->_rhs_key, 
                'type' => 'id', 
                'link' => $this->_relationship_name, 
                'side' => 'right'
            );
            $this->_rhs_vardefs['fields'][$this->_relationship_name . '_name'] = array(
                'name' => $this->_relationship_name . '_name', 
                'type' => 'relate', 
                'source' => 'non-db', 
                'vname' => 'LBL_' . strtoupper($this->_relationship_name), 
                'save' => true, 
                'id_name' => $this->_rhs_key, 
                'link' => $this->_relationship_name, 
                'table' => $this->_lhs_table, 
                'module' => $this->_lhs_module, 
                'rname' => 'name'
            );
        }
    }
    
    /**
     * Write vardef file for lhs
     */
    private function _writeLhsVardef()
    {
        $this->_lhs_vardefs['fields'][$this->_relationship_name] = array(
            'name' => $this->_relationship_name, 
            'type' => 'link', 
            'relationship' => $this->_relationship_name, 
            'source' => 'non-db'
        );
    }
    
    /**
     * Write language files
     * 
     */
    private function _writeLangs()
    {
        $mod_strings = array();
        $this->_lhs_langs = array(
            'LBL_' . $this->_relationship_name => $this->_rhs_module
        );
        $this->_rhs_langs = array(
            'LBL_' . $this->_relationship_name => $this->_lhs_module
        );
    }
    
    /**
     * Write subpanels
     * 
     */
    private function _writeSubpanels()
    {
        if ($this->_relationship_type == 'many-to-many') {
            $this->_rhs_layoutdefs = array(
                'order' => 100, 
                'module' => $this->_lhs_module, 
                'subpanel_name' => 'default', 
                'sort_order' => 'asc', 
                'sort_by' => 'id', 
                'title_key' => $this->_lhs_module, 
                'get_subpanel_data' => $this->_relationship_name, 
                'top_buttons' => array(
                    0 => array(
                        'widget_class' => 'SubPanelTopCreateButton'
                    ), 
                    1 => array(
                        'widget_class' => 'SubPanelTopSelectButton', 
                        'mode' => 'MultiSelect'
                    )
                )
            );
        }
        
        $this->_lhs_layoutdefs = array(
            'order' => 100, 
            'module' => $this->_rhs_module, 
            'subpanel_name' => 'default', 
            'sort_order' => 'asc', 
            'sort_by' => 'id', 
            'title_key' => $this->_rhs_module, 
            'get_subpanel_data' => $this->_relationship_name, 
            'top_buttons' => array(
                0 => array(
                    'widget_class' => 'SubPanelTopCreateButton'
                ), 
                1 => array(
                    'widget_class' => 'SubPanelTopSelectButton', 
                    'mode' => 'MultiSelect'
                )
            )
        );
    }
    
    /**
     * Copy the built arrays w relationship meta, subpanels, langs to 
     * files in custom/Extension/...
     */
    private function _toFiles()
    {
        $lhs_bean = loadBean($this->_lhs_module);
        $rhs_bean = loadBean($this->_rhs_module);
        
        require_once ('include/utils/file_utils.php');
        
        if (!empty($this->_metadata)) {
            system("mkdir -p custom/metadata");
            write_array_to_file("dictionary['" . $this->_relationship_name . "']", $this->_metadata, 'custom/metadata/' . $this->_relationship_name . '.php');
            echo "wrote custom/metadata/$this->_relationship_name.php \n";
            
            system("mkdir -p custom/Extension/application/Ext/TableDictionary");
            file_put_contents("custom/Extension/application/Ext/TableDictionary/$this->_relationship_name.php", "include 'custom/metadata/$this->_relationship_name.php' ");
            echo "wrote custom/Extension/application/Ext/TableDictionary/$this->_relationship_name.php' \n";
            
            system("mkdir -p custom/application/Ext/TableDictionary");
            file_put_contents('custom/application/Ext/TableDictionary/tabledictionary.ext.php', "include 'custom/metadata/$this->_relationship_name.php' ", FILE_APPEND);
            echo "wrote custom/application/Ext/TableDictionary/tabledictionary.ext.php \n";
        }
        
        /****
         * VARDEFS
         */
        if (!empty($this->_lhs_vardefs)) {
            system("mkdir -p custom/Extension/modules/" . $this->_lhs_module . "/Ext/Vardefs");
            foreach ($this->_lhs_vardefs['fields'] as $key => $vardef) {
                write_array_to_file("dictionary['" . $GLOBALS['beanList'][$this->_lhs_module] . "']['fields']['$key']", $vardef, "custom/Extension/modules/" . $this->_lhs_module . "/Ext/Vardefs/" . $this->_relationship_name . '.php', "a");
            }
            
            if (is_array($this->_lhs_vardefs['relationships'])) {
                foreach ($this->_lhs_vardefs['relationships'] as $key => $vardef) {
                    write_array_to_file("dictionary['" . $GLOBALS['beanList'][$this->_lhs_module] . "']['relationships']['$key']", $vardef, "custom/Extension/modules/" . $this->_lhs_module . "/Ext/Vardefs/" . $this->_relationship_name . '.php', "a");
                }
            }
            
            echo "wrote custom/Extension/modules/" . $this->_lhs_module . "/Ext/Vardefs/" . $this->_relationship_name . ".php \n";
        }
        
        if (!empty($this->_rhs_vardefs)) {
            system("mkdir -p custom/Extension/modules/" . $this->_rhs_module . "/Ext/Vardefs");
            foreach ($this->_rhs_vardefs['fields'] as $key => $vardef) {
                write_array_to_file("dictionary['" . $GLOBALS['beanList'][$this->_rhs_module] . "']['fields']['$key']", $vardef, "custom/Extension/modules/" . $this->_rhs_module . "/Ext/Vardefs/" . $this->_relationship_name . '.php', "a");
            }
            
            if (is_array($this->_rhs_vardefs['relationships'])) {
                foreach ($this->_rhs_vardefs['relationships'] as $key => $vardef) {
                    write_array_to_file("dictionary['" . $GLOBALS['beanList'][$this->_rhs_module] . "']['relationships']['$key']", $vardef, "custom/Extension/modules/" . $this->_rhs_module . "/Ext/Vardefs/" . $this->_relationship_name . '.php', "a");
                }
            }
            
            echo "wrote custom/Extension/modules/" . $this->_rhs_module . "/Ext/Vardefs/" . $this->_relationship_name . ".php \n";
        }
        
        /****
         * ~~ VARDEFS
         */
        
        /**
         * LAYOUTDEFS
         */
        
        if (!empty($this->_lhs_layoutdefs)) {
            system("mkdir -p custom/Extension/modules/" . $this->_lhs_module . "/Ext/Layoutdefs");
            write_array_to_file("layout_defs['$this->_lhs_module']['subpanel_setup']['$this->_relationship_name']", $this->_lhs_layoutdefs, "custom/Extension/modules/" . $this->_lhs_module . "/Ext/Layoutdefs/" . $this->_relationship_name . '.php');
            
            echo "wrote custom/Extension/modules/" . $this->_lhs_module . "/Ext/Layoutdefs/" . $this->_relationship_name . ".php \n";
        }
        
        if (!empty($this->_rhs_layoutdefs)) {
            system("mkdir -p custom/Extension/modules/" . $this->_rhs_module . "/Ext/Layoutdefs");
            write_array_to_file("layout_defs['$this->_rhs_module']['subpanel_setup']['$this->_relationship_name']", $this->_rhs_layoutdefs, "custom/Extension/modules/" . $this->_rhs_module . "/Ext/Layoutdefs/" . $this->_relationship_name . '.php');
            
            echo "wrote custom/Extension/modules/" . $this->_rhs_module . "/Ext/Layoutdefs/" . $this->_relationship_name . ".php \n";
        }
        
        /**
         * ~~LAYOUTDEFS
         */
        
        /**
         * LANGS
         */
        if (!empty($this->_lhs_langs)) {
            system("mkdir -p custom/Extension/modules/" . $this->_lhs_module . "/Ext/Language");
            foreach ($this->_lhs_langs as $key => $def) {
                write_array_to_file("mod_strings['" . strtoupper($key) . "']", $def, "custom/Extension/modules/" . $this->_lhs_module . "/Ext/Language/en_us." . $this->_relationship_name . '.php', "a");
            }
            
            echo "wrote custom/Extension/modules/" . $this->_lhs_module . "/Ext/Language/en_us." . $this->_relationship_name . ".php \n";
        }
        
        if (!empty($this->_rhs_langs)) {
            system("mkdir -p custom/Extension/modules/" . $this->_rhs_module . "/Ext/Language");
            foreach ($this->_rhs_langs as $key => $def) {
                write_array_to_file("mod_strings['" . strtoupper($key) . "']", $def, "custom/Extension/modules/" . $this->_rhs_module . "/Ext/Language/en_us." . $this->_relationship_name . '.php', "a");
            }
            
            echo "wrote custom/Extension/modules/" . $this->_rhs_module . "/Ext/Language/en_us." . $this->_relationship_name . ".php \n";
        }
        /**
         * ~~LANGS
         */
    
    }
    
    public function execute()
    {
        $this->_writeRelationshipVardef();
        $this->_writeRhsVardef();
        $this->_writeLhsVardef();
        $this->_writeLangs();
        $this->_writeSubpanels();
        
        $this->_toFiles();
        // var_export($this->_metadata);
    }
}
