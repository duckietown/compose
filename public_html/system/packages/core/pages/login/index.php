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
          $normalize_logo = function ($value) {
              if (!is_string($value)) {
                  return '';
              }
              $value = trim($value);
              if ($value === '') {
                  return '';
              }
              return str_replace('~', Configuration::$BASE, str_replace('~/', '~', $value));
          };
          $logo_light = $normalize_logo(Core::getSetting('logo_black', 'core', ''));
          $logo_dark = $normalize_logo(Core::getSetting('logo_white', 'core', ''));
          if ($logo_light === '') {
              $logo_light = $logo_dark;
          }
          if ($logo_dark === '') {
              $logo_dark = $logo_light;
          }
          if ($logo_light !== '') {
        ?>
        <style>
          #loginLogoDark { display: none; }
          html[data-dt-theme="dark"] #loginLogoLight { display: none; }
          html[data-dt-theme="dark"] #loginLogoDark { display: inline; }
        </style>
        <img id="<?php echo ($logo_dark !== '' && $logo_dark !== $logo_light) ? 'loginLogoLight' : 'loginLogo' ?>"
             src="<?php echo htmlspecialchars($logo_light, ENT_QUOTES, 'UTF-8') ?>" alt="">
        <?php if ($logo_dark !== '' && $logo_dark !== $logo_light) { ?>
        <img id="loginLogoDark" src="<?php echo htmlspecialchars($logo_dark, ENT_QUOTES, 'UTF-8') ?>" alt="">
        <?php } ?>
        <?php } ?>
      </div>
      <p class="dt-login-lead">Sign in with Google or your Duckietown token.</p>

        <div class="text-center">
          <?php
          $login_enabled = Core::getSetting('login_enabled', 'core');
          if( $login_enabled ){
            ?>
            <div id="g-signin" class="text-center" style="display:inline-block; margin: 0 auto 12px;"></div>
            <img id="signin-loader" src="<?php echo htmlspecialchars(Configuration::$BASE, ENT_QUOTES, 'UTF-8') ?>images/loading_blue.gif" style="display:none; width:32px; height:32px; margin:8px auto;" alt="">
            <?php
            $login_addon_files_per_pkg = Core::getPackagesModules('login', null);
            if (count($login_addon_files_per_pkg) > 0) {
              echo '<legend style="width: 100px; margin: 12px auto"></legend>';
            }
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
