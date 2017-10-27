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

/**
* This is the API for handling openid data
*/
class openid_Core {

  /**
   * Get the provider URL for a given name
   *
   * @param string $name Name of the openid provider
   * @return string The URL of the provider, or an empty string if none found
   */

  static function get_provider( $name ) {
    $name = strtolower($name);
    $all_providers = array(
      "microsoft" => "true",
      "facebook" => "true",
      "gallery" => "true",
      "google" => "https://www.google.com/accounts/o8/id",
      "yahoo" => "https://me.yahoo.com/"
    );

    if(array_key_exists($name, $all_providers) ) {
      return $all_providers[$name];
    } else {
      return "";
    }
  }

  /**
   * Implode an array with the key and value pair giving
   * a glue, a separator between pairs and the array
   * to implode.
   * @param string $glue The glue between key and value
   * @param string $separator Separator between pairs
   * @param array $array The array to implode
   * @return string The imploded array
   */
  static function array_implode( $glue, $separator, $array ) {
    if ( ! is_array( $array ) ) return $array;
    $string = array();
    foreach ( $array as $key => $val ) {
      if ( is_array( $val ) )
        $val = implode( ',', $val );
      $string[] = "{$key}{$glue}{$val}";
    }
    return implode( $separator, $string );
  }

 
  /**
   * Process Microsoft logins; used in both reauthentication and login modes
   *
   * @return boolean whether the login was successful
   */
  static function process_openid($signin) {
    $debug = module::get_var("openid","logDebugInfo",false);
    if($debug) {
      Kohana_Log::add("information", "[OpenID Module] Start process_openid() [openid::process_openid]\n");
    }

    $input = Input::instance();
    $valid = false;

    $id_token = $input->post("id_token") ? $input->post("id_token") : $input->get("id_token");

    $tenant = module::get_var("openid","adTenant","");;
    $client_id = module::get_var("openid","appID","");
    $client_secret = module::get_var("openid","appSecret","");
    $policy = null;
    switch($signin) {
      case 'microsoft':
        $policy = module::get_var("openid","msftPolicy","");
        break;
      case 'facebook':
        $policy = module::get_var("openid","fbPolicy","");
        break;
    }

    if( empty($tenant) || empty($client_id) || empty($client_secret) || empty($policy) ) {
      if($debug) {
        Kohana_Log::add("error", "[OpenID Module] No Azure AD B2C Tenant name, AppID, App Secret, or Policy given [openid::process_openid]\n");
       }
       return false;
     }

     if( empty($id_token) ) {

       $openid_array = array(
          'p' => $policy,
          'client_id' => $client_id,
          'scope' => 'openid',
          'response_type' => 'id_token',
          'response_mode' => 'form_post',
          'redirect_uri' => url::site( 'openid/auth','http' ),
          'state' => $signin
       );
       $azure_ad_b2c_url = sprintf("https://login.microsoftonline.com/%s.onmicrosoft.com/oauth2/v2.0/authorize?%s", $tenant, http_build_query($openid_array,'','&'));

       if($debug) {
         Kohana_Log::add("information", "[OpenID Module] Microsoft URL: {$azure_ad_b2c_url} [openid::process_openid]\n");
       }
       url::redirect($azure_ad_b2c_url);
    } // endif

    if( !empty($id_token) ) {
      // State matches
      $msft_error = $input->get("error","");

      //check for errors
      if( !empty( $msft_error ) ) {
        $message = "There was an error authenticating with Microsoft. Description: '"
          . $input->get("error_description","")
          ."', Reason: '" . $input->get("error_reason","") . "'";
        message::warning( $message );
        if($debug) {
          Kohana_Log::add("error", "[OpenID Module] {$message} [openid::process_openid]\n");
        }

        url::redirect( 'openid' );
      }

      // Verify token
      require_once(MODPATH . "openid/lib/EndpointHandler.php");
      set_include_path(MODPATH . "openid/lib/phpseclib");
      require_once "Crypt/RSA.php";
      require_once( MODPATH . "openid/lib/TokenChecker.php");

      $resp_type = "id_token";
      $tokenChecker = new TokenChecker($tenant, $id_token, $resp_type,$client_id, $client_secret, $policy); 

      $verified = $tokenChecker->authenticate();
      if ($verified == false) {
          Kohana_Log::add("ERROR", "[OpenID Module] Token validation error [openid::process_openid]\n");
          return false;
       }

      // Fetch user's email and check if admin
      $email = $tokenChecker->getClaim("emails");
      $email = $email[0];
      $attributes = array();
      $attributes['identity']            = $tokenChecker->getClaim('oid');
      $attributes['contact/email']       = $email;
      $attributes['namePerson/friendly'] = $tokenChecker->getClaim('given_name');
      $attributes['namePerson/fullname']    = $tokenChecker->getClaim('name');
      $attributes['namePerson/first']    = $tokenChecker->getClaim('given_name');
      //$attributes['namePerson/last']     = '';
      $attributes['provider']            = $tokenChecker->getClaim('idp');

      Kohana_Log::add("information",t("[OpenID Module] Translated attribute array: %s [openid::process_openid]\n",
            array("s" => openid::array_implode(":",",",$attributes)))
          );
      $valid = openid::process_user( $attributes );
    }

    return $valid;

  }

  /**
   * this function is ONLY called if the user has been validated through the
   * OpenID login. Therefore, we can (safely?) assume that we want to process
   * the user
   *
   * @param array $attributes
   * @return boolean
   */
  static function process_user($attributes) {
    $debug = module::get_var("openid","logDebugInfo",false);
    if($debug) {
      Kohana_Log::add("information", 
        sprintf("[OpenID Module] Processing user login: %s [process_user()]\n",
          openid::array_implode(':',',',$attributes)
        )
      );
    }

    $user = NULL;
    $valid = false;

    $identity = openid::find_identity($attributes);

    if($identity->loaded()) {
      // We found an identity! Let's double check that the actual user also exists
      if($debug) {
        Kohana_Log::add("information", 
                sprintf("[OpenID Module] Found an identity: %s [process_user()]\n",
                  openid::array_implode(':',',',$identity)
                )
              );
      }
      $user = user::lookup($identity->user_id);
      if(is_null($user)) {
        // Erk, we have an identity, but no user! Delete the $user_identity and start over
        if($debug) {
          Kohana_Log::add("information",
            "[OpenID Module] Found an identity, but no user. Deleting and starting over [process_user()]\n"
          );
        }
        $identity->delete();
        list ($valid, $user) = openid::create_new_user($attributes);

      } else {
        // we have an authenticated identity AND we have a user, so log them in!
        if($debug) {
          Kohana_Log::add("information", "[OpenID Module] Found an identity & user. Will log in! [process_user()]\n");
        }
        $identity->date_last_used = time();
        $identity->save();
        $valid = true;
      }
    } else {
      // User hasn't been found - so we need to add the user!
      if($debug) {
        Kohana_Log::add("information", "[OpenID Module] No identity found: provisioning user [process_user()]\n");
      }
      list ($valid, $user ) = openid::create_new_user($attributes);
    }

    if($valid) {
      // double check that everything is hunky-dorry
      if(is_null($user)) {
        if($debug) {
          Kohana_Log::add("information", "[OpenID Module] For some reason, user information is now null! [process_user()]\n");
        }
        $valid = false;
      } else {
        if($debug) {
          Kohana_Log::add("information", "[OpenID Module] logging in the user: {$user->name} [process_user()]\n");
        }
        auth::login($user);
      }
    }

    return $valid;
  }

  /**
   * find whether the given openid identity has ever loggedin before
   *
   * @param array $attributes
   * @return ORM::openid_identity
   */
  static function find_identity($attributes) {
    $debug = module::get_var("openid","logDebugInfo",false);
    if($debug) {
      Kohana_Log::add("information", 
        sprintf("[OpenID Module] Trying to find an identity: %s [find_identity()]\n",md5($attributes['identity']))
      );
    }
    $user_identity = ORM::factory("openid_id")->where("identity", "=", md5($attributes['identity']))->find();

    return $user_identity;    
  }

  /**
   * find the most recently used identity corresponding to a user id
   *
   * @param int $user_id
   * @return ORM::openid_identity
   */
  static function find_recent_identity($user_id) {
    $debug = module::get_var("openid","logDebugInfo",false);
    if($debug) {
      Kohana_Log::add("information",
        sprintf("[OpenID Module] Trying to find a recently used identity corresponding to user_id: %s [find_recent_identity()]\n", $user_id)
      );
    }
    $user_identity = ORM::factory("openid_id")->where("user_id", "=", $user_id)->order_by('date_last_used','desc')->find();

    if($debug && $user_identity->loaded()) {
      Kohana_Log::add("information",
        sprintf("[OpenID Module] Found identity corresponding to user_id: %s, identity: %d [find_recent_identity()]\n", $user_id, $user_identity->id)
      );
    }

    return $user_identity;
  }

  /**
   * Creates a new user and adds the proper
   *
   * @param array $attributes
   * @return array(boolean,ORM::user)
   */
  static function create_new_user($attributes) {
    $debug = module::get_var("openid","logDebugInfo",false);
    if($debug) {
      Kohana_Log::add("information", "[OpenID Module] Trying to create a user! [create_new_user]\n");
    }
    /** See what kind of information we were able to get. We are most interested
     * 1. contact/email
     * 2. namePerson/friendly
     * 3. namePerson/first
     * 4. namePerson/last
     */

    $email = (isset($attributes['contact/email']) ? $attributes['contact/email'] : "");

    $username =
      (isset($attributes['namePerson/friendly']) ? $attributes['namePerson/friendly'] : 
            (empty($email) ? md5($attributes['identity']) : $email));

    if(identity::lookup_user_by_name($username)) {
      $dup_username = $username;
      $username = md5($attributes['identity']);
      message::warning(t("A duplicate username might have been found. For now, a new username has
          been provisioned for you. If this is a mistake, it is possible that you previously logged
          in with a different OpenID provider."));

      site_status::warning(t("A new identity tried to duplicate the user: {$dup_username}. Instead, a
        new user was created ({$username}). Go to the <a href=\"%uURL\">user admin</a> to take care 
        of the duplicate username, or to the <a href=\"%pURL\">plugin admin</a> to clear the 
        site status.",array("uURL" => url::site("admin/users"),"pURL" => url::site("admin/openid"))),"openid-{$username}");
    }

    $firstname =
      (isset($attributes['namePerson/first']) ? $attributes['namePerson/first'] : "");
    //$lastname =
    //  (isset($attributes['namePerson/last']) ? $attributes['namePerson/last'] : "");
    //$full_name = trim($firstname . " " . $lastname);
    //$full_name = empty($full_name) ? $username : $full_name;
    $full_name =
      ( isset($attributes['namePerson/fullname']) ? $attributes['namePerson/fullname'] : $firstname);

    if($debug) {
      Kohana_Log::add("information",
        "[OpenID Module] User info: username: {$username}, email: {$email}, name: {$full_name} [create_new_user]\n"
      );
    }

    $password = md5(uniqid(mt_rand(), true));
    $new_user = identity::create_user($username, $full_name, $password, $email);
    //$new_user->admin = false;
    $new_user->admin = true;
    //$new_user->guest = true;
    $new_user->guest = false;
    $new_user->save();
    
    $new_identity = ORM::factory("openid_id");
    $new_identity->user_id = $new_user->id;
    $new_identity->identity = md5($attributes['identity']);
    $new_identity->provider = $attributes['provider'];
    $new_identity->date_created = time();
    $new_identity->date_last_used = time();
    $new_identity->save();

    if($debug) {
      Kohana_Log::add("information", 
        sprintf("[OpenID Module] openid_id: %s [create_new_user]\n",
          openid::array_implode(':',',',$identity)
        )
      );
    }

    return array( $new_identity->saved() && $new_user->saved() , $new_user );
  }
  
  /**
   * Deletes an identity 
   *
   * @param array $identity The identity to delete
   * @param boolean $delete_user Whether or not to delete the user at the same time as identity
   */
  public static function delete_identity($identity, $delete_user = false) {
    $debug = module::get_var("openid","logDebugInfo",false);
    if($debug) {
      Kohana_Log::add("information", "[OpenID Module] Deleting an identity: {$identity->id}.\n");
    }
    
    //Get the user id before deleting so we can find and delete the user as well
    $id = $identity->user_id;
    
    //Delete the identity first so that it doesn't conflict with the hook
    // for user_deleted, which is detected in openid_task
    if($identity->loaded()) {
      $identity->delete();
    }

    if($delete_user) {
      if($debug) {
        Kohana_Log::add("information", "[OpenID Module] Deleting a user connected with the above identity: {$id}.\n");
      }
      $user = user::lookup($id);
      $user->delete();
    }
  }

  static function file_exists_ip($filename) {
    $debug = module::get_var("openid","logDebugInfo",false);
    if($debug) {
      Kohana_Log::add("information", "[OpenID Module] Trying to find file: {$filename}.\n");
    }
    if(function_exists("get_include_path")) {
      $include_path = get_include_path();
    } elseif(false !== ($ip = ini_get("include_path"))) {
      $include_path = $ip;
    } else {return false;}

    if(false !== strpos($include_path, PATH_SEPARATOR)) {
      if(false !== ($temp = explode(PATH_SEPARATOR, $include_path)) && count($temp) > 0) {
        for($n = 0; $n < count($temp); $n++) {
          if(false !== @file_exists($temp[$n] . DIRECTORY_SEPARATOR .  $filename)) {
            return true;
          }
        }
        return false;
      } else {return false;}
    } elseif(!empty($include_path)) {
      if(false !== @file_exists($include_path . DIRECTORY_SEPARATOR .  $filename)) {
        return true;
      } else {return false;}
    } else {return false;}
  }
}


?>
