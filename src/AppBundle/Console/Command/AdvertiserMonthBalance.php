<?php

namespace AppBundle\Console\Command;

require_once('PaypalCredentials.php');
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use GuzzleHttp\Client;
use \AppBundle\Entity\Advertiser;

$GLOBALS['accountCredentials'] = $accountCredentials;
$GLOBALS['providerEmail'] = $providerEmail;


class AdvertiserMonthBalance extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('update:advertiser_balance')
            ->setDescription('Update the advertiser (AlloTraffic) balance for this months');
     }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $accountCredentials;
        global $providerEmail;

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $advertiserRepository = $em->getRepository('AppBundle:Advertiser');

        $getBalance = new Client(['base_uri' => 'https://api-3t.sandbox.paypal.com']);

        $dateLastMonth = new \DateTime();
        $dateLastMonth = $dateLastMonth->modify('-1 month')->format('Y-m');

        $getBalanceDetail = [
            'METHOD' => 'TransactionSearch',
            'VERSION' => 90,
            'STARTDATE' => $dateLastMonth . '-01T00:00:00Z',
            'STATUS' => 'Success',
            'EMAIL' => $providerEmail
        ];

        $requestDetails = array_merge($accountCredentials, $getBalanceDetail);
        
        $requestResult = $getBalance->request('POST', 'nvp', [
            'form_params' => $requestDetails
        ]);

        parse_str(urldecode($requestResult->getBody()), $body);

        $advertiserEntityByTrans = $advertiserRepository->findOneByTransactionId($body['L_TRANSACTIONID0']);

        if ($advertiserEntityByTrans != null)
            return;

        $advertiserEntity = new Advertiser();
        $advertiserEntity->setBalance($body['L_AMT0']);
        $advertiserEntity->setTransactionId($body['L_TRANSACTIONID0']);
        $advertiserEntity->setTransactionDate(new \DateTime($body['L_TIMESTAMP0']));
        $em->persist($advertiserEntity);
        $em->flush();
     }
}