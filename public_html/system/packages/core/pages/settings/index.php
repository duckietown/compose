<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele

use \system\classes\Core;
use \system\classes\Cache;
use system\classes\EditableConfiguration;

// update
if (isset($_GET['base_update']) && boolval($_GET['base_update'])) {
  include_once "update.php";
  return;
}
?>

<style type="text/css">
	.text-color-red{
		color: var(--r-bad, #b91c1c);
	}
</style>


<h2 class="page-title"></h2>

<?php
include_once "sections/packages.php";
include_once "sections/pages.php";
include_once "sections/api.php";
include_once "sections/cache.php";
include_once "sections/package_specific.php";
include_once "sections/codebase.php";
include_once "sections/user_roles.php";
include_once "sections/php_info.php";


include_once "sections/theme.php";


$settings_tabs = [
    // [0-20] reserved for \compose\ tabs
    0 => [
        'id' => 'general',
        'title' => 'Dashboard',
        'icon' => 'fa fa-sliders',
        'content' => settings_custom_package_tab,
        'content_args' => ['core', Core::getPackageSettings('core')]
    ],
];

$developer_mode = (bool) Core::getSetting('developer_mode', 'core', false);

if ($developer_mode) {
    $settings_tabs[1] = [
        'id' => 'packages',
        'title' => 'Packages',
        'icon' => 'fa fa-cubes',
        'content' => settings_packages_tab,
        'content_args' => null
    ];
    $settings_tabs[2] = [
        'id' => 'pages',
        'title' => 'Pages',
        'icon' => 'fa fa-file-text-o',
        'content' => settings_pages_tab,
        'content_args' => null
    ];
    $settings_tabs[3] = [
        'id' => 'api',
        'title' => 'API End-points',
        'icon' => 'fa fa-sitemap',
        'content' => settings_api_tab,
        'content_args' => null
    ];
    $settings_tabs[4] = [
        'id' => 'roles',
        'title' => 'User roles',
        'icon' => 'fa fa-users',
        'content' => settings_user_roles_tab,
        'content_args' => null
    ];
    $settings_tabs[10] = [
        'id' => 'theme',
        'title' => 'Theme',
        'icon' => 'fa fa-paint-brush',
        'content' => settings_theme_tab,
        'content_args' => null
    ];
    // [21-100] reserved for packages
    // [101-400] free to use
    // #501 reserved for cache tab
    // [502-600] reserved for \compose\ tabs
    $settings_tabs[502] = [
        'id' => 'php',
        'title' => 'PHP Info',
        'icon' => 'fa fa-server',
        'content' => settings_phpinfo_tab,
        'content_args' => null
    ];
    $settings_tabs[580] = [
        'id' => 'codebase',
        'title' => 'Codebase',
        'icon' => 'fa fa-code',
        'content' => settings_codebase_tab,
        'content_args' => null
    ];

    if( Cache::enabled() ){
        // add cache tab if the flag is active
        $settings_tabs[501] = [
            'id' => 'cache',
            'title' => 'Cache',
            'icon' => 'fa fa-history',
            'content' => settings_cache_tab,
            'content_args' => null
        ];
    }

    $i = 21;
    $package_tab_titles = [
        'data' => 'Mission Control storage',
        'duckietown' => 'Duckietown Hub',
        'duckietown_duckiebot' => 'Robot dashboard APIs',
        'duckietown_duckiedrone' => 'Duckiedrone',
        'duckietown_ros' => 'Duckietown ROS',
        'portainer' => 'Portainer connection',
        'ros' => 'ROS API',
        'elfinder' => 'File Manager',
        'vscode' => 'Code Editor',
    ];
    foreach (Core::getPackagesList() as $pkg_id => $pkg) {
        if ($pkg_id == 'core') continue;
        $pkg_setts = Core::getPackageSettings($pkg_id);
        // skip package if it is not configurable
        if (!$pkg_setts['data'] instanceof EditableConfiguration ||
            !$pkg_setts['data']->is_configurable()){
            continue;
        }
        $title = isset($package_tab_titles[$pkg_id])
            ? $package_tab_titles[$pkg_id]
            : ('Package: <b>'.$pkg['name'].'</b>');
        // render package-specific tab
        $settings_tabs[$i] = [
            'id' => 'package_'.$pkg_id,
            'title' => $title,
            'icon' => 'fa fa-cube',
            'content' => settings_custom_package_tab,
            'content_args' => [$pkg_id, $pkg_setts]
        ];
        // ---
        $i += 1;
    }
}
?>

<div class="dt-page dt-settings dt-form">
<p class="robot-hint"><?php echo $developer_mode
    ? 'Dashboard Settings control this website. Operator options are in the first panel; developer sections follow.'
    : 'Dashboard Settings control this website. Turn on Developer mode to see advanced dashboard options.'; ?></p>
<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
    <?php
    $tab_idxs = array_keys($settings_tabs);
    sort( $tab_idxs );
    foreach( $tab_idxs as $tab_idx) {
        $settings_tab = $settings_tabs[$tab_idx];
        $header = $settings_tab['id'].'_header';
        $collapse = $settings_tab['id'].'_collapse';
        ?>
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="<?php echo $header ?>">
                <a id="collapse_a_<?php echo $collapse ?>" class="collapsed collapse_a" role="button" data-toggle="collapse" data-parent="#accordion" href="#<?php echo $collapse ?>" aria-expanded="true" aria-controls="<?php echo $collapse ?>">
                    <h4 class="panel-title">
                        <span class="<?php echo $settings_tab['icon'] ?>" aria-hidden="true"></span>
                        &nbsp;
                        <?php echo $settings_tab['title'] ?>
                        <!--  -->
                        <span id="<?php echo $settings_tab['id'] ?>_unsaved_changes_mark" class="dt-unsaved" style="display:none">
                            Unsaved changes
                            <span class="fa fa-exclamation-triangle" aria-hidden="true"></span>
                        </span>
                    </h4>
                </a>
            </div>
            <div id="<?php echo $collapse ?>" class="panel-collapse collapse <?php echo ($tab_idx == 0)? 'in' : '' ?>" role="tabpanel" aria-labelledby="<?php echo $header ?>">
                <div class="panel-body">
                    <?php
                    call_user_func( $settings_tab['content'], $settings_tab['content_args'], $settings_tab['id'] );
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
</div>
</div>

<script type="text/javascript">
	// append hash to URL so that if we reload the page we can go back to the previous tab
	$('.collapse').on('shown.bs.collapse', function () {
		location.hash = 'sel:{0}'.format( $(this).attr('id') );
	});

	$(document).ready(function(){
		var collapsible_id = location.hash.replace('#sel:', '');
		if( collapsible_id.length > 2 && collapsible_id !== 'general_collapse' ){
			// show selected tab
			$('#collapse_a_'+collapsible_id).trigger( 'click' );
		}
	});
</script>
