<?php
class nc_mixin_file_controller extends nc_ui_controller {

    /**
     *
     */
    protected function action_save() {
        $nc_core = nc_core::get_object();

        $scope = $nc_core->input->fetch_get_post('scope');
        $infoblock_id = (int)$nc_core->input->fetch_get_post('infoblock_id');
        $mixin_keyword = $nc_core->input->fetch_get_post('mixin_keyword');

        if (preg_match('/^\w+$/', $scope) && preg_match('/^\w+$/', $mixin_keyword)) {
            // путь к загружаемому файлу
            $mixin_file_path = 'mixin/' . $scope . '/' . $infoblock_id . '/' . $mixin_keyword . '/';
            $upload_dir = $nc_core->FILES_FOLDER . $mixin_file_path;
            $uploaded_file = $nc_core->input->fetch_files('file');

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, $nc_core->DIRCHMOD, true);
            }

            $mixin_file_name = nc_get_filename_for_original_fs(basename($uploaded_file['name']), $upload_dir);
            $mixin_file = $upload_dir . $mixin_file_name;

            if (move_uploaded_file($uploaded_file['tmp_name'], $mixin_file)) {
                $result = array(
                    'result' => 'ok',
                    'url' => $nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH . $mixin_file_path . $mixin_file_name,
                );
                if (strtolower(pathinfo($mixin_file_name, PATHINFO_EXTENSION)) === 'svg') {
                    nc_sanitize_svg_file($mixin_file);
                }
            } else {
                $result = array(
                    'result' => 'error',
                );
            }
        } else {
            $result = array(
                'result' => 'error',
            );
        }
        echo nc_array_json($result);
        exit;
    }
}