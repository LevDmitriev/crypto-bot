<?php

declare(strict_types=1);

namespace App\Command;

use App\Market\Model\Candle;
use App\TradingStrategy\TradingStrategyFactoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use WebSocket\Client;
use WebSocket\Connection;
use WebSocket\Message\Message;
use WebSocket\Message\Text;

/**
 * Команда слежения за свечами и передача свечей торговой стратегии
 */
class WatchCandlesCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'app.serializer.bybit')]
        private readonly DenormalizerInterface $denormalizer,
        private readonly TradingStrategyFactoryInterface $tradingStrategyFactory,
    ) {
        parent::__construct('app:kline:watch');
    }

    public function getName(): ?string
    {
        return 'app:kline:watch';
    }

    protected function configure()
    {
        $this->addOption('topic', 't', mode: InputOption::VALUE_OPTIONAL, default: 'kline.5.BTCUSDT');
        $this->addOption('strategy', 's', mode: InputOption::VALUE_OPTIONAL, default: 'always-buy');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $strategy = $this->tradingStrategyFactory->create($input->getOption('strategy'));
        $topic = $input->getOption('topic');
        $client = new Client("wss://stream.bybit.com/v5/public/spot");
        $client->send(new Text(json_encode(["op" => "subscribe", 'args' => [$topic]])));
        $client
            ->onText(function (Client $client, Connection $connection, Message $message) use ($strategy) {
                $json = json_decode($message->getContent(), true);
                if (isset($json['data'][0])) {
                    $candle = $this->denormalizer->denormalize($json['data'][0], Candle::class);
                    $strategy->trade($candle);
                }
            })
            ->start();

        return self::SUCCESS;
    }
}
