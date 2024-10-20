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

    /**
     * @var int Код ошибки при обновлении приказа, когда количество имеет слишком много знаков после запятой
     */
    public const int ORDER_QUANTITY_HAS_TOO_MANY_DECIMALS_AMEND = 170134;
    /**
     * @var int В приказе указано слишком малое количество
     * @see see https://www.bybit.com/en/announcement-info/spot-trading-rules/
     */
    public const int ORDER_QUANTITY_EXCEEDED_LOWER_LIMIT = 170136;
    /**
     * @var int Приказ на покупку не соответствует правилам
     * @see https://www.bybit.com/en/announcement-info/spot-trading-rules/
     */
    public const ORDER_VALUE_EXCEEDED_LOWER_LIMIT = 170140;
}
