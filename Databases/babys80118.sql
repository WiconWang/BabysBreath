/*
 Navicat Premium Data Transfer

 Source Server         : Localhost
 Source Server Type    : MySQL
 Source Server Version : 50720
 Source Host           : localhost:3306
 Source Schema         : babys

 Target Server Type    : MySQL
 Target Server Version : 50720
 File Encoding         : 65001

 Date: 18/01/2018 18:38:36
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for dd_admin_group
-- ----------------------------
DROP TABLE IF EXISTS `dd_admin_group`;
CREATE TABLE `dd_admin_group` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '组名',
  `roles` varchar(255) NOT NULL DEFAULT '' COMMENT '用户组的权限表',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '用户状态 正常0禁止1',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `create_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登录ip',
  `update_time` datetime DEFAULT NULL COMMENT '登录时间',
  `update_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登录ip',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='用户组';

-- ----------------------------
-- Records of dd_admin_group
-- ----------------------------
BEGIN;
INSERT INTO `dd_admin_group` VALUES (1, '核心管理员', '1,2,3,4,5,6,7,8,9,10', 0, '2018-01-18 15:53:39', '', '2017-08-12 15:25:12', '119.164.186.177');
INSERT INTO `dd_admin_group` VALUES (2, '信息管理员', '1,3,4,5,6,8', 0, '2017-06-15 16:22:51', '127.0.0.1', '2017-07-03 15:06:08', '127.0.0.1');
INSERT INTO `dd_admin_group` VALUES (3, '客服', '1,11', 0, '2017-06-15 16:23:06', '127.0.0.1', '2018-01-04 15:55:15', '119.163.152.65');
INSERT INTO `dd_admin_group` VALUES (4, '推送通知', '10', 1, '2017-08-08 14:39:38', '124.128.136.218', '2017-09-26 17:17:02', '119.164.181.209');
COMMIT;

-- ----------------------------
-- Table structure for dd_admin_operation_log
-- ----------------------------
DROP TABLE IF EXISTS `dd_admin_operation_log`;
CREATE TABLE `dd_admin_operation_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '用户姓名',
  `row_id` int(11) NOT NULL DEFAULT '0' COMMENT '修改的记录id',
  `log_type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '日志类别，1为管理员登录，2为后台操作',
  `path` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '路径',
  `ip` varchar(15) COLLATE utf8_unicode_ci DEFAULT '' COMMENT 'ip',
  `operation` text COLLATE utf8_unicode_ci COMMENT '操作细节',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=184 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Table structure for dd_admin_role
-- ----------------------------
DROP TABLE IF EXISTS `dd_admin_role`;
CREATE TABLE `dd_admin_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '权限组名',
  `urls` text NOT NULL COMMENT '用户有权限的url列表',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '用户状态 正常0禁止1',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `create_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登录ip',
  `update_time` datetime DEFAULT NULL COMMENT '登录时间',
  `update_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登录ip',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8 COMMENT='权限组';

-- ----------------------------
-- Records of dd_admin_role
-- ----------------------------
BEGIN;
INSERT INTO `dd_admin_role` VALUES (1, '核心模块', 'admin/admin,login/login,login/outin', 0, '2018-01-18 15:55:21', '', '2017-07-03 15:00:28', '127.0.0.1');
INSERT INTO `dd_admin_role` VALUES (2, '管理员和权限模块', 'manager/role,manager/rolelist,manager/grouplist,manager/group,manager/userlist,manager/user', 0, '2017-06-15 11:46:43', '127.0.0.1', '2017-06-15 19:12:56', '127.0.0.1');
INSERT INTO `dd_admin_role` VALUES (3, '游戏模块', 'game/add,game/del,game/update,game/check,game/select,game/listorder,game/ckup', 0, '2017-06-15 16:19:15', '127.0.0.1', '2017-07-03 14:58:27', '127.0.0.1');
INSERT INTO `dd_admin_role` VALUES (4, '商品模块', 'goods/add,goods/del,goods/update,goods/check,goods/select,goods/listorder', 0, '2017-06-15 16:19:30', '127.0.0.1', '2017-07-03 14:59:30', '127.0.0.1');
INSERT INTO `dd_admin_role` VALUES (5, '用户模块', 'user/usertoagent,user/getgamegoodsbygid,user/sendgameprops,user/sendgamegolds,user/deleteuser,user/status,user/userdetail,user/index,user/allusertree,user/searchuser', 0, '2017-07-03 14:51:14', '127.0.0.1', '2018-01-08 09:49:08', '119.163.153.225');
INSERT INTO `dd_admin_role` VALUES (6, '财务管理模块', 'fund/charge,fund/buy,fund/goldslist,tix/log,tix/del,tix/update,tix/check,tix/select,tix/listorder,tix/okup,tix/noup,pay/select,pay/add,pay/del,pay/update,pay/check,pay/login,pay/okup,pay/noup', 0, '2017-07-03 14:51:27', '127.0.0.1', '2017-09-26 17:19:13', '119.164.181.209');
INSERT INTO `dd_admin_role` VALUES (7, '设置模块', 'setpeiz/add,setpeiz/del,setpeiz/select,setpeiz/update,setpeiz/listorder,settings/level,settings/welfare,server/list,server/listsave', 0, '2017-07-03 14:51:38', '127.0.0.1', '2017-07-03 15:02:14', '127.0.0.1');
INSERT INTO `dd_admin_role` VALUES (8, '广告模块', 'advert/add,advert/del,advert/select,advert/update,advert/listorder', 0, '2017-07-03 14:55:23', '127.0.0.1', '2017-07-03 14:55:23', '127.0.0.1');
INSERT INTO `dd_admin_role` VALUES (9, '日志模块', 'logs/index', 0, '2017-07-03 15:01:01', '127.0.0.1', '2017-07-03 15:01:01', '127.0.0.1');
INSERT INTO `dd_admin_role` VALUES (10, '公司管理', 'personnel/index,personnel/add,personnel/update,personnel/del', 0, '2017-08-08 14:38:41', '124.128.136.218', '2017-08-12 15:23:13', '119.164.186.177');
INSERT INTO `dd_admin_role` VALUES (11, '观察者权限', 'game/select,game/listorder,goods/select,user/userdetail,user/index,tix/select,fund/charge,fund/goldslist,user/allusertree,user/searchuser', 0, '2018-01-04 14:41:40', '119.163.152.65', '2018-01-04 15:54:59', '119.163.152.65');
COMMIT;

-- ----------------------------
-- Table structure for dd_admin_user
-- ----------------------------
DROP TABLE IF EXISTS `dd_admin_user`;
CREATE TABLE `dd_admin_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(32) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` char(32) NOT NULL DEFAULT '' COMMENT '密码加密',
  `salt` char(4) NOT NULL DEFAULT '' COMMENT '盐',
  `ugroup` tinyint(4) NOT NULL DEFAULT '0' COMMENT '用户组',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '用户状态 正常0禁止1',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `create_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登录ip',
  `update_time` datetime DEFAULT NULL COMMENT '登录时间',
  `update_ip` varchar(15) NOT NULL DEFAULT '' COMMENT '登录ip',
  `login_time` datetime DEFAULT NULL,
  `login_ip` varchar(15) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COMMENT='后台用户表';

-- ----------------------------
-- Records of dd_admin_user
-- ----------------------------
BEGIN;
INSERT INTO `dd_admin_user` VALUES (1, 'admin', 'ba2e5cbd206ddc954dc6b43aff1b4dd3', 'qmCT', 1, 0, '2017-06-15 16:56:30', '127.0.0.1', '2017-07-03 16:23:40', '127.0.0.1', NULL, '');
COMMIT;

-- ----------------------------
-- Table structure for dd_system_settings
-- ----------------------------
DROP TABLE IF EXISTS `dd_system_settings`;
CREATE TABLE `dd_system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `update_time` datetime DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `order` tinyint(4) NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '0' COMMENT '1游戏端相关 2代理商相关',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8 COMMENT='系统核心配置';

SET FOREIGN_KEY_CHECKS = 1;
