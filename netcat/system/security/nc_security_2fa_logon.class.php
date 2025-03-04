<?php

class nc_security_2fa_logon {

    /** @var nc_security_2fa */
    protected $_2fa;

    /**
     * В случае, если необходимо запросить второй фактор (определяется методом need_to_ask_for_code()),
     * останавливает выполнение скрипта, и:
     *
     * а) Если код не введён — отправляет второй фактор пользователю (если выбранный метод 2ФА этого требует)
     *    и выводит форму ввода кода.
     * б) Если через POST передан параметр nc_security_2fa_code — проверяет введённый код, результат
     *    отправляется в JSON.
     * в) Если у пользователя не заполнено поле с e-mail или телефоном и разрешено ввести их — выводит форму
     *    для заполнения поля.
     *
     * Т. о. метод является в некотором смысле мини-контроллером, перехватывающим управление
     * при необходимости ввода второго фактора — после ввода пароля при переходе в режим редактирования или
     * администрирования пользователем, для которого включена 2ФА.
     *
     * @return void
     */
    public static function check(nc_security_2fa $nc_security_2fa) {
        $logon = new self($nc_security_2fa);
        $logon->ask_for_code_if_needed();
    }

    /**
     * @param nc_security_2fa $nc_security_2fa
     */
    public function __construct(nc_security_2fa $nc_security_2fa) {
        $this->_2fa = $nc_security_2fa;
    }

    /**
     * @return void
     */
    protected function ask_for_code_if_needed() {
        if ($this->need_to_ask_for_code()) {
            $nc_core = nc_core::get_object();
            // Нужны языковые файлы
            require_once $nc_core->ADMIN_FOLDER . 'lang/' . $nc_core->lang->detect_lang() . '.php';

            // Предварительная проверка — например, запрос номера телефона при первом входе
            $method_block_screen = $this->_2fa->get_first_logon_page();
            if ($method_block_screen) {
                $this->show_page($method_block_screen);
            }

            // Введённый пользователем код передаётся в $_POST['nc_security_2fa_code']
            $checked_code = $nc_core->input->fetch_post('nc_security_2fa_code');

            if ($this->_2fa->is_user_blocked()) {
                $this->show_blocked_page();
            } else if ($nc_core->input->fetch_post('nc_security_2fa_refresh')) {
                $this->make_new_code();
            } else if (strlen($checked_code)) {
                $this->check_code($checked_code);
            } else {
                $this->show_form();
            }
        }
    }

    /**
     * @return void
     */
    protected function make_new_code() {
        $code = $this->_2fa->make_new_code(__CLASS__);
        $this->respond_with_json($code->get_info());
    }

    /**
     * @param $checked_code
     * @return void
     */
    protected function check_code($checked_code) {
        $check_result = $this->_2fa->check_code($checked_code, __CLASS__);
        if ($check_result['passed']) {
            $this->_2fa->set_passed();
        } else if (!$check_result['attempts_left']) {
            $this->_2fa->block_user();
        }
        $this->respond_with_json($check_result);
    }

    /**
     * @param array $response
     * @return void
     */
    protected function respond_with_json(array $response) {
        while (@ob_end_clean()) ;
        header('Content-Type: application/json');
        echo nc_array_json($response);
        die;
    }

    /**
     * @return bool
     */
    protected function need_to_ask_for_code() {
        $nc_core = nc_core::get_object();

        // 2ФА включена?
        if (!$nc_core->get_settings('AuthCodeMode')) {
            return false;
        }

        // Режим редактирования? (в Netcat 6.4 требуется только в режимах редактирования и администрирования)
        if (!$nc_core->admin_mode) {
            return false;
        }

        // Пользователю присвоены права, требующие 2ФА?
        $permissions = nc_array_value($GLOBALS, 'perm');
        if (!$permissions || !nc_security_2fa::will_require_2fa($permissions)) {
            return false;
        }

        // Уже ввёл правильный код?
        return !$this->_2fa->is_passed();
    }

    protected function show_page($content) {
        while (@ob_end_clean()) ;
        LoginFormHeader();
        echo $content;
        LoginFormFooter();
        die;
    }

    /**
     * @return void
     */
    protected function show_form() {
        $nc_core = nc_core::get_object();
        $code = $this->_2fa->get_current_code(__CLASS__);

        require_once $nc_core->ADMIN_FOLDER . 'CheckUserFunctions.inc.php';

        $params = array(
            'length' => $this->_2fa->get_code_length(),
            'code' => $code,
        );

        $form = $nc_core->ui->view($nc_core->ADMIN_FOLDER . 'views/security/auth/2fa_logon_form', $params);
        $this->show_page($form);
    }

    /**
     * @return void
     */
    protected function show_blocked_page() {
        $nc_core = nc_core::get_object();
        require_once $nc_core->ADMIN_FOLDER . 'CheckUserFunctions.inc.php';

        $user_id = $this->_2fa->get_user_id();

        $seconds_left =
            $nc_core->user->get_by_id($user_id, 'ncBlockedTimestamp')
            + $nc_core->get_settings('AuthCodeUnblockMinutes') * 60
            - time();

        $minutes_left = max(1, ceil($seconds_left / 60));

        $params = array(
            'blocked_permanently' => $nc_core->user->get_by_id($user_id, 'ncBlockedPermanently'),
            'minutes_left' => $minutes_left,
        );

        $blocked_page = $nc_core->ui->view($nc_core->ADMIN_FOLDER . 'views/security/auth/2fa_logon_blocked', $params);
        $this->show_page($blocked_page);
    }
}