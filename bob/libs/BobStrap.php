<?php
class BobStrap {

    /**
     * 
     * sets up a fake admin session so we can rebuild all we need
     */
    static function setupUser()
    {
        global $sugar_config;
        require_once 'modules/Users/User.php';
        $_SESSION['unique_key'] = $sugar_config['unique_key'];
        $user_id = $GLOBALS['db']->getOne("SELECT id FROM users WHERE user_name='{$GLOBALS['bob_config']['global']['admin_user_name']}' AND is_admin=1");
        if(empty($user_id)) {
            throw new Exception("Can't find user ".$GLOBALS['bob_config']['global']['admin_user_name'], 1);
        }
        $_SESSION['authenticated_user_id'] = $user_id;
        $GLOBALS['current_user'] = new User();
        $GLOBALS['current_user'] = $GLOBALS['current_user']->retrieve($user_id); 
    }
}