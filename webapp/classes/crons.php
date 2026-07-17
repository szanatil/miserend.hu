<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Azok az időzített feladatok, amiknek nincs saját helyük
 */
class Crons {

	/**
	 * #351: a stats_externalapi tábla nő a legnagyobbra. Elég az utolsó 30 nap
	 * megőrzése, a régebbi statisztika-sorokat naponta töröljük. A `date` oszlopra
	 * szűrünk (DATE típus). Cron-ként a crons.sql-ben 41-es id-vel regisztrálva.
	 *
	 * (A #351 nearby.log része már megoldott: \Api\NearBy::cleanOldLogs cron 37.
	 * Az emails-takarítás külön, maintainer-döntést igényel — nem része ennek.)
	 */
	public static function cleanExternalApiStats(): void {
		$cutoff = date('Y-m-d', strtotime('-30 days'));
		DB::table('stats_externalapi')->where('date', '<', $cutoff)->delete();
	}

}


