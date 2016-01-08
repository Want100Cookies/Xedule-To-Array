<?php
ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';

use Sunra\PhpSimple\HtmlDomParser;

$domain = 'https://roosters.xedule.nl';

echo '<pre>';

function getStrFromWebsite($url)
{
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
	return curl_exec($curl);
}

function getDOM($baseUrl)
{
	try
	{
		$str = getStrFromWebsite($baseUrl);

		if ($str !== FALSE)
		{
			$dom = HtmlDomParser::str_get_html($str);

			if (method_exists($dom, 'find'))
			{
				return $dom;
			}

		}
		else 
		{
			echo 'Website kon niet worden geladen. Url niet correct? Of host geblockt? (url: ' . $baseUrl . ')<br>';
			return false;
		}
	} 
	catch (Exception $e)
	{
		trigger_error('getDom($baseUrl) -> Error: ' . $ex->getMessage(), E_USER_WARNING);
	}

	return false;
}

function getOrgID($orgName, $baseUrl)
{
	$dom = getDOM($baseUrl);

	if ($dom->find('.organisatieContainer')) 
	{
		$orgs = $dom->find('.organisatie a');

		foreach ($orgs as $key => $org)
		{
			if ($org->innerText() != $orgName)
				continue;

			$path = parse_url($org->href, PHP_URL_PATH);
			$pathParts = explode('/', $path);
			$code = $pathParts[count($pathParts) - 1];
			return $code;
		}
	}

	return null;
}

function getLocationID($locationName, $orgId, $baseUrl)
{
	$dom = getDom($baseUrl . '/Organisatie/OrganisatorischeEenheid/' . $orgId);

	if ($dom->find('.organisatieContainer'))
	{
		$locations = $dom->find('.organisatie a');

		foreach ($locations as $key => $location)
		{
			if ($location->innerText() !== $locationName)
				continue;

			$path = parse_url($location->href, PHP_URL_PATH);
			$pathParts = explode('/', $path);
			$code = $pathParts[count($pathParts) - 1];
			return $code;	
		}
	}

	return null;
}

function getGroupID($groupName, $locationId, $baseUrl)
{
	$dom = getDom($baseUrl . '/OrganisatorischeEenheid/Attendees/' . $locationId);

	$types = $dom->find('.AttendeeTypeBlock');

	foreach ($types as $key => $type)
	{
		if ($type->first_child()->innerText() !== 'Studentgroep')
			continue;
		
		$groups = $type->find('a');

		foreach ($groups as $key => $group)
		{
			if ($group->innerText() !== $groupName)
				continue;

			$path = parse_url($group->href, PHP_URL_PATH);
			$pathParts = explode('/', $path);
			$code = $pathParts[count($pathParts) - 1];
			return $code;
		}
	}

	return null;
}

function getGroupSchedule($groupId, $week, $year, $baseUrl)
{
	$url = $baseUrl . '/Calendar/iCalendarICS/' . $groupId . '?week=' . $week . '&year=' . $year;

	$icsFile = getStrFromWebsite($url);

    $lines = explode("\r\n", $icsFile);

    $events = array();

	$lastEvent = array();

    foreach ($lines as $line)
    {
    	switch ($line)
    	{
    		case 'BEGIN:VEVENT':
    			$lastEvent = array();
    			break;

    		case 'END:VEVENT':
    			$events[] = $lastEvent;
    			unset($lastEvent);
    			break;
    		
    		default:
    			if (preg_match('/^(DTSTART;.*:)(.*)$/um', $line, $matches))
    			{
    				$lastEvent['START'] = array_pop($matches);
    			}
    			else if (preg_match('/^(DTEND;.*:)(.*)$/um', $line, $matches))
    			{
    				$lastEvent['END'] = array_pop($matches);
    			}
    			else if (preg_match('/^(DESCRIPTION:)(.*)$/um', $line, $matches))
    			{
    				$lastEvent['SUMMARY'] = array_pop($matches);
    			}
    			break;
    	}
    }

    return $events;
}

$orgId = getOrgID('Stenden', $domain);
$locationId = getLocationID('Stenden Emmen', $orgId, $domain);
$groupId = getGroupID('INF2C', $locationId, $domain);
var_dump(getGroupSchedule($groupId, '2', '2016', $domain));

echo '</pre>';
