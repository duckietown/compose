<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu

// simplify namespaces
use system\classes\Core;
use system\classes\Configuration;

include_once join_path(__DIR__, 'constants.php');

$CORE_PKG_DIR = $GLOBALS['__CORE__PACKAGE__DIR__'];
$page_class = 'page-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) Configuration::$PAGE);
?>


<style type="text/css">
    body {
        margin-bottom: 0;
        background: #ececec;
        background-image: none;
    }
    
    ._ctheme_body {
        height: 100vh;
        padding: <?php echo Configuration::$THEME_CONFIG['dimensions']['page_padding'] ?>px;
    }
    
    ._ctheme_page {
        height: 100%;
        border-radius: <?php echo Configuration::$THEME_CONFIG['dimensions']['page_radius'] ?>px;
        border: 1px solid #d0d0d0;
        background-color: white;
        overflow: hidden;
        box-shadow: none;
    }
    
    /* Layout widths must NOT use !important — collapse toggle sets these via JS/classes */
    ._ctheme_side_bar {
        width: 240px;
        transition: width 160ms ease;
    }

    ._ctheme_container {
        position: absolute;
        top: 52px;
        bottom: 0;
        left: 240px;
        right: 0;
        border-left: 1px solid #e0e0e0;
        transition: left 160ms ease;
    }

    ._ctheme_top_bar {
        left: 240px;
        height: 52px;
        transition: left 160ms ease;
    }

    /* Collapsed sidebar (toggled by ._ctheme_side_bar_toggle) */
    ._ctheme_page.is-sidebar-collapsed ._ctheme_side_bar {
        width: 72px;
    }
    ._ctheme_page.is-sidebar-collapsed ._ctheme_container,
    ._ctheme_page.is-sidebar-collapsed ._ctheme_top_bar {
        left: 72px;
    }
    ._ctheme_page.is-sidebar-collapsed ._ctheme_side_bar_off {
        display: none !important;
    }
    ._ctheme_page.is-sidebar-collapsed ._ctheme_side_bar_on {
        display: table-row !important;
    }
    ._ctheme_page:not(.is-sidebar-collapsed) ._ctheme_side_bar_on {
        display: none !important;
    }
    
    ._ctheme_content {
        position: absolute;
        overflow: auto;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        border-left: none;
        padding: 12px 16px 16px;
    }
    
    .page-title {
        display: none;
    }
    
    #page_container {
        margin-top: 0;
        width: 100%;
        max-width: none;
        padding-left: 8px;
        padding-right: 8px;
    }

    /* elFinder / Portainer need a flush full-bleed content pane */
    ._ctheme_page.page-file-manager ._ctheme_content,
    ._ctheme_page.page-portainer ._ctheme_content {
        padding: 0 !important;
        overflow: hidden !important;
    }
    ._ctheme_page.page-file-manager #page_container,
    ._ctheme_page.page-portainer #page_container {
        position: absolute;
        inset: 0;
        width: 100% !important;
        max-width: none !important;
        height: 100%;
        margin: 0 !important;
        padding: 0 !important;
    }
    ._ctheme_page.page-file-manager #page_canvas,
    ._ctheme_page.page-portainer #page_canvas {
        position: absolute;
        inset: 0;
        height: 100%;
    }
    
    /* width */
    ::-webkit-scrollbar {
      width: 8px;
    }
    
    /* Track */
    ::-webkit-scrollbar-track {
      background: #f0f0f0;
      border-radius: 0;
      box-shadow: none;
    }
    
    /* Handle */
    ::-webkit-scrollbar-thumb {
      background: <?php echo $_THEME_COLOR_3->get_hex() ?>;
      border-radius: 4px;
    }
    
</style>


<div class="_ctheme_body col-md-12">
    <div class="_ctheme_page col-md-12 <?php echo htmlspecialchars($page_class) ?>">
        
        <div class="_ctheme_side_bar">
            <?php
            // load top bar
            include join_path(__DIR__, 'components/side_bar.php')
            ?>
        </div>
        
        <div class="_ctheme_top_bar">
            <?php
            // load top bar
            include join_path(__DIR__, 'components/top_bar.php')
            ?>
        </div>
        
        <div class="_ctheme_container">
            
            <?php
            // Developer mode watermark
            if (Core::getSetting('developer_mode')) {
                include(join_path($CORE_PKG_DIR, 'modules/devel_watermark.php'));
            }
            ?>
            
            <div class="_ctheme_content">

                <!-- Begin page content -->
                <div id="page_container" class="container">
            
                    <?php include(join_path($CORE_PKG_DIR, 'modules/alerts.php')); ?>

                    <!-- Main Container -->
                    <div id="page_canvas">
                        <?php
                        include(join_path(Core::getPageDetails(Configuration::$PAGE, 'path'), "index.php"));
                        ?>
                    </div>
                    <!-- Main Container End -->
    
                </div>
                
            </div>


        </div>
        
    </div>
</div>


