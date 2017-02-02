<?php

namespace AppBundle\Console\Command;

require_once('getAnalyticsReports.php');
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;
use \AppBundle\Entity\PopArticlesAnalytics;
use \AppBundle\Entity\Category;


$GLOBALS['VIEW_ID'] = '*******';
$GLOBALS['KEY_FILE_LOCATION'] = __DIR__ . '/credentials.json';
$GLOBALS['start'] = '2016-08-01';
$GLOBALS['stop'] = 0;
$GLOBALS['analytics'] = $analyticsReports;


class AnalyticsOverall extends ContainerAwareCommand
{    
    protected function configure()
    {
        $this
            ->setName('update:analytics_overall')
            ->setDescription('Update the most popular articles from Google Analytics');
     }

    private function getArticlesByCategory($results, $category)
    {
        $fResults = array();

        foreach($results as $elem)
        {
            $path_article = explode('/', $elem[0]);
            if (count($path_article) == 5)
                if ($path_article[2] == $category)
                    if ($path_article[3] == "article")
                        {
                            $tmp = array();

                            $tmp[] = $path_article[4];
                            $tmp[] = $elem[1];
                            $tmp[] = $elem[2];
                            $tmp[] = $elem[3];
                            $fResults[] = $tmp;
                        }
        }
        return $fResults;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $analytics;
        global $VIEW_ID;
        global $KEY_FILE_LOCATION;
        global $stop;
        global $start;
        
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $qb = $em->createQueryBuilder();

        $qb->delete('AppBundle:PopArticlesAnalytics', 'poparticles')
            ->getQuery()
            ->execute();

        $articleRepository = $em->getRepository('AppBundle:Article');

        $categorys = ['sport', 'multimedia', 'sante', 'auto', 'culture', 'high-tech'];

        $analyticsInit = $analytics->initializeAnalytics($KEY_FILE_LOCATION);
        $response = $analytics->requestReport($analyticsInit, $VIEW_ID,
                                              $start, $stop);
        $results = $analytics->getResults($response);

        foreach($categorys as $category)
        {
            $fResults = $this->getArticlesByCategory($results, $category);
            foreach ($fResults as $elem)
            {
                $article = $articleRepository->findOneBySlug($elem[0]);
                if ($article == null)
                    continue;
                $popArticlesAnalytics = new PopArticlesAnalytics();
                $popArticlesAnalytics->setCategory($category);
                $popArticlesAnalytics->setName($elem[0]);
                $popArticlesAnalytics->setUniqueViews($elem[1]);
                $popArticlesAnalytics->setTotalViews($elem[2]);
                $popArticlesAnalytics->setAverageTime($elem[3]);
                $popArticlesAnalytics->setArticleId($article->getId());
                $popArticlesAnalytics->setAuthorId($article->getAuthor()->getId());
                $em->persist($popArticlesAnalytics);
                $em->flush();
            }
        }
    }
}