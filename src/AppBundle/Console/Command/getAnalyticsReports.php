<?php

require_once('vendor/autoload.php');

$analyticsReports = new getAnalyticsReports();

class getAnalyticsReports
{
    function initializeAnalytics($KEY_FILE_LOCATION)
    {
        $client = new Google_Client();
        $client->setApplicationName("Hello Analytics Reporting");
        $client->setAuthConfig($KEY_FILE_LOCATION);
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $analytics = new Google_Service_AnalyticsReporting($client);

        return $analytics;
    }

    function requestReport(&$analytics, &$VIEW_ID, &$start, &$stop)
    {
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($start);
        $dateRange->setEndDate($stop."daysAgo");

        #Dimension
        $pagePath = new Google_Service_AnalyticsReporting_Dimension();
        $pagePath->setName("ga:pagePath");

        #Metrics
        $uniqueEvents = new Google_Service_AnalyticsReporting_Metric();
        $uniqueEvents->setExpression("ga:uniqueEvents");

        $pageViews = new Google_Service_AnalyticsReporting_Metric();
        $pageViews->setExpression("ga:pageViews");

        $avgTime = new Google_Service_AnalyticsReporting_Metric();
        $avgTime->setExpression("ga:avgTimeOnPage");

        $orderByUnique = new Google_Service_AnalyticsReporting_OrderBy();
        $orderByUnique->setFieldName("ga:uniqueEvents");
        $orderByUnique->setOrderType("VALUE");
        $orderByUnique->setSortOrder("DESCENDING");

        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($VIEW_ID);
        $request->setDateRanges($dateRange);
        $request->setDimensions($pagePath);
        $request->setOrderBys($orderByUnique);
        $request->setMetrics(array($uniqueEvents, $pageViews, $avgTime));

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));
        return $analytics->reports->batchGet($body);
    }

    function getResults(&$reports)
    {
        $results = array();

        for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++)
        {
            $report = $reports[$reportIndex];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();

            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++)
            {
                $dim = array();

                $row = $rows[$rowIndex];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++)
                    $dim[] = $dimensions[$i];

                for ($j = 0; $j < count($metricHeaders) && $j < count($metrics); $j++)
                {
                    $entry = $metricHeaders[$j];
                    $values = $metrics[$j];
                    for ($valueIndex = 0; $valueIndex < count( $values->getValues() ); $valueIndex++)
                        $dim[] = $values->getValues()[$valueIndex];
                }
                $results[] = $dim;
            }
        }
        return $results;
    }
}