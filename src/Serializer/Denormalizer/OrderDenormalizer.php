<?php

declare(strict_types=1);

namespace App\Serializer\Denormalizer;

use App\Entity\Order;
use App\Entity\Order\ByBit\Status as ByBitStatus;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class OrderDenormalizer implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface
{
    /**
     * @inheritDoc
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $order = $context[AbstractNormalizer::OBJECT_TO_POPULATE] ?? new Order();
        $order->setByBitStatus(ByBitStatus::from($data['orderStatus']));
        if ($data['cumExecQty']) {
            $order->setCumulativeExecutedQuantity($data['cumExecQty']);
        }
        if ($data['cumExecFee']) {
            $order->setCumulativeExecutedFee($data['cumExecFee']);
        }
        if ($data['triggerPrice']) {
            $order->setTriggerPrice($data['triggerPrice']);
        }
        if ($data['avgPrice']) {
            $order->setAveragePrice($data['avgPrice']);
            if ($data['cumExecQty']) {
                $order->setCumulativeExecutedValue(bcmul($data['avgPrice'], $data['cumExecQty'], 2));
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
    ): bool {
        return $type == Order::class;
    }

    /**
     * @inheritDoc
     */
    public function getSupportedTypes(?string $format): array
    {
        return [Order::class => true];
    }
}
