<?php defined("SYSPATH") or die("No direct script access.");
/**
* Gallery - a web based photo album viewer and editor
* Copyright (C) 2000-2010 Bharat Mediratta
* Module OpenID - written by Tomek Kott
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

class Admin_openid_Controller extends Admin_Controller {

  public function index() {
    // Generate a new admin page.
    $view = new Admin_View("admin.html");
    $view->page_title = t("OpenID+ Settings");
    $view->content = new View("admin_openid.html");
    $view->content->OpenID_form = $this->_get_admin_form();
    print $view;
  }

  public function saveprefs() {
    // Save user preferences to the database.

    // Prevent Cross Site Request Forgery
    access::verify_csrf();

    // Make sure the user filled out the form properly.
    $form = $this->_get_admin_form();
    if ($form->validate()) {
      //check the login setup
      $allowed_setup = explode(",", module::get_var("openid","allowed_login"));
      $setup_string = $form->Setup->loginList->value;
      $setup_list = preg_split("/(\r\n|\n|\r|\n\r)/", $setup_string);

      $b_tenant = $form->AADB2C->adTenant->value;
      $b_appid = $form->AADB2C->appID->value;
      $b_secret = $form->AADB2C->appSecret->value;
      $correct_setup_list = array();
      foreach( $setup_list as $setup_line) {
        if( in_array($setup_line,$allowed_setup) ){
          if( 0 == strcmp("Microsoft",$setup_line) ) {
            $b_msft_policy = $form->Microsoft->msftPolicy->value; 
            if( !empty($b_secret) && !empty($b_tenant) && !empty($b_appid) && !empty($b_msft_policy) ) {
              $correct_setup_list[] = $setup_line;
            } else {
              message::error(
                t("Either the AD B2C Tenant, AppID, Secret, or Policy for Microsoft App is not present. 
                  Please fill those values in before logging in with OpenID Connect" 
                )
              );
            }
          } elseif( 0 == strcmp("Facebook",$setup_line) ) {
            $b_fb_policy = $form->Facebook->fbPolicy->value; 
            if( !empty($b_secret) && !empty($b_tenant) && !empty($b_appid) && !empty($b_fb_policy) ) {
              $correct_setup_list[] = $setup_line;
            } else {
              message::error(
                t("Either the AD B2C Tenant, AppID, Secret, or Policy for Facebook App is not present. 
                  Please fill those values in before logging in with OpenID Connect" 
                )
              );
            }
          } else {
            $correct_setup_list[] = $setup_line; 
          } 
        } else {
          message::error(
            t("Cannot add '%line' to the login page; it is not
              on the list of allowed values!"
              , array("line"=>$setup_line)
            )
          );
        }
      }

      if( !in_array("Gallery",$correct_setup_list) ) {
        message::warning( 
          t("You got rid of the Gallery login. This has only removed the 
            link. To login through the gallery, you can always go to %url."
            , array("url"=>url::abs_site('login/html'))
          ) 
        );
      }

      $correct_setup = implode("\n",$correct_setup_list);
      module::set_var("openid","loginList",$correct_setup,"");
      
      module::set_var("openid","adTenant",$form->AADB2C->adTenant->value, "");
      module::set_var("openid","appID",$form->AADB2C->appID->value, "");
      module::set_var("openid","appSecret",$form->AADB2C->appSecret->value, "");

      module::set_var("openid","msftPolicy",  $form->Microsoft->msftPolicy->value, "");
      module::set_var("openid","fbPolicy",  $form->Facebook->fbPolicy->value, "");

      module::set_var("openid","expirationDays", $form->Advanced->expirationDays->value, 180);
      module::set_var("openid","autoDelete",     $form->Advanced->autoDelete->checked, false);
      module::set_var("openid","logDebugInfo",   $form->Advanced->logDebugInfo->checked, false);

      if($form->Advanced->siteClear->checked) {
        $messages = ORM::factory("message")->where("key","LIKE","openid%")->find_all();
        foreach($messages as $message) {
          site_status::clear($message->key);
        }
        message::success(t("The site status messages have been cleared."));
      }
      // Display a success message and redirect back to the TagsMap admin page.
      message::success(t("Your settings have been saved."));
      url::redirect("admin/openid");
    } 
    // Else show the page with errors
    $view = new Admin_View("admin.html");
    $view->content = new View("admin_OpenID.html");
    $view->content->OpenID_form = $form;
    print $view;
  }

  private function _get_admin_form() {
    // Make a new Form.
    $form = new Forge("admin/openid/saveprefs", "", "post",
      array("id" => "g-admin-form")
    );

    //Group for ordering of logins
    $openid_login_group = $form->group("Setup")->label(t("Login Page Setup"));

    $openid_login_group->textarea("loginList")
            ->label(
              t("List of allowed OpenID & OAuth Logins. Order is reflected on login page. Allowed values: %s",
                array("s"=>
                  str_replace(
                    ",",
                    ", ",
                    module::get_var("openid","allowed_login")
                  )
                )
              )
            )
            ->value(module::get_var("openid","loginList",""));

    //Group for AADB2C Settings
    $openid_aadb2c_group = $form->group("AADB2C")->label(t("Azure AD B2C Settings"));
    $openid_aadb2c_group->input("adTenant")->label(t("Azure AD B2C Tenant Name"))
            ->value(module::get_var("openid","adTenant",""))
            ->rule("valid_alpha_numeric");
    $openid_aadb2c_group->input("appID")->label(t("Azure AD B2C Application ID"))
            ->value(module::get_var("openid","appID",""))
            ->rule("valid_alpha_numeric");
    $openid_aadb2c_group->input("appSecret")->label(t("Azure AD B2C Application Secret"))
            ->value(module::get_var("openid","appSecret",""))
            ->rule("valid_alpha_numeric");

    //Group for Microsoft Settings
    $openid_msft_group = $form->group("Microsoft")->label(t("Microsoft App Settings"));
    $openid_msft_group->input("msftPolicy")->label(t("Microsoft Application Sign-In / Sign-out Policy"))
            ->value(module::get_var("openid","msftPolicy",""))
            ->rule("valid_alpha_numeric");

    //Group for Microsoft Settings
    $openid_msft_group = $form->group("Facebook")->label(t("Facebook App Settings"));
    $openid_msft_group->input("fbPolicy")->label(t("Facebook Application Sign-In / Sign-out Policy"))
            ->value(module::get_var("openid","fbPolicy",""))
            ->rule("valid_alpha_numeric");

    //Group for "Advanced Settings"
    $openid_adv_group = $form->group("Advanced")
                             ->label(t("Advanced Settings"));

    $openid_adv_group->checkbox("logDebugInfo")->label(t("Log debugging information"))
            ->checked(module::get_var("openid","logDebugInfo",false));


    //Group for "Advanced Settings"
    $openid_adv_group = $form->group("Advanced")
                             ->label(t("Advanced Settings"));

    $openid_adv_group->checkbox("logDebugInfo")->label(t("Log debugging information"))
            ->checked(module::get_var("openid","logDebugInfo",false));

    $openid_adv_group->input("expirationDays")->label(t("For expiration task: check all identities older than this many days"))
            ->value(module::get_var("openid","expirationDays",180))
            ->rule("valid_numeric");

    $openid_adv_group->checkbox("autoDelete")->label(t("For expiration task: automatically delete old users?"))
            ->checked(module::get_var("openid","autoDelete",false));

    $openid_adv_group->checkbox("siteClear")->label(t("Clear all site status alerts regarding OpenID duplicate users."))
            ->checked(false);

    // Add a save button to the form.
    $form->submit("SaveSettings")->value(t("Save"));

    // Return the newly generated form.
    return $form;
  }

}
?>
