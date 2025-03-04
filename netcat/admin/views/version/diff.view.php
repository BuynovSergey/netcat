<?php
if (!class_exists('nc_core')) {
    die;
}

if (!$version instanceof nc_version_record) {
    die;
}

$changes = $version->get_changes();

if ($changes) {
    foreach ($changes as $field => $data) {
        $diff = new \Caxy\HtmlDiff\HtmlDiff($data['old'], $data['new']);
        echo "<div class='nc-version-field-name'><b>$field</b></div>";
        echo "<div class='nc-version-field-diff'>" . $diff->build() . "</div>";
    }
} else {
    echo NETCAT_VERSION_TABLE_NO_CHANGES;
}