<?php

/**
 * Оставлен для совместимости, функционал реализован в nc_Mail
 */
class CMIMEMail extends nc_Mail
{

    function __construct($priority = 3)
    {
        parent::__construct();
        $this->priority = $priority;
    }

}
