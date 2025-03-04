<?php

/**
 * Класс для реализации поля типа "Файл - Иконка/Изображение"
 */
class nc_a2f_field_file_icon extends nc_a2f_field_file {

    protected function get_additional_html_after_file_field() {
        $ret = '';
        $dialog_url = nc_controller_url('admin.image', 'index');

        if (!empty($this->value['resultpath'])) {
            $file_absolute_path = nc_core::get_object()->DOCUMENT_ROOT . $this->value['resultpath'];
            $provider = new nc_image_provider_icon();
            $icon_info = $provider->parse_icon_info($file_absolute_path);
            if (!empty($icon_info['library'])) {
                // $icon_info — массив с ключами library, icon, color
                $dialog_url = nc_controller_url('admin.image', 'index', $icon_info);
            }
        }

        $upload_file_js = "\$nc(this).closest('.nc-upload').find('.nc-upload-input').click();return false;";
        $load_dialog_js =
            "nc.load_dialog(this.href).set_option('image_dialog_input'," .
            "\$nc(this).parents('.nc-upload').find('.nc-upload-file-server-path').eq(0)" .
            ");return false;";

        $ret .=
            "<input class='nc-upload-file-server-path' type='hidden' name='" . $this->get_field_name('file') . "' value='' />" .
            "<div class='nc-upload-hide-if-full'>" .
            NETCAT_FIELD_FILE_ICON_SELECT . ' ' .
            "<a href='" . htmlspecialchars($dialog_url, ENT_QUOTES) . "'" .
            " onclick='" . htmlspecialchars($load_dialog_js, ENT_QUOTES) . "'>" .
            NETCAT_FIELD_FILE_ICON_ICON .
            "</a>".
            ' ' . NETCAT_FIELD_FILE_ICON_OR . ' ' .
            "<a href='#' onclick='" . htmlspecialchars($upload_file_js, ENT_QUOTES) . "'>" .
            NETCAT_FIELD_FILE_ICON_FILE .
            "</a>";

        $ret .= "</div>";

        return $ret;
    }
}
