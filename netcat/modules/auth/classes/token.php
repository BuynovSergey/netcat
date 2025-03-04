<?php

class nc_auth_token {

    public function get_logins($user_id) {
        $res = array();
        $query = "SELECT `ID`, `Login` FROM `Auth_Token` WHERE `User_ID` = '".intval($user_id)."'";
        $r = nc_Core::get_object()->db->get_results($query, ARRAY_A);
        if ($r) {
            foreach ($r as $v) {
                $res[$v['ID']] = $v['Login'];
            }
        }

        return $res;
    }

    public function delete_by_id($id) {
        $query = "DELETE FROM `Auth_Token` WHERE `ID` = '".intval($id)."'";
        nc_Core::get_object()->db->query($query);

        return 0;
    }

    public function verify($Hash, $Qx, $Qy, $R, $S) {
        $this->_load_files();

        $pGOST = GOSTcurve::generator_GOST();
        $curve_GOST = GOSTcurve::curve_GOST();
        $pubk = new PublicKey($pGOST, new Point($curve_GOST, gmp_Utils::gmp_hexdec('0x'.$Qx), gmp_Utils::gmp_hexdec('0x'.$Qy)));
        $got = $pubk->GOST_verifies(gmp_Utils::gmp_hexdec('0x'.$Hash), new Signature(gmp_Utils::gmp_hexdec('0x'.$R), gmp_Utils::gmp_hexdec('0x'.$S)));
        return $got;
    }

    public function get_random_256() {
        $data = rand().substr(nc_Core::get_object()->get_settings('SecretKey'), 10).time();
        if (function_exists('hash') && function_exists('sha256')) {
            return $this->_fullhex(hash('sha256', $data, true));
        }

        return md5($data).md5($data.'netcat');
    }

    protected function _fullhex($str = null) {
        if (is_null($str)) {
            return false;
        }

        $hexStr = "";
        for ($i = 0; isset($str[$i]); $i++) {
            $char = dechex(ord($str[$i]));
            if (strlen($char) < 2) $hexStr .='0';
            $hexStr .= $char;
        }

        return $hexStr;
    }

    protected function _load_files() {
        $classes = array('CurveFp', 'Point', 'PublicKey', 'Signature', 'GOSTcurve', 'gmp_Utils');
        $dir = nc_module_folder('auth') . 'crypto/';
        foreach ($classes as $v) {
            require_once $dir . $v . '.php';
        }
    }
}
?>
