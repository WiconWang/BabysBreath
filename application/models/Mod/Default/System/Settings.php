<?php
/**
 * 557 Model for database tables
 * 
 * @author WiconWang@gmail.com
 * @copyright 2018-01-19  11:22:14
 * @file Settings.php
 */
class Mod_Default_System_SettingsModel extends Abstract_M{
	protected $_tbl_name = 'dd_system_settings';
	protected $_tbl_alis_name = 'system_settings';
	protected static $_instance = null;
        public static function instance()
        {
            if (!self::$_instance) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
}
?>
