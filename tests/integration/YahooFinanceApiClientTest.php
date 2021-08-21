<?php

namespace App\Tests\integration;

use App\Tests\DatabaseDependantTestCase;

class YahooFinanceApiClientTest extends DatabaseDependantTestCase
{
    /**
     * @test
     * @group integration
     */
    public function the_yahoo_finance_api_client_returns_the_correct_data()
    {
        // SETUP //
        // Need YahooFinanceApiClient
        $yahooFinanceApiClient = self::$kernel->getContainer()->get('yahoo-finance-api-client');


        // DO SOMETHING //
        $response = $yahooFinanceApiClient->fetchStockProfile('AMZN', 'US'); //symbol, region

        $stockProfile = json_decode($response->getContent());



        // MAKE ASSERTIONS //
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('USD', $stockProfile->currency);
        $this->assertSame('NasdaqGS', $stockProfile->exchangeName);
        $this->assertSame('AMZN', $stockProfile->symbol);
        $this->assertSame('Amazon.com, Inc.', $stockProfile->shortName);
        $this->assertSame('US', $stockProfile->region);
        $this->assertIsFloat($stockProfile->price);
        $this->assertIsFloat($stockProfile->previousClose);
        $this->assertIsFloat($stockProfile->priceChange);
    }
}