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
class openid_Controller extends Controller {

  /**
   * default action for the openid controller (i.e; gallery/openid/)
   */
  public function index() {
    $login_array = preg_split("/(\r\n|\n|\r|\n\r)/", module::get_var("openid","loginList",""));
    $debug = module::get_var("openid","logDebugInfo",false);
    if($debug) {
      Kohana_Log::add("information", "[OpenID Module] Running index() [openid/index]\n");
      Kohana_Log::add("information", t("[OpenID Module] Login array: %s [openid/index]\n"
        ,array("s"=>openid::array_implode(":",",",$login_array)) )
      );
    }

    $view = new Theme_View("page.html", "other", "login");
    $view->page_title = t("Log in to Gallery");
    $view->content = new View("openid_login.html");
    $view->content->csrf = access::csrf_token();
    $view->content->continue_url = Session::instance()->get("continue_url");
    $view->content->login_array = $login_array;
    print $view;
  }

  /**
   * Action for the openid/auth controller (i.e; gallery/openid/auth)
   */
  public function auth() {
    //access::verify_csrf();  // just to make it easier
    $input = Input::instance();
    // Priority param for form_sign: 1. signin  2. state
    $form_signin = $input->post('signin') ? $input->post('signin') : $input->get('signin');
    if (empty($form_signin)) {
        $form_signin = $input->post('state') ? $input->post('state') : $input->get('state');
    }
    $debug = module::get_var("openid","logDebugInfo",false);
    if($debug) {
      Kohana_Log::add("information", "[OpenID Module] Running auth() [openid/auth]\n");
      Kohana_Log::add("information", "[OpenID Module] Form sign-in: {$form_signin} [openid/auth]\n");
    }

    $valid = false;
    $signin =  strtolower($form_signin);
    if ( $signin == 'gallery') {
        url::redirect("login/html");
    } else {
        $valid = openid::process_openid($signin);
    }

    if ($valid) {
      //Redirect to site top if it's valid 
      $continue_url = item::root()->abs_url();

      if($debug) {
        Kohana_Log::add("information", "[OpenID Module] Login successful: redirecting to {$continue_url} [openid/auth]\n");
      }
      url::redirect($continue_url);
    } else {
      $fail_url = url::abs_site('openid/');
      if($debug) {
        Kohana_Log::add("information", "[OpenID Module] Login not successful: redirecting to '{$fail_url}' [openid/auth]\n");
      }
      message::warning("Something went wrong with the {$form_signin} login process, sorry!");
      url::redirect( $fail_url );
    }
  }

  /**
   * Action for the openid/user controller (i.e; gallery/openid/user)
   */
  public function user() {
    access::verify_csrf();
    $input = Input::instance();

    $form_signin = $input->post('signin') ? $input->post('signin') : $input->get('signin');

    $debug = module::get_var("openid","logDebugInfo",false);
    if($debug) {
      Kohana_Log::add("information", "[OpenID Module] Running user() [openid/user]\n");
      Kohana_Log::add("information", "[OpenID Module] Form sign-in: {$form_signin} [openid/user]\n");
    }

    if( empty($form_signin) ) {
      message::error( t("No OpenID provider selected for entering a username. Please select a provider!") );
      url::redirect("openid/");
    }

    $view = new Theme_View("page.html", "other", "login");
    $view->page_title = t("Provide your username");
    $view->content = new View("openid_user.html");

    $form = new Forge("openid/auth","","post");

    $group = $form->group("User")->label(t("Username"));
    $group->input("user")
      ->label(
          t("%provider username",array("provider"=>$form_signin))
        )
      ->rule("valid_alpha_numeric");
    $group->hidden("signin")->value($form_signin);
    $group->hidden("continue_url")->value(Session::instance()->get("continue_url"));

    $view->content->user_form = $form;
    print $view;
  }
}

?>
