<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu

use system\classes\Core;
use system\classes\Configuration;

$_TOPBAR_H = 52;

$main_user_role = Core::getUserRole();
$user_roles = Core::getUserRolesList();
$login_enabled = Core::getSetting('login_enabled', 'core');
$developer_mode = (bool) Core::getSetting('developer_mode', 'core', false);

$pages_whitelist = null;
$pages_blacklist = null;
if (!Core::isComposeConfigured()) {
    $pages_whitelist = ['setup'];
} else {
    // File Manager / Portainer live as Robot tabs; Login is a top-bar action.
    $pages_blacklist = ['setup', 'file-manager', 'login', 'portainer'];
}

$developer_only_pages = [
    'package_store',
    'users',
    'profile',
    'api',
    'desktop',
    'onboarding',
    'vscode',
    'code-editor',
    'code',
    'maintenance',
];

$page_is_navigable = function ($page) use (
    $pages_whitelist,
    $pages_blacklist,
    $main_user_role,
    $user_roles,
    $developer_mode,
    $developer_only_pages
) {
    if (!is_array($page) || !isset($page['id'])) {
        return false;
    }
    $id = $page['id'];
    if ($id === 'login') {
        return false;
    }
    if (isset($page['enabled']) && empty($page['enabled'])) {
        return false;
    }
    if (isset($page['access_level']) && is_array($page['access_level'])
        && count(array_intersect($user_roles, $page['access_level'])) === 0) {
        return false;
    }
    if (!is_null($pages_whitelist) && !in_array($id, $pages_whitelist)) {
        return false;
    }
    if (!is_null($pages_blacklist) && in_array($id, $pages_blacklist)) {
        return false;
    }
    if ($main_user_role != 'administrator' && Core::getSetting('maintenance_mode', 'core')) {
        return false;
    }
    if (!$developer_mode && in_array($id, $developer_only_pages)) {
        return false;
    }
    $exclude_roles = isset($page['menu_entry']['exclude_roles']) ? $page['menu_entry']['exclude_roles'] : [];
    if (is_array($exclude_roles) && count(array_intersect($user_roles, $exclude_roles)) > 0) {
        return false;
    }
    return true;
};

$pages = Core::getFilteredPagesList(
    'by-menuorder',
    true,
    $user_roles
);
$page_rows = $pages;
if (isset($page_rows['by-menuorder']) && is_array($page_rows['by-menuorder'])) {
    $page_rows = $page_rows['by-menuorder'];
}

$visible = [];
foreach ($page_rows as $page) {
    if (!is_array($page) || !isset($page['id'], $page['menu_entry'])) {
        continue;
    }
    if (!$page_is_navigable($page)) {
        continue;
    }
    $visible[$page['id']] = $page;
}

$pages_by_id = Core::getPagesList('by-id');
if (!is_array($pages_by_id)) {
    $pages_by_id = [];
}

$primary_nav = [];
$more_nav = [];
if (isset($visible['robot'])) {
    // Brand (logo + name) is the home link to Robot; do not repeat it as a nav chip.
    unset($visible['robot']);
}
if (isset($visible['settings'])) {
    // Settings is the cog on the right, not a primary nav link.
    unset($visible['settings']);
}
foreach ($visible as $page) {
    if (in_array($page['id'], $developer_only_pages, true)) {
        $more_nav[] = $page;
    } else {
        $primary_nav[] = $page;
    }
}

$current_page_id = Configuration::$PAGE;
$current_page_name = ucfirst((string) Core::getPageDetails($current_page_id, 'name'));
$show_page_title = ($current_page_id !== 'robot');

$logo = Core::getSetting('logo_black', 'core', '');
if (!is_string($logo) || strlen(trim($logo)) === 0) {
    $logo = Core::getSetting('logo_white', 'core', '');
}
if (!is_string($logo)) {
    $logo = '';
}
$logo = str_replace('~', Configuration::$BASE, str_replace('~/', '~', $logo));

$navbar_title = Core::getSetting('navbar_title', 'core', '');
if (!is_string($navbar_title)) {
    $navbar_title = '';
}

$navbar_subtitle = Core::getSetting('navbar_subtitle', 'core', '');
if (!is_string($navbar_subtitle)) {
    $navbar_subtitle = '';
}

// Brand home is Robot only when that page is allowed for this viewer.
$robot_page = (isset($pages_by_id['robot']) && is_array($pages_by_id['robot']))
    ? $pages_by_id['robot']
    : null;
$robot_home_allowed = ($robot_page && $page_is_navigable($robot_page));
$home_url = $robot_home_allowed ? Core::getURL('robot') : Configuration::$BASE;
if (!is_string($home_url) || $home_url === '') {
    $home_url = Configuration::$BASE;
}

$settings_page = (isset($pages_by_id['settings']) && is_array($pages_by_id['settings']))
    ? $pages_by_id['settings']
    : null;
$show_settings = ($settings_page && $page_is_navigable($settings_page));

if (!function_exists('_ctheme_esc')) {
function _ctheme_esc($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}
}

if (!function_exists('_ctheme_page_icon')) {
function _ctheme_page_icon($page) {
    return sprintf(
        '%s %s-%s',
        $page['menu_entry']['icon']['class'],
        $page['menu_entry']['icon']['class'],
        $page['menu_entry']['icon']['name']
    );
}
}

if (!function_exists('_ctheme_nav_link_class')) {
function _ctheme_nav_link_class($page, $current_page_id) {
    $children = isset($page['child_pages']) && is_array($page['child_pages']) ? $page['child_pages'] : [];
    $active = ($current_page_id == $page['id']) || in_array($current_page_id, $children);
    return $active ? 'active' : '';
}
}
?>

<style type="text/css">
    ._ctheme_page ._ctheme_top_bar {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: <?php echo $_TOPBAR_H ?>px !important;
        color: #1e1e1e;
        padding: 0 16px;
        font-size: 14px;
        font-weight: 500;
        letter-spacing: -0.01em;
        border-bottom: 1px solid #e0e0e0;
        background: #ffffff;
        box-shadow: none;
        display: flex;
        align-items: center;
        z-index: 40;
        overflow: visible;
    }

    ._ctheme_page ._ctheme_top_bar a {
        color: #444;
    }

    ._ctheme_page a:hover {
        text-decoration: none;
    }

    ._ctheme_top_bar_inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        gap: 16px;
        min-width: 0;
    }

    ._ctheme_top_bar_left {
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
        flex: 1 1 auto;
    }

    ._ctheme_brand {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 0 0 auto;
        color: #1e1e1e;
        text-decoration: none;
    }

    ._ctheme_brand:hover,
    ._ctheme_brand:focus {
        color: #1e1e1e;
        text-decoration: none;
        opacity: 0.85;
    }

    ._ctheme_brand.is-current {
        opacity: 1;
    }

    ._ctheme_brand img {
        height: 28px;
        width: auto;
        display: block;
    }

    ._ctheme_brand_text {
        display: flex;
        flex-direction: column;
        min-width: 0;
        line-height: 1.15;
    }

    ._ctheme_brand_title {
        font-size: 14px;
        font-weight: 700;
        color: #1e1e1e;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    ._ctheme_brand_subtitle {
        font-size: 10px;
        font-weight: 400;
        color: #888;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    ._ctheme_nav_links {
        display: flex;
        align-items: center;
        gap: 2px;
        min-width: 0;
    }

    ._ctheme_nav_link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        height: 32px;
        padding: 0 10px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #444;
        white-space: nowrap;
    }

    ._ctheme_nav_link:hover,
    ._ctheme_nav_link:focus {
        color: #1e1e1e;
        background: #f5f5f5;
        text-decoration: none;
    }

    ._ctheme_nav_link.active {
        color: #1e1e1e;
        background: #f0f0f0;
    }

    ._ctheme_nav_link .fa,
    ._ctheme_nav_link .glyphicon {
        font-size: 12px;
        opacity: 0.75;
    }

    ._ctheme_page_title {
        font-size: 13px;
        font-weight: 600;
        color: #888;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding-left: 8px;
        border-left: 1px solid #e6e6e6;
        line-height: 1.2;
    }

    ._ctheme_top_bar_right {
        display: flex;
        align-items: center;
        gap: 4px;
        flex: 0 0 auto;
    }

    ._ctheme_page ._ctheme_top_bar ._ctheme_top_bar_button,
    ._ctheme_icon_btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 8px;
        border-radius: 6px;
        font-size: 14px;
        color: #555;
        opacity: 1;
    }

    ._ctheme_page ._ctheme_top_bar ._ctheme_top_bar_button:hover,
    ._ctheme_icon_btn:hover,
    ._ctheme_icon_btn:focus,
    ._ctheme_icon_btn.open,
    ._ctheme_user_toggle:hover,
    ._ctheme_user_toggle:focus {
        background: #f5f5f5;
        color: #1e1e1e;
        text-decoration: none;
        opacity: 1;
    }

    ._ctheme_icon_btn.active {
        background: #f0f0f0;
        color: #1e1e1e;
    }

    ._ctheme_user_toggle {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 32px;
        padding: 0 8px 0 4px;
        border-radius: 999px;
        color: #1e1e1e;
        font-size: 12px;
        font-weight: 600;
    }

    ._ctheme_user_toggle img {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        border: 1px solid rgba(0,0,0,0.12);
        object-fit: cover;
    }

    ._ctheme_user_toggle .caret {
        margin-left: 0;
    }

    ._ctheme_top_bar .dropdown-menu {
        margin-top: 8px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        min-width: 200px;
        padding: 6px 0;
        z-index: 50;
    }

    ._ctheme_top_bar .dropdown-menu > li > a {
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 500;
        color: #333;
    }

    ._ctheme_top_bar .dropdown-menu > li > a:hover,
    ._ctheme_top_bar .dropdown-menu > li > a:focus {
        background: #f5f5f5;
        color: #1e1e1e;
    }

    ._ctheme_top_bar .dropdown-menu .dropdown-header {
        padding: 8px 14px 4px;
        font-size: 11px;
        font-weight: 600;
        color: #888;
        text-transform: none;
    }

    ._ctheme_dropdown_foot {
        padding: 6px 14px 2px;
        font-size: 10px;
        color: #999;
    }

    ._ctheme_nav_overflow {
        display: none;
    }

    ._ctheme_top_bar ._ctheme_progress_bar {
        position: absolute;
        width: 100%;
        left: 0;
        right: 0;
        bottom: 0;
        margin: 0;
        pointer-events: none;
    }

    ._ctheme_top_bar ._ctheme_progress_bar #compose_progress_bar.progress {
        height: 2px;
        border-radius: 0;
        margin: 0;
        box-shadow: none;
    }

    #_updates_helper_btn {
        top: 60px;
        right: 20px;
    }

    @media (max-width: 760px) {
        ._ctheme_nav_links,
        ._ctheme_page_title,
        ._ctheme_brand_text,
        ._ctheme_user_name {
            display: none !important;
        }
        ._ctheme_nav_overflow {
            display: inline-flex;
        }
        ._ctheme_more_menu {
            display: none !important;
        }
    }
</style>

<div class="_ctheme_top_bar_inner">
    <div class="_ctheme_top_bar_left">
        <a class="_ctheme_brand<?php echo ($current_page_id === 'robot') ? ' is-current' : '' ?>"
           href="<?php echo _ctheme_esc($home_url) ?>"
           title="<?php echo $robot_home_allowed ? 'Robot dashboard' : 'Home' ?>">
            <img src="<?php echo _ctheme_esc($logo) ?>" alt="">
            <span class="_ctheme_brand_text">
                <span class="_ctheme_brand_title"><?php echo _ctheme_esc($navbar_title) ?></span>
                <?php if (strlen(trim($navbar_subtitle)) > 0) { ?>
                    <span class="_ctheme_brand_subtitle"><?php echo _ctheme_esc($navbar_subtitle) ?></span>
                <?php } ?>
            </span>
        </a>

        <nav class="_ctheme_nav_links" aria-label="Primary">
            <?php foreach ($primary_nav as $page) { ?>
                <a class="_ctheme_nav_link <?php echo _ctheme_nav_link_class($page, $current_page_id) ?>"
                   href="<?php echo _ctheme_esc(Core::getURL($page['id'])) ?>">
                    <span class="<?php echo _ctheme_page_icon($page) ?>" aria-hidden="true"></span>
                    <?php echo _ctheme_esc($page['name']) ?>
                </a>
            <?php } ?>
        </nav>

        <?php if ($show_page_title && strlen($current_page_name) > 0) { ?>
            <div class="_ctheme_page_title"><?php echo _ctheme_esc($current_page_name) ?></div>
        <?php } ?>
    </div>

    <div class="_ctheme_top_bar_right">
        <?php if (count($primary_nav) > 0 || count($more_nav) > 0) { ?>
        <div class="dropdown _ctheme_nav_overflow">
            <a class="_ctheme_icon_btn dropdown-toggle" href="#" data-toggle="dropdown" role="button"
               aria-haspopup="true" aria-expanded="false" title="Menu" aria-label="Menu">
                <span class="fa fa-bars" aria-hidden="true"></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                <?php foreach ($primary_nav as $page) { ?>
                    <li class="<?php echo _ctheme_nav_link_class($page, $current_page_id) ?>">
                        <a href="<?php echo _ctheme_esc(Core::getURL($page['id'])) ?>">
                            <span class="<?php echo _ctheme_page_icon($page) ?>" aria-hidden="true"></span>
                            &nbsp;<?php echo _ctheme_esc($page['name']) ?>
                        </a>
                    </li>
                <?php } ?>
                <?php if (count($more_nav) > 0) { ?>
                    <li role="separator" class="divider"></li>
                    <li class="dropdown-header">Developer</li>
                    <?php foreach ($more_nav as $page) { ?>
                        <li>
                            <a href="<?php echo _ctheme_esc(Core::getURL($page['id'])) ?>">
                                <span class="<?php echo _ctheme_page_icon($page) ?>" aria-hidden="true"></span>
                                &nbsp;<?php echo _ctheme_esc($page['name']) ?>
                            </a>
                        </li>
                    <?php } ?>
                <?php } ?>
            </ul>
        </div>
        <?php } ?>

        <?php if (count($more_nav) > 0) { ?>
        <div class="dropdown _ctheme_more_menu">
            <a class="_ctheme_icon_btn dropdown-toggle <?php echo in_array($current_page_id, array_column($more_nav, 'id'), true) ? 'active' : '' ?>"
               href="#" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"
               title="Developer tools" aria-label="Developer tools">
                <span class="fa fa-ellipsis-h" aria-hidden="true"></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-right">
                <li class="dropdown-header">Developer</li>
                <?php foreach ($more_nav as $page) { ?>
                    <li class="<?php echo _ctheme_nav_link_class($page, $current_page_id) ?>">
                        <a href="<?php echo _ctheme_esc(Core::getURL($page['id'])) ?>">
                            <span class="<?php echo _ctheme_page_icon($page) ?>" aria-hidden="true"></span>
                            &nbsp;<?php echo _ctheme_esc($page['name']) ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        </div>
        <?php } ?>

        <?php if ($show_settings) { ?>
            <a class="_ctheme_icon_btn <?php echo ($current_page_id === 'settings') ? 'active' : '' ?>"
               href="<?php echo _ctheme_esc(Core::getURL('settings')) ?>"
               data-toggle="tooltip" data-placement="bottom" title="Dashboard Settings" aria-label="Dashboard Settings">
                <span class="fa fa-cog" aria-hidden="true"></span>
            </a>
        <?php } ?>

        <?php
        if (Core::isUserLoggedIn()) {
            $user = Core::getUserLogged();
            $picture_url = isset($user['picture']) ? $user['picture'] : '';
            if (!is_string($picture_url)) {
                $picture_url = '';
            }
            if (preg_match('#^https?://#i', $picture_url) !== 1) {
                $picture_url = sanitize_url(sprintf('%s%s', Configuration::$BASE, $picture_url));
            }
            $user_name = isset($user['name']) ? $user['name'] : '';
            ?>
            <div class="dropdown">
                <a class="_ctheme_user_toggle dropdown-toggle" href="#" data-toggle="dropdown" role="button"
                   aria-haspopup="true" aria-expanded="false" title="<?php echo _ctheme_esc($user_name) ?>">
                    <img src="<?php echo _ctheme_esc($picture_url) ?>" alt="">
                    <span class="_ctheme_user_name"><?php echo _ctheme_esc($user_name) ?></span>
                    <span class="caret"></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li class="dropdown-header"><?php echo _ctheme_esc($user_name) ?></li>
                    <li>
                        <a href="https://hub.duckietown.com/" target="_blank" rel="noopener noreferrer">
                            <span class="fa fa-external-link" aria-hidden="true"></span>
                            &nbsp;Duckietown Hub
                        </a>
                    </li>
                    <li>
                        <a href="#" onclick="logOutButtonClick(); return false;">
                            <span class="fa fa-sign-out" aria-hidden="true"></span>
                            &nbsp;Sign out
                        </a>
                    </li>
                    <li role="separator" class="divider"></li>
                    <li class="_ctheme_dropdown_foot">
                        &copy; <?php echo date('Y') ?> <?php echo _ctheme_esc(Core::getSiteName()) ?>
                    </li>
                </ul>
            </div>
            <?php
        } else if ($login_enabled) {
            ?>
            <a class="_ctheme_nav_link" href="<?php echo _ctheme_esc(Core::getURL('login')) ?>" title="Sign in">
                <span class="fa fa-sign-in" aria-hidden="true"></span>
                Sign in
            </a>
            <?php
        }
        ?>
    </div>
</div>

<div class="_ctheme_progress_bar">
    <?php
    include join_path($CORE_PKG_DIR, 'modules/progress_bar.php');
    ?>
</div>
