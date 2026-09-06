<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele

use system\classes\Core;

include_once __DIR__.'/utils/enum_fillers.php';


function settings_custom_package_tab( $args, $settings_tab_id ){
    $package_name = $args[0];
    $package_settings_res = $args[1];
    ?>
    <h5 style="font-weight:bold">
        <?php
        if( !$package_settings_res['success'] ){
            ?>
            <div class="alert alert-danger" role="alert">
                <span style="color:red">ERROR!</span>&nbsp;
                <?php echo $package_settings_res['data'] ?>
            </div>
            <?php
        }
        $package_settings = ($package_settings_res['success'])? $package_settings_res['data'] : null;
        if (is_null($package_settings)) {
            echo sprintf("Error: %s", $package_settings_res['data']);
            return;
        }
        //
        if (!$package_settings->is_writable()) {
            ?>
            <div class="alert alert-warning" role="alert">
                <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
                <span>WARNING!</span>&nbsp;
                The server does not have the rights to edit the configuration file.
                Any change will be lost.
            </div>
            <?php
        }
        ?>
    </h5>

    <?php
    $config_schema = $package_settings->getSchema();
    $config_values = $package_settings->asArray(true);
    $developer_mode = booleanval(Core::getSetting('developer_mode', 'core', false));
    $hide_advanced_core = ($package_name === 'core' && !$developer_mode);

    // fill in enums
    $w = function ($path, &$_, &$schema) use (&$config_schema) {
        $fcn_path = sprintf('.%s.%s', '__form__', 'enum_filler_fcn');
        $args_path = sprintf('.%s.%s', '__form__', 'enum_filler_args');
        if (!is_null($schema) && $schema->has($fcn_path)) {
            $fcn = $schema->get($fcn_path);
            $args = $schema->get($args_path);
            $entries = $fcn($args);
            if (!is_array($entries)) return;
            $values = [];
            $labels = [];
            array_map(function ($entry) use (&$values, &$labels) {
                array_push($values, $entry['value']);
                array_push($labels, $entry['label']);
            }, $entries);
            $values_path = sprintf('%s.%s', $path, 'values');
            $labels_path = sprintf('%s.%s.%s', $path, '__form__', 'labels');
            $config_schema->set($values_path, $values, true);
            $config_schema->set($labels_path, $labels, true);
        }
    };
    $config_schema->walk($w, $config_values);

    // Pass a plain array into SmartForm. Operators keep Maintenance mode and
    // the Developer mode toggle; everything under Developer mode is omitted
    // from the schema so ComposeForm never renders it. configuration/set only
    // writes posted keys, so Save does not wipe website name / logos / etc.
    $schema_for_form = $config_schema->asArray();
    if ($hide_advanced_core && isset($schema_for_form['_data']) && is_array($schema_for_form['_data'])) {
        $operator_keys = ['maintenance_mode' => true, 'developer_mode' => true];
        $schema_for_form['_data'] = array_intersect_key($schema_for_form['_data'], $operator_keys);
        if (is_array($config_values)) {
            $config_values = array_intersect_key($config_values, $operator_keys);
        }
    }

    $form = new SmartForm($schema_for_form, $config_values);
    $form->render();
    ?>
    <div class="dt-form-actions">
    <button type="button" class="robot-btn robot-btn-primary" id="<?php echo $package_name ?>-settings-save-button">
        <i class="fa fa-check" aria-hidden="true"></i>
        Save and Apply
    </button>
    </div>
    
    <script type="text/javascript">
    	$('#<?php echo $package_name ?>-settings-save-button').on('click', function(){
    	    let form = ComposeForm.get("<?php echo $form->formID ?>");
    	    // hide changes mark on success
    	    let unsaved_mark_id = "#<?php echo $settings_tab_id ?>_unsaved_changes_mark";
    	    let succ_fcn = function(r){
                $(unsaved_mark_id).css('display', 'none');
            };
    	    // call API
    	    smartAPI('configuration', 'set', {
    	        method: 'POST',
                arguments: {
    	            package: "<?php echo $package_name ?>"
                },
                data: {
    	            configuration: form.serialize()
                },
                block: true,
                confirm: true,
                reload: true,
                on_success: succ_fcn
            });
    	});

        $(document).ready(function(){
            $('#<?php echo $form->formID ?> input').change(function(){
                let unsaved_mark_id = "#<?php echo $settings_tab_id ?>_unsaved_changes_mark";
                $(unsaved_mark_id).css('display', '');
            });
            <?php if ($hide_advanced_core) { ?>
            (function hideRowsUnderDeveloperMode(){
                var root = document.getElementById("<?php echo $form->formID ?>");
                if (!root) return;
                root.setAttribute('data-operator-dashboard', '1');
                var hide = false;
                $(root).find('.compose-form-atom').each(function(){
                    var title = $(this).find('.input-group-addon.text-bold').first().text().trim().toLowerCase();
                    if (hide) {
                        this.style.setProperty('display', 'none', 'important');
                    }
                    if (title === 'developer mode') {
                        hide = true;
                    }
                });
            })();
            <?php } ?>
        });
    </script>

<?php
}
?>
