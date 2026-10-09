<?php
# @Author: Andrea F. Daniele <afdaniele>
# @Email:  afdaniele@ttic.edu
# @Last modified by:   afdaniele

use \system\classes\Core;
use \system\classes\Configuration;

$modes = [
    "/" => [
        "main" => "users",
        "section" => "users"
    ],
    "groups/" => [
        "main" => "groups",
        "section" => "groups"
    ],
    "groups/user" => [
        "main" => "users",
        "section" => "groups"
    ],
    "groups/link" => [
        "main" => "groups",
        "section" => "users"
    ],
    "groups/members" => [
        "main" => "groups",
        "section" => "users"
    ]
];
$section_sel = sprintf('%s/%s', Configuration::$ACTION, Configuration::$ARG1);

// redirect to /users if the given arguments do not identify a mode
if (!array_key_exists($section_sel, $modes)) {
    Core::redirectTo('users');
    return;
}

// get main and section file to load
$main = $modes[$section_sel]['main'];
$section = $modes[$section_sel]['section'];

// create title
$mains = [
    'users' => ['position' => 'left', 'url' => Core::getURL('users')],
    '/' => ['position' => 'center', 'url' => ''],
    'groups' => ['position' => 'right', 'url' => Core::getURL('users', 'groups')]
];
?>
<div class="dt-page">
<h2 class="page-title-static">
    <?php
    foreach ($mains as $mkey => $mdata) {
        $is_current = ($main == $mkey || $mdata['position'] === 'center');
        $type = $is_current ? 'span' : 'a';
        $class = trim(
            ($main == $mkey ? 'text-bold ' : '') .
            'page-title-side page-title-' . $mdata['position']
        );
        $label = htmlspecialchars(ucfirst($mkey), ENT_QUOTES, 'UTF-8');
        $class_attr = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        if ($type === 'a') {
            printf(
                '<a href="%s" class="%s">%s</a>',
                htmlspecialchars($mdata['url'], ENT_QUOTES, 'UTF-8'),
                $class_attr,
                $label
            );
        } else {
            printf('<span class="%s">%s</span>', $class_attr, $label);
        }
    }
    ?>
</h2>


<?php
include_once sprintf('%s/sections/%s.php', __DIR__, $section);
?>
</div>