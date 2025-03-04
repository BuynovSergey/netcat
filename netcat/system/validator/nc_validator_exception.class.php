<?php


class nc_validator_exception extends Exception {


    const DEFAULT_MESSAGE = "Validation error";

    /**
     * @var array
     */
    private $errors;


    public function __construct(array $errors, $message = self::DEFAULT_MESSAGE, $code = 0,
        Exception $previous = null) {
        $this->errors = $errors;

        parent::__construct(count($this->errors) == 1 ? $this->errors[0] : $message, $code, $previous);
    }

    /**
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }


}
