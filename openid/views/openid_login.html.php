<?php defined("SYSPATH") or die("No direct script access.") ?>
<div id="g-login">
  <ul>
    <li id="g-login-form">
      <form action="openid/auth" method="post" id="openid_form">
          <input type="hidden" name="action" value="verify" />
          <input type="hidden" name="csrf" value="<?= $csrf ?>" />
          <input type="hidden" name="continue_url" value="<?= $continue_url ?>" />
          <fieldset>
            <legend><? echo t("Sign-in") ?></legend>
            <div id="">
              <p><? echo t("Please choose your account provider:") ?></p>
              <div id="">
              <?
                foreach( $login_array as $login_provider):
                  $url = openid::get_provider($login_provider);
                  if( !empty($url) ) :
              ?>
                    <button type="submit" name="signin" 
                      id="<?=$login_provider?>" 
                      class="openid_button"
                      value="<?=$login_provider?>"
                    >
                      <div class="openid_clipwrapper">
                        <img src='<?= url::base() . 'modules/openid/images/icons.png'?>'
                          id="openid_clip_<?=$login_provider?>"
                          class="openid_clip"
                        >
                      </div>
                      <?=$login_provider?>
                    </button>
              <?
                  endif;
                endforeach;
              ?>
              </div>
            </div>
          </fieldset>
      </form>
    </li>
  </ul>
</div>
