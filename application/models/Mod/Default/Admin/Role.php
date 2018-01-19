<?php
/**
 * 557 Model for database tables
 * 
 * @author WiconWang@gmail.com
 * @copyright 2018-01-19  11:22:14
 * @file Role.php
 */
class Mod_Default_Admin_RoleModel extends Abstract_M{
	protected $_tbl_name = 'dd_admin_role';
	protected $_tbl_alis_name = 'admin_role';
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
