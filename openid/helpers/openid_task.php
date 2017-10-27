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
class openid_task_Core {
  static function available_tasks() {
    $curr_time = time();

    $search_days = module::get_var("openid","expirationDays",180);
    $search_time = $curr_time - $search_days*3600*24;

    $inactive_users = ORM::factory("user")->
      where("last_login","<", (int) $search_time)->
      and_where("last_login","<>",0)->find_all();

    $user_ids = array();

    foreach($inactive_users as $user) {
      $user_ids[] = $user->id;
    }

    if( count($user_ids) ) {
      $count = ORM::factory("openid_id")->where("user_id","in",$user_ids)->count_all();
    } else {
      $count = 0;
    }

    return array(Task_Definition::factory()
                 ->callback("openid_task::expireusers")
                 ->name(t("Delete old identities"))
                 ->description( $count ?
                         t2(
                          "There is potentially one identity who hasn't logged in for %days days. Check and delete old users & identities created using the OpenID plugin.",
                          "There are potentially %count identities who haven't logged in for %days days. Check and delete old users & identities created using the OpenID plugin.",
                          $count,
                          array("days"=>$search_days)
                         ) :
                         t("Check and delete old identities that were created via the OpenID plugin. No candidate users were found.")
                   )
                 ->severity($count ? log::WARNING : log::SUCCESS ),
        );
  }

  static function expireusers($task) {
    try {
      $curr_time = time();
      $search_time = $curr_time - module::get_var("openid","expirationDays",180)*3600*24;
      
      $task->log("Searching for inactive users (time: " . $curr_time . ", search-time: " . $search_time . ")");

      $inactive_users = ORM::factory("user")->
        where("last_login","<", (int) $search_time)->
        and_where("last_login","<>",0)->find_all();
      
      $user_ids = array();

      foreach($inactive_users as $user) {
        $user_ids[] = $user->id;
      }
  
      if( count($user_ids) ) {

        $task->log(
          sprintf("Searching for identities with the following user ids: %s", implode(',',$user_ids))
        );

        $identities = ORM::factory("openid_id")->where("user_id","in",$user_ids);
        $identity_all = $identities->find_all();
        $identity_count = $identity_all->count();
        $i = 0; 
      
        $task->log("Found " . $identity_count . " identities to delete.");

        if($identity_count > 0 ) {
          $start = microtime(true);
  
          foreach($identity_all as $identity) {
            if (!isset($start)) {
              $start = microtime(true);
            }

            openid::delete_identity($identity, module::get_var("openid","autoDelete",false) );
          
            $task->percent_complete = $i / $identity_count * 100;
            if (microtime(true) - $start > 50) {
              break;
            }
          }
        } else {
          message::error("No identities were found to delete!");
          $task->log("No identities were found to delete!");  
        }
      } else {
        $task->log("No inactive users were found to delete!");  
      }

    } catch (Exception $e) {
      $task->done = true;
      $task->state = "error";
      $task->status = $e->getMessage();
      $task->log((string)$e);
    }
    
    $task->done = true;
    $task->percent_complete = 100;
    $task->state = "success";
    $task->status = "Finished!";
  }

}
