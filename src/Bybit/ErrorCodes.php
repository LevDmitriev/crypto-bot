<?php

namespace App\Bybit;

/**
 * Коды ошибок ByBit
 * @link https://bybit-exchange.github.io/docs/v5/error
 */
class ErrorCodes
{
    public const NOT_SUPPORTED_SYMBOLS = 10001;
    public const INVALID_SERVER_TIMESTAMP = 10002;
    public const ORDER_DOES_NOT_EXISTS = 170213;
    public const ORDER_QUANTITY_HAS_TOO_MANY_DECIMALS = 170137;
}
