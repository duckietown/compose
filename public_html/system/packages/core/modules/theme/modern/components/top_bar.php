<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu


// simplify namespaces
use system\classes\Core;
use system\classes\Configuration;

// Tight, professional chrome (overrides bulky stored theme dimensions)
$_TOPBAR_H = 52;
$_SIDEBAR_W = Configuration::$THEME_CONFIG['dimensions']['sidebar_full_width'];
?>

<style type="text/css">
    ._ctheme_page ._ctheme_top_bar {
        position: absolute;
        top: 0;
        left: <?php echo $_SIDEBAR_W ?>px;
        right: 0;
        height: <?php echo $_TOPBAR_H ?>px !important;
        color: <?php echo Configuration::$THEME_CONFIG['colors']['primary']['foreground'] ?>;
        padding: 0 16px 0 28px;
        font-size: 16px;
        font-weight: 600;
        letter-spacing: -0.01em;
        border-left: 1px solid #e0e0e0;
        border-bottom: 1px solid #e0e0e0;
        background: #ffffff;
        box-shadow: none;
        display: flex;
        align-items: center;
        z-index: 20;
    }

    ._ctheme_page ._ctheme_top_bar a {
        color: #444;
    }

    ._ctheme_page a:hover {
        text-decoration: none;
    }
    
    ._ctheme_page ._ctheme_top_bar ._ctheme_side_bar_btn {
        width: 22px;
        height: <?php echo $_TOPBAR_H - 1 ?>px;
        position: absolute;
        top: 0;
        left: 0;
        z-index: 99;
        background-color: #f5f5f5;
        border-right: 1px solid #e0e0e0;
        text-align: center;
        cursor: pointer;
        transition: background-color 160ms ease;
    }

    ._ctheme_page ._ctheme_top_bar ._ctheme_side_bar_btn:hover {
        background-color: #ebebeb;
    }
    
    ._ctheme_page ._ctheme_top_bar ._ctheme_side_bar_btn a {
        color: #666;
        line-height: <?php echo $_TOPBAR_H - 1 ?>px;
        font-size: 11px;
        text-decoration: none;
        display: block;
        width: 100%;
        height: 100%;
    }
    
    ._ctheme_page ._ctheme_top_bar ._ctheme_side_bar_btn a:hover {
        text-decoration: none;
        color: #222;
    }

    ._ctheme_top_bar_inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        gap: 12px;
        min-width: 0;
    }

    ._ctheme_top_bar_title {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-width: 0;
        line-height: 1.15;
    }

    ._ctheme_top_bar_title_main {
        font-size: 15px;
        font-weight: 600;
        color: #1e1e1e;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    ._ctheme_page ._ctheme_top_bar ._ctheme_top_bar_button {
        padding: 0 4px;
        font-size: 14px;
        opacity: 0.75;
    }

    ._ctheme_page ._ctheme_top_bar ._ctheme_top_bar_button:hover {
        opacity: 1;
    }

    ._ctheme_top_bar .breadcrumb {
        background-color: unset;
        font-size: 11px;
        font-weight: 400;
        padding: 0;
        margin: 1px 0 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #888;
    }

    ._ctheme_top_bar .breadcrumb > li + li:before {
        color: #bbb;
        padding: 0 4px;
    }

    ._ctheme_top_bar .breadcrumb > li,
    ._ctheme_top_bar .breadcrumb > li.active,
    ._ctheme_top_bar .breadcrumb > li > a {
        color: #888;
    }
    
    ._ctheme_top_bar ._ctheme_progress_bar {
        position: absolute;
        width: 100%;
        left: 0;
        right: 0;
        bottom: 0;
        margin: 0;
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
</style>

<div class="_ctheme_side_bar_btn">
    <a class="glyphicon glyphicon-chevron-left" href="#" role="button"
       aria-label="Collapse sidebar" title="Collapse sidebar"
       onclick="return _ctheme_side_bar_toggle(event);"></a>
</div>

<div class="_ctheme_top_bar_inner">
    <div class="_ctheme_top_bar_title">
        <div class="_ctheme_top_bar_title_main">
            <?php echo ucfirst(Core::getPageDetails(Configuration::$PAGE, 'name')); ?>
        </div>
        <ol class="breadcrumb">
            <?php
            $parts = [
                '',
                Configuration::$PAGE,
                Configuration::$ACTION,
                Configuration::$ARG1,
                Configuration::$ARG2,
                null
            ];
            $cur = [];
            for ($i = 0; $i < count($parts) - 1; $i++) {
                $part = $parts[$i];
                $npart = $parts[$i + 1];
                if (is_null($part)) {
                    break;
                }
                array_push($cur, $part);
                $active = is_null($npart) ? 'class="active"' : '';
                $url = is_null($npart) ? ucfirst($part) :
                    sprintf('<a href="%s">%s</a>', Core::getURL(...$cur), ucfirst($part));
                printf('<li %s>%s</li>', $active, $url);
            }
            ?>
        </ol>
    </div>
    <div>
        <?php
        if (Core::isUserLoggedIn()) {
            ?>
            <span class="_ctheme_top_bar_button">
                <a href="<?php echo Core::getURL('settings') ?>"
                   data-toggle="tooltip" data-placement="bottom" title="Dashboard Settings">
                    <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
                </a>
            </span>
            <?php
        }
        ?>
    </div>
</div>

<div class="_ctheme_progress_bar">
    <?php
    include(join_path($CORE_PKG_DIR, 'modules/progress_bar.php'));
    ?>
</div>
