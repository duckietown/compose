<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele

use \system\classes\Core;
use \system\classes\Configuration;

// get pages
$pages_list = Core::getPagesList();

// get the current user's role
$main_user_role = Core::getUserRole();
$user_roles = Core::getUserRolesList();

// get list of visible buttons
$pages = Core::getFilteredPagesList(
    'by-menuorder',
    true /* enabledOnly */,
    $user_roles /* accessibleBy */
);

// create a whitelist/blacklist of pages
$pages_whitelist = null;
$pages_blacklist = null;

// check if compose was configured
if (!Core::isComposeConfigured()) {
    $pages_whitelist = ['setup'];
} else {
    // file-manager stays routable for a future Robot tab, but is not a
    // primary sidebar entry in the robot-first navigation hierarchy
    $pages_blacklist = ['setup', 'file-manager'];
}

// remove login if the functionality is not enabled
$login_enabled = Core::getSetting('login_enabled', 'core');
$developer_mode = (bool) Core::getSetting('developer_mode', 'core', false);
// Pages hidden for normal users; visible only in developer mode
$developer_only_pages = ['package_store', 'users', 'profile'];
?>

<style type="text/css">
    /* Compact professional sidebar */
    ._ctheme_page ._ctheme_side_bar {
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        padding: 0 8px;
        font-size: 14px;
        overflow-x: hidden;
        box-shadow: 1px 0 0 #e0e0e0;
        width: <?php echo Configuration::$THEME_CONFIG['dimensions']['sidebar_full_width'] ?>px;
        <?php
        echo _get_gradient_color($_THEME_COLOR_2->darken(0.05), $_THEME_COLOR_2, 143)
        ?>
    }
    
    ._ctheme_page ._ctheme_side_bar a,
    ._ctheme_page ._ctheme_side_bar .btn {
        color: <?php echo $_THEME_FG_COLOR_2->get_hex() ?>;
    }
    
    ._ctheme_page ._ctheme_side_bar hr {
        border-color: rgba(0,0,0,0.12);
    }
    
    ._ctheme_page ._ctheme_side_bar hr._ctheme_logo_hr {
        position: absolute;
        top: 52px;
        left: 8%;
        right: 8%;
        margin: 0;
        border-top-color: rgba(0,0,0,0.12);
    }
    
    ._ctheme_page ._ctheme_side_bar ._ctheme_logo_div {
        padding: 6px 4px;
        height: 52px;
        box-sizing: border-box;
        display: flex;
        align-items: center;
    }
    
    ._ctheme_page ._ctheme_side_bar ._ctheme_logo_div,
    ._ctheme_page ._ctheme_side_bar ._ctheme_logo_div table{
        float: left;
        width: 100%;
    }
    
    ._ctheme_page ._ctheme_side_bar ._ctheme_logo_div table td:first-child{
        width: 1%;
    }
    
    ._ctheme_page ._ctheme_side_bar ._ctheme_logo_div table td:last-child{
        width: 99%;
        text-align: left;
        padding-left: 8px;
        padding-right: 4px;
    }
    
    ._ctheme_page ._ctheme_side_bar ._ctheme_logo_div table td:last-child h3{
        margin: 0;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: -0.01em;
        line-height: 1.2;
    }
    
    ._ctheme_page ._ctheme_side_bar ._ctheme_logo_div table td:last-child h6{
        margin: 0;
        font-size: 10px;
        opacity: 0.7;
        font-weight: 400;
    }

    ._ctheme_page ._ctheme_side_bar #navbarLogo {
        max-height: 28px !important;
        width: auto;
    }
    
    ._ctheme_side_bar_buttons_group_container {
        margin: 6px 0;
        padding: 0 4px 0 0;
        position: absolute;
        top: 52px;
        bottom: 96px;
        left: 0;
        right: 0;
    }
    
    ._ctheme_side_bar_buttons_group {
        height: 100%;
        overflow: auto;
    }
    
    ._ctheme_side_bar_buttons_group .btn {
        margin: 2px 0;
        width: 100%;
        height: 36px;
        font-size: 12px;
        text-transform: none;
        font-family: inherit;
        font-weight: 500;
        letter-spacing: 0;
        border-radius: 6px;
        padding: 8px 6px;
        text-align: left;
    }
    
    ._ctheme_side_bar_buttons_group .btn:hover {
        text-decoration: none;
        background-color: <?php echo $_THEME_COLOR_2->darken(0.12)->get_hex() ?>;
    }
    
    ._ctheme_side_bar_buttons_group .btn.active {
        background-color: #ffffff;
        color: #1e1e1e;
        box-shadow: 0 0 0 1px rgba(0,0,0,0.06);
    }
    
    ._ctheme_side_bar_buttons_group .btn span:first-child {
        font-size: 13px;
        width: 28px;
        text-align: center;
    }
    
    ._ctheme_page ._ctheme_side_bar hr._ctheme_footer_hr {
        position: absolute;
        bottom: 96px;
        left: 8%;
        right: 8%;
        margin: 0;
        border-top-color: rgba(0,0,0,0.12);
    }
    
    ._ctheme_footer {
        position: absolute;
        bottom: 8px;
        left: 0;
        right: 0;
        margin: 0;
        font-size: 11px;
        width: 100%;
    }
    
    ._ctheme_footer #_sidebar_user_btn {
        margin: 0 0 0 8px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        gap: 0;
    }
    
    ._ctheme_footer #_sidebar_user_btn:hover {
        text-decoration: none;
        opacity: 0.85;
    }
    
    ._ctheme_footer #_sidebar_user_btn img {
        border-radius: 50%;
        width: 28px;
        height: 28px;
        margin-top: 0;
        border: 1px solid rgba(0,0,0,0.2);
    }
    
    ._ctheme_footer #_sidebar_user_btn span {
        font-size: 12px;
        font-weight: 600;
        padding-left: 8px;
    }

    ._ctheme_footer ._ctheme_signout_link {
        display: block;
        text-align: left;
        padding: 2px 8px 0 44px;
        font-size: 11px;
        font-weight: 500;
        opacity: 0.8;
    }
    
    ._ctheme_page ._ctheme_side_bar hr._ctheme_footer_credits_hr {
        margin: 8px 8% 0 8%;
        border-top-color: rgba(0,0,0,0.12);
    }
    
    ._ctheme_footer ._ctheme_footer_credits {
        height: auto;
        color: <?php echo Configuration::$THEME_CONFIG['colors']['secondary']['foreground'] ?>;
    }
    
    ._ctheme_footer ._ctheme_footer_credits td {
        width: 100%;
        text-align: left;
        padding: 6px 12px 0 12px;
    }
    
    ._ctheme_footer ._ctheme_footer_credits td img {
        height: 14px;
    }
    
    ._ctheme_footer ._ctheme_footer_credits td ._ctheme_footer_credit_row {
        line-height: 14px;
        font-size: 10px;
        opacity: 0.65;
        font-weight: 400;
    }
</style>

<a class="_ctheme_logo_div" href="<?php echo Configuration::$BASE ?>">
    <table>
        <tr class="_ctheme_side_bar_off">
            <td>
                <?php
                $logo = Core::getSetting('logo_white');
                $logo = str_replace('~', Configuration::$BASE, str_replace('~/', '~', $logo));
                ?>
                <img id="navbarLogo" src="<?php echo $logo ?>" alt=""/>
            </td>
            <td>
                <h3><?php echo Core::getSetting('navbar_title') ?></h3>
                <?php
                $subtitle = Core::getSetting('navbar_subtitle');
                if (!is_null($subtitle) && strlen(trim($subtitle)) > 0) {
                    ?>
                    <h6><?php echo $subtitle ?></h6>
                    <?php
                }
                ?>
            </td>
        </tr>
        <tr class="_ctheme_side_bar_on">
            <td>
                <?php
                $logo = Core::getSetting('logo_white_small');
                $logo = str_replace('~', Configuration::$BASE, str_replace('~/', '~', $logo));
                ?>
                <img id="navbarLogo" src="<?php echo $logo ?>" alt="" style="max-height: 44px !important;"/>
            </td>
        </tr>
    </table>
</a>

<hr class="_ctheme_logo_hr">


<div class="_ctheme_side_bar_buttons_group_container col-md-12">
    <div class="_ctheme_side_bar_buttons_group col-md-12">
    <?php
    $active_page_btn_id = '';
    foreach ($pages as &$page) {
        if (!$login_enabled && $page['id'] == 'login') {
            continue;
        }
        if (!is_null($pages_whitelist) && !in_array($page['id'], $pages_whitelist)) {
            continue;
        }
        if (!is_null($pages_blacklist) && in_array($page['id'], $pages_blacklist)) {
            continue;
        }
        // hide pages if maintenance mode is enabled
        if ($main_user_role != 'administrator' && Core::getSetting('maintenance_mode', 'core') && $page['id'] != 'login') {
            continue;
        }
        // hide developer-only pages unless developer mode is on
        if (!$developer_mode && in_array($page['id'], $developer_only_pages)) {
            continue;
        }
        // hide page if the current user' role is excluded
        if (count(array_intersect($user_roles, $page['menu_entry']['exclude_roles'])) > 0) {
            continue;
        }
        $icon = sprintf('%s %s-%s', $page['menu_entry']['icon']['class'], $page['menu_entry']['icon']['class'], $page['menu_entry']['icon']['name']);
        $active = (Configuration::$PAGE == $page['id']) || in_array(Configuration::$PAGE, $page['child_pages']);
        $active_page_btn_id = $active? sprintf("_sidebar_page_btn_%s", $page['id']) : '';
        //
        ?>
        <a  role="button" id="_sidebar_page_btn_<?php echo $page['id'] ?>"
            class="btn btn-link _sidebar_page_btn <?php echo ($active) ? 'active' : '' ?>"
            href="<?php echo Core::getURL($page['id']) ?>"
            >
            <span class="<?php echo $icon ?>" aria-hidden="true" style=""></span>
            <span class="_ctheme_side_bar_off">
                <?php echo $page['name'] ?>
            </span>
        </a>
        <?php
    }
    ?>
    </div>
</div>

<hr class="_ctheme_footer_hr">

<table class="_ctheme_footer">
    <?php
    if (Core::isUserLoggedIn()) {
        ?>
        <tr>
            <td>
                <a  role="button" id="_sidebar_user_btn"
                    class="btn btn-link"
                    href="https://hub.duckietown.com/"
                    target="_blank"
                    title="Open Duckietown Hub"
                    >
                    <?php
                    // get user info
                    $user = Core::getUserLogged();
                    $picture_url = $user['picture'];
                    if (preg_match('#^https?://#i', $picture_url) !== 1) {
                      $picture_url = sanitize_url(sprintf(
                        "%s%s", Configuration::$BASE, $picture_url
                      ));
                    }
                    ?>
                    <img src="<?php echo $picture_url; ?>" alt="">
                    <span class="_ctheme_side_bar_off">
                        <?php echo $user['name'] ?>
                    </span>
                </a>
                <a role="button" class="btn btn-link _ctheme_side_bar_off _ctheme_signout_link"
                   href="#" onclick="logOutButtonClick();">
                    <span class="glyphicon glyphicon-log-out" aria-hidden="true"></span>
                    &nbsp;Sign out
                </a>
                <hr class="_ctheme_footer_credits_hr">
            </td>
        </tr>
    <?php
    } else if ($login_enabled) {
        ?>
        <tr>
            <td>
                <a  role="button" id="_sidebar_user_btn"
                    class="btn btn-link"
                    href="<?php echo Core::getURL('login') ?>"
                    title="Sign in"
                    >
                    <span class="glyphicon glyphicon-log-in" aria-hidden="true" style="margin: 8px;"></span>
                    <span class="_ctheme_side_bar_off">
                        Sign in
                    </span>
                </a>
                <hr class="_ctheme_footer_credits_hr">
            </td>
        </tr>
    <?php
    }
    ?>
    
    <tr class="_ctheme_footer_credits">
        <td class="_ctheme_side_bar_off">
            <?php
            // "powered by compose", serial/git hash, and burn-cache controls removed for
            // a cleaner robot-focused dashboard. Keep copyright only.
            ?>
            <span class="_ctheme_footer_credit_row">
                &copy; <?php echo date("Y"); ?> <?php echo Core::getSiteName() ?>
            </span>
            <?php
            /*
            $hide_credits = Core::getSetting("hide_credits");
            if ($hide_credits !== true) {
                ?>
                <span class="_ctheme_footer_credit_row">
                    powered by
                    <a href="https://github.com/afdaniele/compose" target="_blank">
                        <img src="<?php echo Configuration::$BASE ?>images/compose-black-logo.svg" alt=""/>
                    </a>
                </span>
                <br/>
                <?php
            }
            // codebase info
            $codebase_info = Core::getCodebaseInfo();
            $codebase_hash = $codebase_info['head_hash'];
            $codebase_tag = $codebase_info['head_tag'];
            $codebase_latest_tag = $codebase_info['latest_tag'];
            $codebase_tag = (strcasecmp($codebase_tag, $codebase_latest_tag) === 0) ? $codebase_tag : 'devel';
            $codebase_str = (in_array($codebase_tag, ['ND', null])) ? '' : sprintf("%s | ", $codebase_tag);
            ?>
            
            <span class="_ctheme_footer_credit_row">
                <b>serial</b>&nbsp;
                <span style="font-family:monospace">
                    git | <?php echo $codebase_str . $codebase_hash ?>
                </span>
            </span>
    
            <?php
            if (Core::getSetting('cache_enabled')) {
                ?>
                |&nbsp; <span
                        onclick="clearCache()"
                        class="glyphicon glyphicon-fire focus-on-hover pointer-hand"
                        aria-hidden="true"
                        data-toggle="tooltip"
                        data-placement="top"
                        title="Burn cache"
                ></span>
                <?php
            }
            */
            ?>
        </td>
    </tr>
</table>

<script type="text/javascript">
    $(document).ready(function(){
        let sidebar_btn = $('._sidebar_page_btn.active')[0];
        if (sidebar_btn != undefined)
            sidebar_btn.scrollIntoView();
        // localStorage.getItem returns null when unset — treat as expanded
        let saved = localStorage.getItem('_CTHEME_SIDEBAR_STATUS');
        _ctheme_side_bar_set(saved === 'small' ? 'small' : 'full');
    });

    function _ctheme_side_bar_toggle(evt){
        if (evt && evt.preventDefault) evt.preventDefault();
        let page = $('._ctheme_page');
        let status = page.hasClass('is-sidebar-collapsed') ? 'full' : 'small';
        _ctheme_side_bar_set(status);
        localStorage.setItem('_CTHEME_SIDEBAR_STATUS', status);
        return false;
    }

    function _ctheme_side_bar_set(status){
        if (status !== 'small' && status !== 'full') {
            status = 'full';
        }
        let page = $('._ctheme_page');
        let button = $('._ctheme_side_bar_btn a');
        let collapsed = (status === 'small');
        page.toggleClass('is-sidebar-collapsed', collapsed);
        $('._sidebar_page_btn').css('text-align', collapsed ? 'center' : 'left');
        button
            .removeClass('glyphicon-chevron-left glyphicon-chevron-right')
            .addClass(collapsed ? 'glyphicon glyphicon-chevron-right' : 'glyphicon glyphicon-chevron-left')
            .attr('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar')
            .attr('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    }
</script>

