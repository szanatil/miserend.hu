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
	 * (A #351 nearby.log része már megoldott: \Api\NearBy::cleanOldLogs cron 37.)
	 */
	public static function cleanExternalApiStats(): void {
		$cutoff = date('Y-m-d', strtotime('-30 days'));
		DB::table('stats_externalapi')->where('date', '<', $cutoff)->delete();
	}

	/**
	 * #351: az emails tábla reflex tájékoztató/értesítő leveleit takarítjuk (90 napnál
	 * régebbieket). Az észrevételeket kísérő levelezést (remark_*, remarkfeedback*) ÉS
	 * minden más, itt nem listázott típust MEGTARTJUK — csak a lenti explicit "reflex"
	 * típusokat töröljük, hogy semmi értékes ne vesszen el. A lista a maintainer által
	 * bővíthető, ha többet is takarítana. Cron: crons.sql 42-es id.
	 */
	public static function cleanNotificationEmails(): void {
		$deletableTypes = [
			'user_pleaseupdate',   // "frissítsd az adataidat" reflex emlékeztető
			'user_pleaselogin',    // "jelentkezz be" reflex értesítő
			'churchholders_allowed_user',
			'churchholders_asked_admin',
			'image_admin', 'image_diocese', 'image_responsible',
		];
		$cutoff = date('Y-m-d H:i:s', strtotime('-90 days'));
		DB::table('emails')
			->where('created_at', '<', $cutoff)
			->whereIn('type', $deletableTypes)
			->delete();
	}

}


