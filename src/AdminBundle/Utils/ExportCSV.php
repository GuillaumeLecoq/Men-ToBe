<?php

namespace AdminBundle\Utils;

use UserBundle\Entity\User;

class ExportCSV
{
	public function getFileExtension() { return 'csv'; }
	public function getContentType()   { return 'text/csv'; }

	public function dump($users)
	{
	    $fp = fopen('php://temp','r+');

	    // Header
	    $row = array(
	        "Lastname","Firstname", "Email"
	    );
	    fputcsv($fp,$row);

	    // Users is passed in
	    foreach($users as $user)
	    {
	        // Build up row
	        $row = array();
	        $row[] = $user->getLastname();
	        $row[] = $user->getFirstname();
	        $row[] = $user->getEmail();

	        fputcsv($fp,$row);
	    }

	    // Return the content
	    rewind($fp);
	    $csv = stream_get_contents($fp);
	    fclose($fp);

	    return $csv;
	}
}