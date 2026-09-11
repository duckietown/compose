<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu

// simplify namespaces
use system\classes\Core;
use system\classes\Configuration;

include_once join_path(__DIR__, 'constants.php');

$CORE_PKG_DIR = $GLOBALS['__CORE__PACKAGE__DIR__'];
$page_class = 'page-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) Configuration::$PAGE);
$is_embed = isset($_GET['embed']) && $_GET['embed'] !== '' && $_GET['embed'] !== '0';
if ($is_embed) {
    $page_class .= ' is-embed';
}

$ds_css_path = join_path(__DIR__, 'components/design_system.css');
$ds_js_path = join_path(__DIR__, 'components/design_system.js');
$ds_css_url = Configuration::$BASE . 'system/packages/core/modules/theme/modern/components/design_system.css';
$ds_js_url = Configuration::$BASE . 'system/packages/core/modules/theme/modern/components/design_system.js';
if (is_file($ds_css_path)) {
    $ds_css_url .= '?v=' . filemtime($ds_css_path);
}
if (is_file($ds_js_path)) {
    $ds_js_url .= '?v=' . filemtime($ds_js_path);
}
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($ds_css_url, ENT_QUOTES, 'UTF-8') ?>">
<script src="<?php echo htmlspecialchars($ds_js_url, ENT_QUOTES, 'UTF-8') ?>"></script>

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

    ._ctheme_container {
        position: absolute;
        top: 52px;
        bottom: 0;
        left: 0;
        right: 0;
        border-left: none;
    }

    ._ctheme_top_bar {
        left: 0;
        height: 52px;
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
        width: 100% !important;
        max-width: none !important;
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

    /* Robot-tab embeds: no top bar / page chrome */
    ._ctheme_body.is-embed {
        padding: 0;
        height: 100vh;
        background: #fff;
    }
    ._ctheme_page.is-embed {
        border: 0;
        border-radius: 0;
        box-shadow: none;
    }
    ._ctheme_page.is-embed ._ctheme_top_bar {
        display: none !important;
    }
    ._ctheme_page.is-embed ._ctheme_container {
        left: 0;
        top: 0;
        border-left: 0;
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


<div class="_ctheme_body col-md-12<?php echo $is_embed ? ' is-embed' : '' ?>">
    <div class="_ctheme_page col-md-12 <?php echo htmlspecialchars($page_class) ?>">
        
        <div class="_ctheme_top_bar<?php echo Core::getSetting('developer_mode') ? ' is-developer-mode' : '' ?>">
            <?php
            include join_path(__DIR__, 'components/top_bar.php')
            ?>
        </div>
        
        <div class="_ctheme_container">
            
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
