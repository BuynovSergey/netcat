<?php

class nc_image_path_a2f extends nc_image_path {
    protected function can_user_edit_image() {
        return false;
    }

    protected function get_editable_image_form($file_path) {
        return '';
    }
}