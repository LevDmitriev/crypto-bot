<?php

declare(strict_types = 1);

namespace App\Serializer\Denormalizer;

use App\Entity\Order;
use App\Entity\Order\ByBit\Status as ByBitStatus;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class OrderDenormalizer implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface
{

    /**
     * @inheritDoc
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []) : mixed
    {
        $order = $context[AbstractNormalizer::OBJECT_TO_POPULATE] ?? new Order();
        $order->setByBitStatus(ByBitStatus::from($data['orderStatus']));
        if ($data['cumExecQty']) {
            $order->setCumulativeExecutedQuantity((float) $data['cumExecQty']);
        }
        if ($data['avgPrice']) {
            $order->setAveragePrice((float) $data['avgPrice']);
            if ($data['cumExecQty']) {
                $order->setCumulativeExecutedValue((float) ($data['avgPrice'] * $data['cumExecQty']));
            }
        }

        return $order;
    }

    /**
     * @inheritDoc
     */
    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ) : bool {
        return $type == Order::class
               && is_array($data)
               && isset($data['orderLinkId'])
            ;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedTypes(?string $format) : array
    {
        return [Order::class => true];
    }
}
