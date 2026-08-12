<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


use \system\classes\Configuration;
use \system\classes\Core;
?>

<section>
  <div class="container login">
    <div class="row" style="width:480px; margin:auto">
      <div class="center span4 well">
        <div class="col-md-6">
          <h3 style="margin-top:0"><strong><span class="glyphicon glyphicon-lock" aria-hidden="true"></span> &nbsp;Sign in</strong></h3>
        </div>
        <div class="col-md-6">
          <?php
          $logo = Core::getSetting('logo_black');
          $base = Configuration::$BASE;
          $logo = str_replace('~', $base, str_replace('~/', '~', $logo));
          ?>
          <img id="loginLogo" src="<?php echo $logo ?>"/>
        </div>
        <br>
        <br>
        <br>
        <legend></legend>

        <div class="text-center" style="padding:25px 0 35px 0">
          <?php
          $login_enabled = Core::getSetting('login_enabled', 'core');
          if( $login_enabled ){
            ?>
            <?php /* Google Sign-In unused on Duckiebots — commented out
            <div id="g-signin" class="text-left" style="margin-left:100px;"></div>
            <img id="signin-loader" src="<?php echo Configuration::$BASE ?>images/loading_blue.gif" style="display:none; width:32px; height:32px; margin-top:10px">
            */ ?>
            <?php
            // get list of login plugins files
            $login_addon_files_per_pkg = Core::getPackagesModules('login', null);
            // render add-ons (Duckietown token sign-in)
            foreach ($login_addon_files_per_pkg as $pkg_id => $login_addon_files) {
              require_once $login_addon_files[0];
            }
          }else{
            ?>
            <h3>Login disabled.</h3>
            <?php
          }
          ?>
        </div>

        <legend style="margin-top:4px"></legend>

        <?php /* Google API notice unused — commented out
        <p style="color:grey">
          <?php echo Core::getSiteName() ?> uses the <a href="https://developers.google.com/identity/">Google Sign-In API</a>
          authentication service.
        </p>
        */ ?>
        <p style="color:grey; margin: 0;">
          Paste your Duckietown token to sign in.
          <a href="https://hub.duckietown.com/profile/" target="_blank">Get a token</a>
          if you do not have one.
        </p>
      </div>
    </div>
  </div>
  <p class="text-left muted" style="color:grey; margin: 10px auto; width:480px; font-size: 12px;">
    &copy; Copyright <?php echo date("Y"); ?> - <?php echo Core::getSiteName() ?>
  </p>
</section>

<script type="text/javascript">
    $(window).on('COMPOSE_LOGGED_IN', function(){
        let resource = "<?php echo trim(Configuration::$BASE . base64_decode($_GET['q'])) ?>";
        if (resource.length > 0) {
            window.open(resource, "_top");
        } else {
            location.reload();
        }
    });
</script>
