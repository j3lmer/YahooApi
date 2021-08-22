<?php

namespace App\Tests\feature;

use App\Entity\Stock;
use App\Http\FakeYahooFinanceApiClient;
use App\Tests\DatabaseDependantTestCase;
use App\Tests\DatabasePrimer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;


class RefreshStockProfileCommandTest extends DatabaseDependantTestCase
{



    /** @test  */
    public function the_refresh_stock_profile_command_behaves_correctly_when_a_stock_record_does_not_exist()
    {
        // SETUP //
        $application = new Application(self::$kernel);

        //Command
        $command = $application->find('app:refresh-stock-profile');
        $commandTester = new CommandTester($command);

        FakeYahooFinanceApiClient::$content = '{"symbol":"AMZN","shortName":"Amazon.com, Inc.","region":"US","currency":"USD","exchangeName":"NasdaqGS","price":3199.95,"previousClose":3187.75,"priceChange":12.20}';

        // DO SOMETHING //
        $commandTester->execute([
            'symbol' => 'AMZN',
            'region' => 'US'
        ]);


        // MAKE ASSERTIONS //
        $repo = $this->entityManager->getRepository(Stock::class);

        /** @var Stock $stock */
        $stock = $repo->findOneBy(['symbol' => 'AMZN']);

        $this->assertSame('USD', $stock->getCurrency());
        $this->assertSame('NasdaqGS', $stock->getExchangeName());
        $this->assertSame('AMZN', $stock->getSymbol());
        $this->assertSame('Amazon.com, Inc.', $stock->getShortName());
        $this->assertSame('US', $stock->getRegion());
        $this->assertGreaterThan(50, $stock->getPrice());
        $this->assertStringContainsString('Amazon.com, Inc. has been saved / updated', $commandTester->getDisplay());

    }

    /** @test */
    public function non_200_status_code_responses_are_handled_correctly()
    {
        // SETUP //
        $application = new Application(self::$kernel);

        //Command
        $command = $application->find('app:refresh-stock-profile');
        $commandTester = new CommandTester($command);

        //non 200 response
        FakeYahooFinanceApiClient::$statusCode = 500;

        //error content
        FakeYahooFinanceApiClient::$content = 'Finance Api Client error';

        // DO SOMETHING //
        $commandStatus = $commandTester->execute([
            'symbol' => 'AMZN',
            'region' => 'US'
        ]);

        // MAKE ASSERTIONS //
        $repo = $this->entityManager->getRepository(Stock::class);

        $stockRecordCount = $repo->createQueryBuilder('stock')
            ->select('count(stock.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals(1, $commandStatus);

        $this->assertEquals(0, $stockRecordCount);

        $this->assertStringContainsString('Finance Api Client error', $commandTester->getDisplay());
    }

}