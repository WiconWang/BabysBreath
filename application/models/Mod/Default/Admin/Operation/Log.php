<?php
/**
 * 557 Model for database tables
 * 
 * @author WiconWang@gmail.com
 * @copyright 2018-01-18  18:33:55
 * @file Log.php
 */
class Mod_Default_Admin_Operation_LogModel extends Abstract_M{
	protected $_tbl_name = 'dd_admin_operation_log';
	protected $_tbl_alis_name = 'admin_operation_log';
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
