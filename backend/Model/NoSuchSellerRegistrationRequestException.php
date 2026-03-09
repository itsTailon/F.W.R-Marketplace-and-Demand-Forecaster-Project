<?php

namespace TTE\App\Model;

class NoSuchSellerRegistrationRequestException extends \Exception {
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null) {
        parent::__construct( $message, $code, $previous );
    }
}