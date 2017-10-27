<?php defined("SYSPATH") or die("No direct script access.");
/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2010 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
class openid_installer {

  static function can_activate() {
    $messages = array();
    if(!openid::file_exists_ip( MODPATH . "openid/lib/openid.php")){
      $messages["error"][] =
        t("Can't find the included openid library. Make sure the module is named 'openid' (all lowercase)");
    }
  }

  static function install() {
    $db = Database::instance();
    $db->query("CREATE TABLE IF NOT EXISTS {openid_ids} (
                  `id` int(9) NOT NULL auto_increment,
                  `identity` varchar(255) NOT NULL,
                  `user_id` int(9) NOT NULL,
                  `date_created` int(10) unsigned NOT NULL DEFAULT 0,
                  `date_last_used` int(10) unsigned NOT NULL DEFAULT 0,
                  `provider` varchar(255) NOT NULL,
                  PRIMARY KEY (`id`)
                )
                
                DEFAULT CHARSET=utf8;");
    module::set_version("openid", 2);

    //module::set_var("openid","allowed_login","Gallery,Google,Facebook,Microsoft,Flickr,WordPress,Yahoo");
    module::set_var("openid","allowed_login","Gallery,Facebook,Microsoft");
    module::set_var("openid","loginList","Gallery\nMicrosoft\nFacebook");
    module::set_var("openid","logDebugInfo",FALSE);
  }

  static function upgrade($version) {
    $db = Database::instance();
    if ($version == 1) {
      $db->query("ALTER TABLE {openid_ids} ADD COLUMN `date_last_used` int(10) unsigned NOT NULL DEFAULT 0");
      $db->query("ALTER TABLE {openid_ids} ADD COLUMN `provider` varchar(255) NOT NULL DEFAULT 'unknown'");
      module::set_version("openid", 2);
    }
  }

  static function uninstall() {
    Database::instance()->query("DROP TABLE IF EXISTS {openid_ids};");
  }  
}
?>
