<?php

namespace App\Command;

use App\Entity\Stock;
use App\Http\FinanceApiClientInterface;
use App\Http\YahooFinanceApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\SerializerInterface;

class RefreshStockProfileCommand extends Command
{
    protected static $defaultName = 'app:refresh-stock-profile';
    protected static $defaultDescription = 'Check if the refresh stock profile behaves as expected';


    /** @var EntityManagerInterface */
    private ENtityManagerInterface $entityManager;

    /** @var FinanceApiClientInterface  */
    private FinanceApiClientInterface $financeApiClient;

    /** @var SerializerInterface  */
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entityManager, FinanceApiClientInterface $financeApiClient, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->financeApiClient = $financeApiClient;
        $this->serializer = $serializer;

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
        $stockProfile = $this->financeApiClient->fetchStockProfile($input->getArgument('symbol'), $input->getArgument('region'));


        //handle non 2000 status code responses
        if($stockProfile->getStatusCode() !== 200)
        {
            $output->writeln($stockProfile->getContent());
            return Command::FAILURE;
            //TODO: HANDLE
        }

        //2b. Use the stock profile to create a record if it doesn't exist
        /** @var  $stock */
        $stock = $this->serializer->deserialize($stockProfile->getContent(), Stock::class, 'json');

        $this->entityManager->persist($stock);
        $this->entityManager->flush();

        $output->writeln($stock->getShortName() . ' has been saved / updated');

        return Command::SUCCESS;
    }
}
