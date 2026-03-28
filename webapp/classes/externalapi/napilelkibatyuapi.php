<?php

namespace ExternalApi;

https://github.com/szentjozsefhackathon/napi-lelki-batyu

class NapilelkibatyuApi extends \ExternalApi\ExternalApi {

    public $name = 'napilelkibatyu';
    public $apiUrl = "https://szentjozsefhackathon.github.io/napi-lelki-batyu/" ;    
	public $format = 'json';
	public $cache = "1 week"; //false or any time in strtotime() format
	public $testQuery = '';
    
    function buildQuery() {
        global $config;
        $this->rawQuery = $this->query;        
    }
	
	function liturgicalDay($date = false) {
		if($date == false)
			$date = date('Y-m-d');
				
		$this->fetchApiData(date('Y'));
		
		if(!isset($this->jsonData->$date)) {
			//throw new \Exception("There is no data for date '$date' from napilelkibatyu.");
			return false;
		}
		
		return $this->extractDateInfo();
	}

	/**
	 * Get liturgical days for a date range
	 * @param string $from ISO 8601 datetime string (e.g., "2026-03-28T00:00:00")
	 * @param string $until ISO 8601 datetime string (e.g., "2026-04-03T23:59:00")
	 * @return array Associative array with dates as keys and liturgical info as values
	 */
	function getLiturgicalDaysInRange($from, $until) {
		// Parse ISO 8601 datetime strings to extract dates (YYYY-MM-DD)
		$fromDate = explode('T', $from)[0];
		$untilDate = explode('T', $until)[0];
		
		// Get start and end timestamps
		$startTimestamp = strtotime($fromDate);
		$endTimestamp = strtotime($untilDate);
		
		// Collect years to fetch
		$yearsToFetch = [];
		$currentDate = $startTimestamp;
		while ($currentDate <= $endTimestamp) {
			$year = date('Y', $currentDate);
			if (!in_array($year, $yearsToFetch)) {
				$yearsToFetch[] = $year;
			}
			$currentDate = strtotime('+1 day', $currentDate);
		}
		
		// Fetch API data for all needed years
		foreach ($yearsToFetch as $year) {
			$this->fetchApiData($year);
		}
		
		// Build result array
		$result = [];
		$currentDate = $startTimestamp;
		while ($currentDate <= $endTimestamp) {
			$dateStr = date('Y-m-d', $currentDate);
			
			if (isset($this->jsonData->$dateStr)) {
				$result[$dateStr] = $this->extractDateInfo($dateStr);
			}
			
			$currentDate = strtotime('+1 day', $currentDate);
		}
		
		return $result;
	}

	function liturgicalAlert($date = false) {
		if($date == false)
			$date = date('Y-m-d');
				
		$nextDay = date('Y-m-d', strtotime($date . ' +1 day'));

		// Fetch API data
		$this->fetchApiData(date('Y'));
		
		if(!isset($this->jsonData->$date)) {
			//throw new \Exception("There is no data for date '$date' from napilelkibatyu.");
			return false;
		}

		// Extract information for current date and next day
		$dateInfo = $this->extractDateInfo($date);
		$nextDayInfo = $this->extractDateInfo($nextDay);

		// Check if we should render alert and return rendered output if yes
		return $this->renderAlertIfNeeded($date, $nextDay, $dateInfo, $nextDayInfo);
	}

	/**
	 * Fetch API data for the given year
	 * @param string $year
	 */
	private function fetchApiData($year) {
		$this->query = $year . ".json";
		$this->run();
	}

	/**
	 * Extract liturgical information for a given date
	 * @param string $date
	 * @return array
	 */
	private function extractDateInfo($date) {
		$isSunday = date('N', strtotime($date)) == 7;
		$level = $this->jsonData->$date->celebration[0]->level;
		
		// use the last celebration entry instead of the first (index 0)
		$celebrations = $this->jsonData->$date->celebration;
		if (is_array($celebrations) && count($celebrations) > 0) {
			$lastCelebration = end($celebrations);
			$name = isset($lastCelebration->name) ? $lastCelebration->name : '';
		} else {
			$name = '';
		}
		
		return [
			'date' => $date,
			'name' => $name,
			'level' => $level,
			'isSunday' => $isSunday
		];
	}

	/**
	 * Check if alert should be rendered and render if conditions are met
	 * @param string $date
	 * @param string $nextDay
	 * @param array $dateInfo
	 * @param array $nextDayInfo
	 * @return bool|string
	 */
	private function renderAlertIfNeeded($date, $nextDay, $dateInfo, $nextDayInfo) {
		if($dateInfo['level'] <= 4 || $nextDayInfo['level'] <= 4) {
			return $this->renderAlert($dateInfo, $nextDayInfo);
		}
		return false;
	}

	/**
	 * Render the liturgical alert template
	 * @param array $dateInfo
	 * @param array $nextDayInfo
	 * @return string
	 */
	private function renderAlert($dateInfo, $nextDayInfo) {
		global $twig;
		global $_honapok;
		return $twig->render('alert_liturgicalday.html',
			array(
				'honapok' => $_honapok,
				'date' => $dateInfo,
				'nextDay' => $nextDayInfo,
			));
	}
}

