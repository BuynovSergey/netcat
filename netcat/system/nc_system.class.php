<?php

abstract class nc_System {

    protected $debug_mode = 0;
    protected $debug_access = false;
    protected $system_start_mctime;
    protected $debug_arr = array();

    protected function __construct() {
        $this->debug_level_arr = array(
            'error' => '#FFE5E5',
            'info'  => '#F0F7FF',
            'ok'    => '#EDFFEB'
        );
    }

    /**
     * Collect debug info function
     * for critical errors!
     *
     * @param Exception $e
     */
    public function errorMessage(Exception $e) {
        if (!$this->debug_mode) {
            return;
        }

        $this->debug_arr[] = array(
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'level'   => 'error'
        );
    }

    /**
     * Collect debug info function
     *
     * @param string $message
     * @param string $file
     * @param int $line
     * @param string $level
     */
    public function debugMessage($message, $file = '', $line = 0, $level = 'info') {
        if (!$this->debug_mode) {
            return;
        }

        $this->debug_arr[] = array(
            'message' => $message,
            'file'    => $file,
            'line'    => $line,
            'level'   => array_key_exists($level, $this->debug_level_arr) ? $level : "info"
        );
    }

    protected function debugInfo() {
        $result = '';

        if (!empty($this->debug_arr)) {
            $result = "<div style='font-family:Arial, sans-serif; font-size:14px; padding:10px'>";
            $result .= "<h2 style='padding-bottom:5px'>System debug info, <span style='color:#A00'>" . get_class($this) . '</span> class</h2>';
            $result .= "<table cellpadding='5' cellspacing='1' style='font-size:12px; border:none; background:#CCC; width:100%'>";
            $result .= "<col style='width:1%'/><col style='width:45%'/><col style='width:44%'/><col style='width:10%'/>";
            $result .= "<tr><td style='background:#EEE'><b>!</b></td><td style='background:#EEE'><b>Message</b></td><td style='background:#EEE'><b>File</b></td><td style='background:#EEE'><b>Line</b></td></tr>";
            foreach ($this->debug_arr as $debug) {
                $background = $this->debug_level_arr[$debug['level']] ?: '#FFFFFF';
                $result .= "<tr><td style='background:{$background}'></td><td style='background:#FFF'>{$debug['message']}</td><td style='background:#FFF'>{$debug['file']}</td><td style='background:#FFF'>{$debug['line']}</td></tr>";
            }
            $result .= '</table>';
            $result .= '</div>';
        }

        return $result;
    }

    protected function check_system_install() {
        global $DOCUMENT_ROOT, $SUB_FOLDER;
        global $MYSQL_PASSWORD, $MYSQL_DB_NAME;

        if (
            !$MYSQL_PASSWORD &&
            !$MYSQL_DB_NAME &&
            file_exists($DOCUMENT_ROOT . $SUB_FOLDER . '/install/index.php')
        ) {
            header("Location: {$SUB_FOLDER}/install/");
            exit;
        }

        return true;
    }
}
