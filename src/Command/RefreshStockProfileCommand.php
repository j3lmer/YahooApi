<?php

namespace App\Command;

use App\Entity\Stock;
use App\Http\YahooFinanceApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RefreshStockProfileCommand extends Command
{
    protected static $defaultName = 'app:refresh-stock-profile';
    protected static $defaultDescription = 'Check if the refresh stock profile behaves as expected';


    /** @var EntityManagerInterface */
    private ENtityManagerInterface $entityManager;
    private YahooFinanceApiClient $yahooFinanceApiClient;

    public function __construct(EntityManagerInterface $entityManager, YahooFinanceApiClient $yahooFinanceApiClient)
    {
        $this->entityManager = $entityManager;
        $this->yahooFinanceApiClient = $yahooFinanceApiClient;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('symbol', InputArgument::REQUIRED, 'Stock symbol; (AMZN for Amazon)')
            ->addArgument('region', InputArgument::REQUIRED, 'The region of the company; (US for United states)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //1. Ping Yahoo API and grab the response ( a stock profile ) ['statuscode' => $statusCode, 'content' => $someJsonContent]
        $stockProfile = $this->yahooFinanceApiClient->fetchStockProfile($input->getArgument('symbol'), $input->getArgument('region'));

        //handle non 2000 status code responses
        if($stockProfile['statusCode'] !== 200)
        {
            //TODO: HANDLE
        }

        //2b. Use the stock profile to create a record if it doesn't exist
        $stock = $this->serializer->deserialize($stockProfile['content'], Stock::class, 'json');



//        $stock = new Stock();
//        $stock->setCurrency($stockProfile->currency);
//        $stock->setExchangeName($stockProfile->exchangeName);
//        $stock->setSymbol($stockProfile->symbol);
//        $stock->setShortName($stockProfile->shortName);
//        $stock->setRegion($stockProfile->region);
//        $stock->setPreviousClose($stockProfile->previousClose);
//        $stock->setPrice($stockProfile->price);
//        $priceChange = $stockProfile->price - $stockProfile->previousClose;
//        $stock->setPriceChange($priceChange);

        $this->entityManager->persist($stock);
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
