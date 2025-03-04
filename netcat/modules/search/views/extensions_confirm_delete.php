<?php

if (!class_exists("nc_system")) { die; }

$ui = $this->get_ui();
$ui->add_settings_toolbar();
$ui->add_submit_button(NETCAT_MODULE_SEARCH_ADMIN_EXTENSIONS_CONFIRM_DELETE_OK);
$ui->add_back_button();

$ids = $this->get_input('ids');
if (!$ids) {
    $this->redirect("?view=extensions");
}

print NETCAT_MODULE_SEARCH_ADMIN_EXTENSIONS_CONFIRM_DELETE_WARNING;
print "<form action='?action=delete&amp;data_class=nc_search_extension_rule&amp;view=extensions' method='post'>";
print $nc_core->token->get_input();
foreach ($ids as $id) {
    print "<input type='hidden' name='ids[]' value='$id' />";
}
print "</form>";