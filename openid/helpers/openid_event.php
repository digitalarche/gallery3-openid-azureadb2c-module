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
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
* General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA 02110-1301, USA.
*/
class openid_event_Core {

  /**
  * remove the default login link and use our own
  */
  static function user_menu($menu, $theme) {
    $user = identity::active_user();
    if ($user->guest) {
      // disable the default login
      $menu->remove('user_menu_login');
      // add ours
      $menu->append(Menu::factory("link")
                    ->id("user_menu_openid")
                    ->css_id("g-openid-menu")
                    ->url(url::site("openid"))
                    ->label(t("Login")));
    }
  }

  static function admin_menu($menu, $theme) {
    $menu->get("settings_menu")
      ->append(Menu::factory("link")
               ->id("openid_menu")
               ->label(t("OpenID+"))
               ->url(url::site("admin/openid")));
  }

  static function user_deleted($user) {
    $debug = module::get_var("openid","logDebugInfo",false);
    if($debug) {
      Kohana_Log::add("information", "[OpenID Module] Trying to delete an identity! [user_deleted]\n");
    }
    
    // Clear the site status relating to this user just in case it is still up.
    site_status::clear("openid-{$user->name}");
    
    $identity = ORM::factory("openid_id")->where("user_id","=",$user->id)->find();
    if($identity->loaded()) {
      if($debug) {
        Kohana_Log::add("information", "[OpenID Module] Deleting identity: {$identity->id} [user_deleted]\n");
      }
      $identity->delete();
    }
  }
}
