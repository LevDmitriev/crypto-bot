<?php

namespace App\Tests\Market\Model;

use App\Market\Model\Candle;
use App\Market\Model\CandleCollection;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \App\Market\Model\CandleCollection
 */
class CandleCollectionTest extends TestCase
{

    /**
     * @covers ::getOpenPrice
     */
    public function testGetOpenPrice(): void
    {
        $collection = new CandleCollection();

        for ($i = 10; $i <= 20; $i++) {
            $candle = new Candle(
                startTime:    $i,
                openPrice:    $i,
                closePrice:   1,
                highestPrice: 1,
                lowestPrice:  1,
                volume:       1,
                turnover:     1
            );
            $collection->add($candle);
        }
        $collection->add(new Candle(
                             startTime:    1,
                             openPrice:    100,
                             closePrice:   1,
                             highestPrice: 1,
                             lowestPrice:  1,
                             volume:       1,
                             turnover:     1
                         ));
        self::assertEquals('100', $collection->getOpenPrice());
    }

    /**
     * @covers ::getLowestPrice
     */
    public function testGetLowestPrice(): void
    {
        $collection = new CandleCollection();
        for ($i = 1; $i <= 10; $i++) {
            $candle = new Candle(
                startTime:    1,
                openPrice:    1,
                closePrice:   1,
                highestPrice: 1,
                lowestPrice:  $i,
                volume:       1,
                turnover:     1
            );
            $collection->add($candle);
        }
        self::assertEquals('1', $collection->getLowestPrice());
    }

    /**
     * @covers ::getClosePrice
     */
    public function testGetClosePrice(): void
    {
        $collection = new CandleCollection();
        $collection->add(new Candle(
                             startTime:    11,
                             openPrice:    1,
                             closePrice:   100,
                             highestPrice: 1,
                             lowestPrice:  1,
                             volume:       1,
                             turnover:     1
                         ));
        for ($i = 1; $i <= 10; $i++) {
            $candle = new Candle(
                startTime:    $i,
                openPrice:    1,
                closePrice:   $i,
                highestPrice: 1,
                lowestPrice:  1,
                volume:       1,
                turnover:     1
            );
            $collection->add($candle);
        }
        self::assertEquals('100', $collection->getClosePrice());
    }

    /**
     * @covers ::getVolume
     */
    public function testGetVolume(): void
    {
        $collection = new CandleCollection();
        $result = 0;
        for ($i = 1; $i <= 10; $i++) {
            $candle = new Candle(
                startTime:    1,
                openPrice:    1,
                closePrice:   1,
                highestPrice: 1,
                lowestPrice:  1,
                volume:       $i,
                turnover:     1
            );
            $collection->add($candle);
            $result += $i;
        }
        self::assertEquals($result . '.00', $collection->getVolume());
    }

    /**
     * @covers ::getStartTime
     */
    public function testGetStartTime(): void
    {
        $collection = new CandleCollection();
        for ($i = 1; $i <= 10; $i++) {
            $candle = new Candle(
                startTime:    $i,
                openPrice:    1,
                closePrice:   1,
                highestPrice: 1,
                lowestPrice:  1,
                volume:       1,
                turnover:     1
            );
            $collection->add($candle);
        }
        self::assertEquals('1', $collection->getStartTime());
    }

    /**
     * @covers ::getHighestPrice
     */
    public function testGetHighestPrice(): void
    {
        $collection = new CandleCollection();
        for ($i = 1; $i <= 10; $i++) {
            $candle = new Candle(
                startTime:    1,
                openPrice:    1,
                closePrice:   1,
                highestPrice: $i,
                lowestPrice:  1,
                volume:       1,
                turnover:     1
            );
            $collection->add($candle);
        }

        self::assertEquals('10', $collection->getHighestPrice());
    }
}
