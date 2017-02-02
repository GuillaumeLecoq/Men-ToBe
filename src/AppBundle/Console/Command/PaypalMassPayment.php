<?php

namespace AppBundle\Console\Command;

require_once('PaypalCredentials.php');
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \AppBundle\Entity\Advertiser;
use \AppBundle\Entity\PopArticleAnalyticsMonth;
use GuzzleHttp\Client;

$GLOBALS['accountCredentials'] = $accountCredentials;


class PaypalMassPayment extends ContainerAwareCommand
{    
    protected function configure()
    {
        $this
            ->setName('proceed:mass_payment')
            ->setDescription('Send money to the authors using Paypal Mass payment');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $accountCredentials;

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $advertiserRepository = $em->getRepository('AppBundle:Advertiser');
        $userRepository = $em->getRepository('UserBundle:User');
        $analyticsMonthRepository = $em->getRepository('AppBundle:PopArticlesAnalyticsMonth');

        $advertiserEntity = $advertiserRepository->findOneByIsDone(false);

        if (!$advertiserEntity)
            return;

        $advertiserBalance = intval($advertiserEntity->getBalance() / 1.1);
        
        $authors = $analyticsMonthRepository->getPopularAuthors(1000000, 0, null, true);
        if (!$authors)
            return;
        $authorsEntity = $userRepository->findById($analyticsMonthRepository->getPopularAuthors(1000000, 0));

        $overallUv = 0;
        foreach ($authors as $author)
            $overallUv += $author['uv'];

        $paymentDest = [];
        $j = 0;
        for ($i = 0; $i != sizeof($authorsEntity); $i++)
        {
            $earned = $authors[$i]['uv'] / $overallUv * $advertiserBalance + $authorsEntity[$i]->getBalance();
            $authorsEntity[$i]->setTotalAmount($authorsEntity[$i]->getTotalAmount() + $earned);

            if ($earned >= $authorsEntity[$i]->getThreshold() &&
                $authorsEntity[$i]->getLastPayment() + 1 >= $authorsEntity[$i]->getFrequency())
            {
                if ($authorsEntity[$i]->getEmailPaypal())
                    $paymentDest['L_EMAIL'.$j] = $authorsEntity[$i]->getEmailPaypal();
                else
                    $paymentDest['L_EMAIL'.$j] = $authorsEntity[$i]->getEmail();
                $paymentDest['L_AMT'.$j] = intval($earned);
                $authorsEntity[$i]->setBalance(0);
                $authorsEntity[$i]->setLastPayment(0);
                $j++;
            }
            else
            {
                $authorsEntity[$i]->setBalance($earned);
                $authorsEntity[$i]->setLastPayment($authorsEntity[$i]->getLastPayment() + 1);
            }
        }

        $sendMoney = new Client();

        $massPaymentDetail = [
            'METHOD' => 'MassPay',
            'VERSION' => 90,
            'RECEIVERTYPE' => 'EmailAddress',
            'CURRENCYCODE' => 'EUR',
        ];
        
        $paymentDetails = array_merge($accountCredentials, $massPaymentDetail);

        $paymentFeedback = $sendMoney->post("https://api-3t.sandbox.paypal.com/nvp", ['body' => array_merge($paymentDetails, $paymentDest)]);

        parse_str(urldecode($paymentFeedback->getBody()), $body);
        if ($body['ACK'] == 'Failure')
            return;

        $advertiserEntity->setIsDone(true);

        foreach ($authorsEntity as $authors)
            $em->persist($authors);
        $em->persist($advertiserEntity);
        $em->flush();
    }
}