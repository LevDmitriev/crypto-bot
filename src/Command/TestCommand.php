<?php

declare(strict_types=1);

namespace App\Command;

use App\Bybit\ErrorCodes;
use App\Entity\Order;
use App\Repository\CoinRepository;
use ByBit\SDK\ByBitApi;
use ByBit\SDK\Exceptions\HttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CoinRepository $coinRepository,
        private readonly ByBitApi $byBitApi
    ) {
        parent::__construct('test:command');
    }



    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->sellAllCoins();
        return 0;
        $orderLinkId = '01928bbf-9245-7022-a683-964b0ab30d0a';
        $result = $this->byBitApi->tradeApi()->getOpenOrders(['orderLinkId' => $orderLinkId, 'category' => 'spot']);
        if (!$result['list']) {
            $result = $this->byBitApi->tradeApi()->getOrderHistory(['orderLinkId' => $orderLinkId, 'category' => 'spot']);
        }
        $a = 0;
        return 0;
        $this->sellAllCoins();
        return self::SUCCESS;
        $orderLinkId = time() . "-11-09-2024";
        $price = $this->getBitCoinCurrentPrice();
        //        $this->sellAllBitCoins();
        $this->byBitApi->tradeApi()->cancelAllOrders([
            "category" => Order\ByBit\Category::spot->value,
            "symbol" => "BTCUSDT",
//            "settleCoin" => "USDT"
        ]);
        $result = $this->byBitApi->tradeApi()->placeOrder([
                'orderLinkId' => $orderLinkId,
                'category' => Order\ByBit\Category::spot->value,
                'symbol' => "BTCUSDT",
                'side' => Order\ByBit\Side::Buy->value,
                'orderType' => Order\ByBit\Type::Market->value,
                'qty' => '6',
        ]);
        sleep(5);
        $orderFromHistory = $this->getOrderFromHistory($orderLinkId);
        $result = $this->byBitApi->tradeApi()->placeOrder([
            'orderLinkId' => '1' . $orderLinkId,
            'category' => Order\ByBit\Category::spot->value,
            'symbol' => "BTCUSDT",
            'side' => Order\ByBit\Side::Sell->value,
            'orderType' => Order\ByBit\Type::Market->value,
//            'qty' => '0.0001',
            'qty' => $orderFromHistory["list"][0]["cumExecQty"],
            'triggerPrice' => bcmul($price, '0.9999', 2),
            'orderFilter' => 'StopOrder'
        ]);
        sleep(5);
        echo print_r($this->getOpenOrders(2), true);
        echo print_r($this->getOrderFromHistory('1' . $orderLinkId), true);

        return self::SUCCESS;
    }

    private function getOrderHistory(int $limit): array
    {
        return $this->byBitApi->tradeApi()->getOrderHistory(['category' => 'spot', 'limit' => $limit]);
    }
    private function getOrderFromHistory(string $orderLinkId): array
    {
        return $this->byBitApi->tradeApi()->getOrderHistory(['category' => 'spot', 'orderLinkId' => $orderLinkId]);
    }
    private function getOpenOrders(int $limit = 1): array
    {
        return $this->byBitApi->tradeApi()->getOpenOrders([
            'category' =>'spot',
            'symbol' => 'BTCUSDT',
            'limit' => $limit,
        ]);
    }
    private function getBitCoinCurrentPrice(): string
    {
        $kline = $this->byBitApi->marketApi()->getKline(['category' => 'spot', 'symbol' => 'BTCUSDT', 'interval' => '60', 'limit' => '1']);
        return $kline['list'][0][4];
    }

    /**
     * Продать все биткоины
     */
    private function sellAllCoins(): void
    {
        $walletBalance = $this->byBitApi->accountApi()->getWalletBalance(['accountType' => 'UNIFIED']);
        assert($walletBalance['list'][0]['coin'][0]['coin'] === 'BTC');
        foreach ($walletBalance['list'][0]['coin'] as $coin) {
            try {
                $this->byBitApi->tradeApi()->placeOrder([
                                                            'category' => Order\ByBit\Category::spot->value,
                                                            'symbol' => "{$coin['coin']}USDT",
                                                            'side' => Order\ByBit\Side::Sell->value,
                                                            'orderType' => Order\ByBit\Type::Market->value,
                                                            'qty' => $coin['walletBalance'],
                                                        ]);
            } catch (HttpException $e) {
                if ($e->getCode() !== ErrorCodes::ORDER_QUANTITY_EXCEEDED_LOWER_LIMIT) {
                    throw $e;
                }
            }

        }
        sleep(5);
    }
}
