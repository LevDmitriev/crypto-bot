<?php

declare(strict_types=1);

namespace App\Command;

use ByBit\SDK\ByBitApi;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Message\Message;

class TestCommand extends Command
{
    public function getName(): ?string
    {
        return 'test:command';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bybitApi = new ByBitApi('e0xMGEHgCp0nJLHrVY', 'WZgnTBdbUIR4HH9JYoGX2tQBtAb6OjIKMzj1');
        $client = new Client("wss://stream.bybit.com/v5/public/linear");
        $client->send(new \WebSocket\Message\Text(json_encode(["op" => "subscribe", 'args' => ['kline.5.BTCUSDT']])));
        $client
            // Add standard middlewares
            //->addMiddleware(new \WebSocket\Middleware\CloseHandler())
            //->addMiddleware(new \WebSocket\Middleware\PingResponder())
            // Listen to incoming Text messages
            ->onText(function (Client $client, Connection $connection, Message $message) {
                // Act on incoming message
                echo "Got message: {$message->getContent()} \n";
            })
            ->start();
        //$result = $bybitApi->accountApi()->getAccountInfo([]);

        return self::SUCCESS;
    }
}
