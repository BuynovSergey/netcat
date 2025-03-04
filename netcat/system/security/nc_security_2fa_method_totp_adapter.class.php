<?php

use Otp\Otp;
use Otp\GoogleAuthenticator;
use ParagonIE\ConstantTime\Encoding;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;

class nc_security_2fa_method_totp_adapter {

    protected $length = 6;
    protected $period = 30;
    protected $window = 5;
    protected $digest = 'sha256';
    protected $secret;

    protected $totp;

    public function __construct($secret) {
        $this->totp = new Otp();
        $this->totp->setAlgorithm($this->digest);

        $this->secret = $secret;

        $nc_core = nc_core::get_object();
        $code_validity_minutes = $nc_core->get_settings('AuthCodeValidityMinutes');
        $this->window = ceil(($code_validity_minutes * 60 - $this->period) / $this->period);
    }

    public function get_provisioning_uri() {
        $nc_core = nc_core::get_object();
        $issuer = $nc_core->catalogue->get_current('Domain');
        $label = $nc_core->user->get_current($nc_core->AUTHORIZE_BY);
        return GoogleAuthenticator::getKeyUri(
            'totp',
            $label,
            $this->secret,
            null,
            array('issuer' => $issuer, 'algorithm' => strtoupper($this->digest))
        );
    }

    public function get_qr_uri() {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $data = $writer->writeString($this->get_provisioning_uri());

        return 'data:image/svg+xml;base64,' . base64_encode($data);
    }

    public function verify($input) {
        $input = preg_replace('/\D+/', '', $input);
        return $this->totp->checkTotp(Encoding::base32DecodeUpper($this->secret), $input, $this->window);
    }

}