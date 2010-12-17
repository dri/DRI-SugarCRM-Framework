<?php

/**
 * 
 * to be used by usort
 * @param array $a1 - should have a priority key with a numeric value 
 * @param array $a2 - should have a priority key with a numeric value
 */
function comparePriorities($a1, $a2) {
    if($a1['priority'] > $a2['priority']) {
        return 1;
    } else {
        return 0;
    }
}

/**
 * 
 * thank you giulio dot provasi at gmail dot com
 * http://pt.php.net/manual/en/function.array-search.php#97645
 * 
 * @param unknown_type $haystack
 * @param unknown_type $needle
 * @param unknown_type $index
 */
function recursiveArraySearch($haystack, $needle, $index = null) 
{ 
    $aIt = new RecursiveArrayIterator($haystack); 
    $it = new RecursiveIteratorIterator($aIt); 
    
    while($it->valid()) 
    {        
        if (((isset($index) AND ($it->key() == $index)) OR (!isset($index))) AND ($it->current() == $needle)) { 
            return $aIt->key(); 
        } 
        
        $it->next(); 
    } 
    
    return false; 
} 