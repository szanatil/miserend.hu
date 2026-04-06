<?php

namespace Html\Ajax;

use Illuminate\Database\Capsule\Manager as DB;

class AutocompleteBoundaries extends Ajax {

	public $format = "json";

    public function __construct() {
        $kulcsszo = \Request::Text('text');
		// TODO: kezeljük azért valahogy, nehogy bajt csináljon!

		$limit = 9;
		
		$results = \Eloquent\Boundary::where('name', 'like', '%' . $kulcsszo . '%')
			->where(function($query) {
				$query->whereNull('denomination')
					->orWhere('denomination', 'like', '%catholic%');
			})
			->orderByRaw("CASE WHEN boundary = 'religious_administration' THEN 0 WHEN boundary = 'administrative' THEN 1 ELSE 2 END")
			->orderBy('admin_level', 'asc')
			->orderBy('name', 'asc')
			->take($limit)
			->get()
			->map->toSimpleArray();

		$this->content = json_encode(array('results' => $results));

		return;

    }

}
