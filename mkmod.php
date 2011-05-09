<?php
if (!defined('sugarEntry'))
    define('sugarEntry', true);
error_reporting(E_ALL);
ini_set('display_errors', true);

require_once ('include/entryPoint.php');


$args = $_SERVER['argv'];

try {
    $mod = new MakeModule($args);
    $mod->execute();
} catch (Exception $e) {
    echo $e->getMessage();
}

class MakeModule
{
    private $_name;
    private $_template_type;
    private $_team_security;
    private $_nav_tab;
    private $_importable;
    
    private $args_template = array(
        //  0 is for script name
        1 => array(
            'name' => 'name',
            'null' => false
        ),
        2 => array(
            'name' => 'template_type',
            'null' => false
        ),
        3 => array(
            'name' => 'team_security',
            'null' => false
        ),
        4 => array(
            'name' => 'nav_tab',
            'null' => false
        ),
        5 => array(
            'name' => 'importable',
            'null' => false
        )
    );
    
    private $_manual_labels = array(
        'LBL_LIST_FORM_TITLE' => 'Listar <>',
        'LBL_MODULE_NAME' => '<>',
        'LBL_MODULE_TITLE' => '<>',
        'LBL_HOMEPAGE_TITLE' => 'Meus <>',
        'LNK_NEW_RECORD' => 'Criar <>',
        'LNK_LIST' => 'Vista <>',
        'LNK_IMPORT_X_TESTE2' => 'Import <>',
        'LBL_SEARCH_FORM_TITLE' => 'Pesquisar <>',
        'LBL_HISTORY_SUBPANEL_TITLE' => 'Ver Histórico',
        'LBL_ACTIVITIES_SUBPANEL_TITLE' => 'Actividades',
        'LBL_X_TESTE2_SUBPANEL_TITLE' => '<>',
        'LBL_NEW_FORM_TITLE' => 'Novo <>'
    );
    
    public $supported_types = array(
        'basic',
        'company',
        'person',
        'file',
        'issue',
        'sale'
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
        
        if (!in_array($this->_template_type, $this->supported_types)) {
            throw new Exception("Unsupported relationship type: " . $this->_template_type);
        }
    }

    /**
     * print usage help message
     */
    private function _printusage()
    {
        return "usage: php -f mkmod.php module_name template_type team_security navigation_tab importable \n";
    }

    /**
     * checks we're not creating a duplicate relationship
     */
    private function _checkExists()
    {
        global $beanList;
        if (isset($beanList[$this->_name])) {
            // existing relationship
            throw new Exception($this->_name . " module already exists in this sugar instance \n");
        }
    }

    /**
     * Write vardefs
     * 
     */
    private function _writeVardefs()
    {
        $module = $this->_name;
        require_once ("include/SugarObjects/templates/{$this->_template_type}/vardefs.php");
        $this->_vardefs = $vardefs;
    }

    private function _writeClass()
    {
        $this->_templateBeanName = ucwords($this->_template_type);
        $this->_tableName = strtolower($this->_name);
        $this->_classDef = <<<EOQ
<?php

require_once('include/SugarObjects/templates/{$this->_template_type}/{$this->_templateBeanName}.php');
class {$this->_name} extends {$this->_templateBeanName} {
    var \$new_schema = true;
    var \$module_dir = {$this->_name};
    var \$object_name = {$this->_name};
    var \$table_name = {$this->_tableName};
    var \$importable = {$this->_importable};\n
EOQ;
        
        foreach ($this->_vardefs['fields'] as $key => $vardef) {
            $this->_classDef .= "    var \${$vardef['name']};\n";
        }
        
        if ($this->_team_security) {
            $this->_classDef .= "    var \$disable_row_level_security = true;\n";
        }
        
        $this->_classDef .= <<<EOQ

    function dc_teste_sugar(){	
		parent::Company();
	}
	
	function bean_implements(\$interface){
		switch(\$interface){
			case 'ACL': return true;
		}
		return false;
    }	

}
EOQ;
    
    }

    private function _writeMenu()
    {
        $this->_menu = <<< EOQ
<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point'); 

global \$mod_strings, \$app_strings, \$sugar_config;
 
if(ACLController::checkAccess('{$this->_name}', 'edit', true))\$module_menu[]=Array("index.php?module={$this->_name}&action=EditView&return_module={$this->_name}&return_action=DetailView", \$mod_strings['LNK_NEW_RECORD'],"Create{$this->_name}", '{$this->_name}');
if(ACLController::checkAccess('{$this->_name}', 'list', true))\$module_menu[]=Array("index.php?module={$this->_name}&action=index&return_module={$this->_name}&return_action=DetailView", \$mod_strings['LNK_LIST'],"{$this->_name}", '{$this->_name}');
EOQ;
        
        if ($this->_importable) {
            $this->_menu .= <<< EOQ
if(ACLController::checkAccess('{$this->_name}', 'import', true))\$module_menu[]=Array("index.php?module=Import&action=Step1&import_module={$this->_name}&return_module={$this->_name}&return_action=index", \$app_strings['LBL_IMPORT'],"Import", '{$this->_name}');
EOQ;
        }
    }

    /**
     * 
     * Enter description here ...
     */
    private function _writeInclude()
    {
        if ($this->_nav_tab) {
            $this->_includeModules = <<< EOQ
<?php

\$beanList['{$this->_name}'] = '{$this->_name}';
\$beanFiles['{$this->_name}'] = 'modules/{$this->_name}/{$this->_name}.php';
\$moduleList[] = '{$this->_name}';

EOQ;
        } else {
            $this->_includeModules = <<< EOQ
<?php

\$beanList['{$this->_name}'] = '{$this->_name}';
\$beanFiles['{$this->_name}'] = 'modules/{$this->_name}/{$this->_name}.php';
\$modInvisList[] = '{$this->_name}';

EOQ;
        }
        
        $this->_includeLanguage = array(
            'en_us' => "\$app_list_strings['moduleList']['{$this->_name}'] = '{$this->_name}';",
            'pt_PT' => "\$app_list_strings['moduleList']['{$this->_name}'] = '{$this->_name}';"
        );
    
    }

    private function _copyMetadataFiles() {
        system("mkdir -p modules/{$this->_name}/metadata");
        $in_dir = "include/SugarObjects/templates/" . $this->_template_type . "/metadata/*.php";
        $files = glob($in_dir);

        foreach($files as $file) {
            $fname = basename($file);
            system("sed 's/<object_name>/{$this->_name}/g;s/<module_name>/{$this->_name}/g;s/<_module_name>/{$this->_name}/g' $file > modules/{$this->_name}/metadata/$fname");
        }
        
        system("mkdir -p modules/{$this->_name}/metadata/subpanels");
        $subpanel = "include/SugarObjects/templates/" . $this->_template_type . "/metadata/subpanels/default.php";
        system("sed 's/<object_name>/{$this->_name}/g;s/<module_name>/{$this->_name}/g;s/<_module_name>/{$this->_name}/g' $subpanel > modules/{$this->_name}/metadata/subpanels/default.php");
    }
    
    private function _copyDashletFiles() {
        system("mkdir -p modules/{$this->_name}/Dashlets/{$this->_name}Dashlet");
        $f1 = "include/SugarObjects/templates/basic/Dashlets/Dashlet/m-n-Dashlet.meta.php";
        $f2 = "include/SugarObjects/templates/basic/Dashlets/Dashlet/m-n-Dashlet.php";
        system("sed 's/<object_name>/{$this->_name}/g;s/<module_name>/{$this->_name}/g;s/<_module_name>/{$this->_name}/g' $f1 > modules/{$this->_name}/Dashlets/{$this->_name}Dashlet/{$this->_name}Dashlet.meta.php");
        system("sed 's/<object_name>/{$this->_name}/g;s/<module_name>/{$this->_name}/g;s/<_module_name>/{$this->_name}/g' $f2 > modules/{$this->_name}/Dashlets/{$this->_name}Dashlet/{$this->_name}Dashlet.php");
    }
    
    /**
     * Copy the built arrays w relationship meta, subpanels, langs to 
     * files in custom/Extension/...
     */
    private function _toFiles()
    {
        system("mkdir -p modules/" . $this->_name);
        write_array_to_file("dictionary['" . $this->_name . "']", $this->_vardefs, 'modules/' . $this->_name . '/vardefs.php');
        echo "wrote modules/$this->_name/vardefs.php \n";
        
        file_put_contents("modules/" . $this->_name . "/" . $this->_name . ".php", $this->_classDef);
        echo "wrote modules/" . $this->_name . "/" . $this->_name . ".php \n";
        
        $this->_copyMetadataFiles();
        echo "wrote modules/" . $this->_name . "/metadata directory \n";
        
        $this->_copyDashletFiles();
        echo "wrote modules/" . $this->_name . "/Dashlets directory \n";
        
        system("cp -a include/SugarObjects/templates/" . $this->_template_type . "/language modules/" . $this->_name);
        echo "wrote modules/" . $this->_name . "/language directory \n";
        
        system("mkdir -p custom/Extension/application/Ext/Include/");
        file_put_contents("custom/Extension/application/Ext/Include/" . $this->_name . ".php", $this->_includeModules);
        echo "wrote modules/" . $this->_name . "/" . $this->_name . ".php \n";
        
        system("mkdir -p custom/Extension/application/Ext/Language/");
        foreach ($this->_includeLanguage as $lang => $contents) {
            file_put_contents("custom/Extension/application/Ext/Language/" . $lang . "." . $this->_name . ".php", "<?php \n " . $contents);
            echo "wrote custom/Extension/application/Ext/Language/" . $lang . "." . $this->_name . ".php \n";
        }
        
        file_put_contents("modules/{$this->_name}/Menu.php", $this->_menu);
        echo "wrote modules/{$this->_name}/Menu.php \n";
        
        echo "you need to create custom/themes/default/images/Create" . strtolower($this->_name) . ".gif, commander \n";
        echo "you need to create custom/themes/default/images/" . strtolower($this->_name) . ".gif, commander \n";
        echo "you need to create custom/themes/default/images/icon_" . $this->_name . "_32.png, commander \n";
        echo "you need to create custom/themes/default/images/icon_" . strtolower($this->_name) . "_bar_32.png, commander \n";
        file_put_contents("modules/{$this->_name}/manual_labels.txt", var_export($this->_manual_labels, true));
        echo "you need to insert the labels in modules/{$this->_name}/manual_labels.txt manually, commander. \n";
        
    }

    public function execute()
    {
        $this->_writeVardefs();
        $this->_writeClass();
        $this->_writeInclude();
        $this->_writeMenu();
        
        $this->_toFiles();
    
     // var_export($this->_metadata);
    }
}
