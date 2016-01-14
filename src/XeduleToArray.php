<?php
namespace Want100Cookies\XeduleToArray;

use Sunra\PhpSimple\HtmlDomParser;
use Carbon\Carbon;

/**
* Converts any Xedule data to regular arrays
*/
class XeduleToArray
{
	const BASE_URL = 'https://roosters.xedule.nl';
	
	public function __construct()
	{
		# code...
	}

	private function getStringFromWebsite($url)
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
		return curl_exec($curl);
	}

	private function getDOM($baseUrl)
	{
		try
		{
			$str = $this->getStringFromWebsite($baseUrl);

			if ($str !== FALSE)
			{
				$dom = HtmlDomParser::str_get_html($str);

				if (method_exists($dom, 'find')) // Check if find method exists because sometimes there is a string but not a good one
				{
					return $dom;
				}

			}
			else 
			{
				trigger_error('Website kon niet worden geladen. Url niet correct? Of host geblockt? (url: ' . $baseUrl . ')', E_USER_WARNING);;
				return false;
			}
		} 
		catch (Exception $e)
		{
			trigger_error('getDom($baseUrl) -> Error: ' . $ex->getMessage(), E_USER_WARNING);
		}

		return false;
	}

	// Get the organisation ID 
	public function getOrgID($orgName, $baseUrl)
	{
		$dom = $this->getDOM($baseUrl);

		if ($dom->find('.organisatieContainer')) // If the website contains the class organisatieContainer
		{
			$orgs = $dom->find('.organisatie a'); // Find every link where the parent has the class organisatie

			foreach ($orgs as $key => $org)
			{
				if ($org->innerText() != $orgName) // Check if this is the organisation we are searching for
					continue;

				$path = parse_url($org->href, PHP_URL_PATH); // Only interested in the path
				$pathParts = explode('/', $path);
				$code = $pathParts[count($pathParts) - 1]; // Only interested in the last part
				return $code;
			}
		}

		return null;
	}

	// Get the location ID
	public function getLocationID($locationName, $orgId, $baseUrl)
	{
		$dom = $this->getDom($baseUrl . '/Organisatie/OrganisatorischeEenheid/' . $orgId);

		if ($dom->find('.organisatieContainer')) // If the website contains the class organisatieContainer
		{
			$locations = $dom->find('.organisatie a'); // Find every link where the parent has the class organisatie

			foreach ($locations as $key => $location)
			{
				if ($location->innerText() !== $locationName) // Check if the location is the one we are searching for
					continue;

				$path = parse_url($location->href, PHP_URL_PATH); // Only interested in the path
				$pathParts = explode('/', $path);
				$code = $pathParts[count($pathParts) - 1]; // Only interested in the last part
				return $code;	
			}
		}

		return null;
	}

	// Get the id of the group
	function getGroupID($groupName, $locationId, $baseUrl)
	{
		$dom = $this->getDom($baseUrl . '/OrganisatorischeEenheid/Attendees/' . $locationId);

		$types = $dom->find('.AttendeeTypeBlock');

		foreach ($types as $key => $type)
		{
			if ($type->first_child()->innerText() !== 'Studentgroep') // Only for students
				continue;
			
			$groups = $type->find('a'); // Every link under students type is a group

			foreach ($groups as $key => $group)
			{
				if ($group->innerText() !== $groupName) // Check if the group is the one we are searching for
					continue;

				$path = parse_url($group->href, PHP_URL_PATH);
				$pathParts = explode('/', $path);
				$code = $pathParts[count($pathParts) - 1]; // Only interested in the last part
				return $code;
			}
		}

		return null;
	}

	// Get the schedule for the group and pars it to an array
	function getGroupSchedule($groupId, $week, $year, $baseUrl)
	{
		$url = $baseUrl . '/Calendar/iCalendarICS/' . $groupId . '?week=' . $week . '&year=' . $year;

		$icsFile = $this->getStringFromWebsite($url);

	    $lines = explode("\r\n", $icsFile); // to an array of every line

	    $events = array();
		$lastEvent = array();

	    foreach ($lines as $line)
	    {
	    	switch ($line)
	    	{
	    		case 'BEGIN:VEVENT': // A new event needs a new clean array
	    			$lastEvent = array();
	    			break;

	    		case 'END:VEVENT':
	    			$events[] = $lastEvent; // End of the event so store the last event to the general events array
	    			unset($lastEvent); // unset the last event cause we don't need it anymore
	    			break;
	    		
	    		default:
	    			// We are only interested in start & end dates and the description
	    			// Other fields can easily be added through more preg_match functions
	    			
	    			if (preg_match('/^(DTSTART;.*:)(.*)$/um', $line, $matches)) // Start date
	    			{
	    				$lastEvent['START'] = Carbon::parse(array_pop($matches)); // Preg_match return 2 or 3 results but only the last one is the one we are looking for
	    			}
	    			else if (preg_match('/^(DTEND;.*:)(.*)$/um', $line, $matches)) // End date
	    			{
	    				$lastEvent['END'] = Carbon::parse(array_pop($matches));
	    			}
	    			else if (preg_match('/^(DESCRIPTION:)(.*)$/um', $line, $matches)) // Description
	    			{
	    				$lastEvent['DESCRIPTION'] = array_pop($matches);
	    			}
	    			break;
	    	}
	    }

	    return $events;
	}

}