<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele


use \system\classes\Configuration;
use \system\classes\Core;
?>

<section>
  <div class="dt-login">
    <div class="dt-login-card">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px;">
        <h3><i class="fa fa-lock" aria-hidden="true"></i> Sign in</h3>
        <?php
          $logo = Core::getSetting('logo_black', 'core', '');
          if (!is_string($logo) || strlen(trim($logo)) === 0) {
              $logo = Core::getSetting('logo_white', 'core', '');
          }
          if (!is_string($logo)) {
              $logo = '';
          }
          $logo = str_replace('~', Configuration::$BASE, str_replace('~/', '~', $logo));
          if (strlen(trim($logo)) > 0) {
        ?>
        <img id="loginLogo" src="<?php echo htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="">
        <?php } ?>
      </div>
      <p class="dt-login-lead">Paste your Duckietown token to sign in.</p>

        <div class="text-center">
          <?php
          $login_enabled = Core::getSetting('login_enabled', 'core');
          if( $login_enabled ){
            $login_addon_files_per_pkg = Core::getPackagesModules('login', null);
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
    </div>
  </div>
  <p class="dt-login-copy">
    &copy; Copyright <?php echo date("Y"); ?> - <?php echo htmlspecialchars((string) (Core::getSiteName() ?? ''), ENT_QUOTES, 'UTF-8') ?>
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
