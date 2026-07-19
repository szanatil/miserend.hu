<?php

namespace Html\Church;

class Edit extends \Html\Html {
    public $tid;
    public $church;
    public $form;
    public $help;
    public $input;
    public $nearbyChurches;

    public function __construct($path) {
        global $user;
   
        $this->input = $_REQUEST;
        $this->tid = $path[0];
        $this->church = \Eloquent\Church::find($this->tid);
        if (!$this->church) {
            throw new \Exception('Nincs ilyen templom.');
        }
        $this->church = $this->church->append(['writeAccess']);

        if (!$this->church->writeAccess) {
            throw new \Exception('Hiányzó jogosultság!');
            return;
        }

        $isForm = \Request::Text('submit');
        $isRelationshipAction = isset($_REQUEST['relationship']['add']) || isset($_REQUEST['relationship']['delete']);
        if ($isForm || $isRelationshipAction) {
            $this->modify();
        }
        $this->preparePage();
    }

    function modify() {
        $hasChurchForm = isset($this->input['church']['id']);

        // --- Kapcsolat hozzáadása (önálló akció, nincs szükség a teljes church formra) ---
        if (isset($this->input['relationship']['add']) && !empty($this->input['relationship']['parent_id'])) {
            $parentId = (int) $this->input['relationship']['parent_id'];
            $type = $this->input['relationship']['type'] ?? '';
            $validTypes = \Eloquent\ChurchRelationship::validTypes();
            if (in_array($type, $validTypes) && $parentId !== (int)$this->tid) {
                \Eloquent\ChurchRelationship::updateOrCreate(
                    ['parent_church_id' => $parentId, 'child_church_id' => $this->tid],
                    ['type' => $type]
                );
            }
            $this->redirect("/church/" . $this->tid . "/edit");
            return;
        }

        // --- Kapcsolat törlése (önálló akció) ---
        if (isset($this->input['relationship']['delete'])) {
            $relId = (int) $this->input['relationship']['delete'];
            $rel = \Eloquent\ChurchRelationship::find($relId);
            // Csak akkor törölheti, ha a child_church_id == $this->tid (saját kapcsolat)
            if ($rel && (int)$rel->child_church_id === (int)$this->tid) {
                $rel->delete();
            }
            $this->redirect("/church/" . $this->tid . "/edit");
            return;
        }

        // --- Teljes church form mentése ---
        if ($hasChurchForm && $this->input['church']['id'] != $this->tid) {
            throw new \Exception("Gond van a módosítandó templom azonosítójával.");
        }

        if (!$hasChurchForm) {
            return; // Nincs church form, nincs mit menteni
        }

        $allowedFields = ['adminmegj', 'nev',
            'orszag', 'megye', 'varos', 'cim',
            'egyhazmegye', 'espereskerulet', 'plebania', 'pleb_eml',
            'megjegyzes', 'miseaktiv', 'misemegj', 'leiras', 'ok', 'frissites',
            'lat','lon'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $this->input['church'])) {
                $this->church->$field = $this->input['church'][$field];
            }
        }

        // Handle external calendar URL
        if (isset($this->input['church']['external_calendar_url'])) {
            $newUrl = trim($this->input['church']['external_calendar_url']);
            
            if (!empty($newUrl)) {
                // URL validation
                if (!filter_var($newUrl, FILTER_VALIDATE_URL)) {
                    throw new \Exception('Érvénytelen URL formátum!');
                }
                
                // Update or create external calendar
                $externalCal = \Eloquent\ExternalCalendar::where('church_id', $this->tid)
                    ->where('active', 1)
                    ->first();
                
                if ($externalCal) {
                    $externalCal->url = $newUrl;
                    $externalCal->save();
                } else {
                    \Eloquent\ExternalCalendar::create([
                        'church_id' => $this->tid,
                        'name' => 'Google Calendar',
                        'url' => $newUrl,
                        'active' => 1
                    ]);
                }
            } else {
                // Empty URL: deactivate external calendar
                \Eloquent\ExternalCalendar::where('church_id', $this->tid)
                    ->update(['active' => 0]);
            }
        }

       
        global $user;
        $this->church->log .= "\nMod: " . $user->login . " (" . date('Y-m-d H:i:s') . ")";
        
        /* Valamiért a writeAcess nem az igazi és mivel nincs a tálában ezért kiakadt...*/
        $this->church->save();

        switch ($this->input['modosit']) {
            case 'n':
                $this->redirect("/church/catalogue");
                break;

            case 't':
                $this->redirect("/church/" . $this->church->id);
                break;

            case 'm':
                $this->redirect("/church/" . $this->church->id . "/editschedule");
                break;

            default:
                break;
        }
    }

    function preparePage() {

        global $user;
        if ($user->checkRole('miserend')) {
            $this->addFormNewHolder();
        }
  

        $this->addFormAdministrative();
        $this->addFormReligiousAdministration();

        // Közeli templomok a kapcsolat hozzáadásához (max 20, koordináta alapján)
        if ($this->church->lat && $this->church->lon) {
            $lat = (float) $this->church->lat;
            $lon = (float) $this->church->lon;
            $this->nearbyChurches = \Eloquent\Church::select('templomok.*')
                ->addSelect(\Illuminate\Database\Capsule\Manager::raw(
                    "ST_distance_sphere(
                        ST_GeomFromText('POINT({$lat} {$lon})', 4326),
                        ST_GeomFromText(CONCAT('POINT(', lat, ' ', lon, ')'), 4326)
                    ) / 1000 as distance_km"
                ))
                ->where('ok', 'i')
                ->where('id', '!=', $this->tid)
                ->whereRaw('NOT (lat = 0 AND lon = 0)')
                ->orderBy('distance_km', 'ASC')
                ->limit(20)
                ->get();
        } else {
            $this->nearbyChurches = collect([]);
        }

        // Meglévő kapcsolatok betöltése (szülő irányban)
        $this->church->load('parentRelationships.parent');
        
        // Add external calendar URL field
        $this->form['external_calendar_url'] = [
            'type' => 'text',
            'name' => 'church[external_calendar_url]',
            'id' => 'external_calendar_url',
            'class' => 'form-control',
            'placeholder' => 'https://calendar.google.com/calendar/ical/...',
            'value' => $this->getExternalCalendarUrl(),
            'labelback' => 'Külső naptár (iCalendar ICS URL) - maximum 1'
        ];
    
  $this->form['misemegj'] = array(
			'class' => 'tinymce',
            'name' => 'church[misemegj]',
			'type' => 'textarea',
			'value' => $this->church->misemegj
        );
		
		$this->form['miseaktiv'] = array(
            'name' => 'church[miseaktiv]',
			'id' => 'miseaktiv',
			'type' => 'radio',
            'options' => array(
                '1' => 'Van rendszeresen mise',
                '0' => 'Nincs rendszeresen mise'
            ),
            'selected' => $this->church->miseaktiv
        );
		
		$this->form['ok'] = array(
            'name' => 'church[ok]',
            'options' => array(
                'i' => 'megjelenhet',
                'f' => 'áttekintésre vár',
                'n' => 'letiltva'
            ),
            'selected' => $this->church->ok
        );

		$this->form['frissites'] = array(
            'type' => 'checkbox',
            'name' => "church[frissites]",
            'value' => date('Y-m-d'),
            'checked' => false,
            'labelback' => 'Frissítsük a dátumot! (Utoljára frissítve: ' . date('Y.m.d.', strtotime($this->church->frissites)).')'
        );
				
        $this->title = $this->church->fullName;
		
        
        for($i = 1; $i < 60; $i++) {
            $help = new \Help($i);
            if($help)
                $this->help[$i] = $help->html;
        }
    }

    private function getExternalCalendarUrl() {
        $externalCal = \Eloquent\ExternalCalendar::where('church_id', $this->tid)
            ->where('active', 1)
            ->first();
        return $externalCal ? $externalCal->url : '';
    }
    
    function addFormAdministrative() {
        $options = [0 => 'Válassz/Nem tudom'];
        $countries = \Illuminate\Database\Capsule\Manager::table('orszagok')
                        ->select('id', 'nev')
                        ->orderBy('nev')->get();
        foreach ($countries as $selectibleCountry) {
            $options[$selectibleCountry->id] = $selectibleCountry->nev;
        }
        $this->form['country'] = array(
            'type' => 'select',
            'name' => 'church[orszag]',
            'id' => 'selectOrszag',
            'options' => $options,
            'selected' => $this->church->orszag
        );

        foreach ($countries as $selectibleCountry) {
            $options = [0 => 'Válassz/Nem tudom'];
            $counties = \Illuminate\Database\Capsule\Manager::table('megye')
                            ->select('id', 'megyenev', 'orszag')
                            ->where('orszag', $selectibleCountry->id)
                            ->orderBy('megyenev')->get();

            foreach ($counties as $selectibleCounty) {
                $options[$selectibleCounty->id] = $selectibleCounty->megyenev . " megye";
                $allCounties[] = $selectibleCounty;
            }

            $this->form['counties'][$selectibleCountry->id] = array(
                'type' => 'select',
                'name' => 'church[megye]',
                'id' => 'selectMegyeCountry' . $selectibleCountry->id,
                'class' => 'selectMegyeCountry',
                'data' => $selectibleCountry->id,
                'options' => $options,
                'selected' => $this->church->megye
            );
            if ($selectibleCountry->id == $this->church->orszag) {
                $this->form['counties'][$selectibleCountry->id]['style'] = 'display: inline';
            } else {
                $this->form['counties'][$selectibleCountry->id]['style'] = 'display: none';
                $this->form['counties'][$selectibleCountry->id]['disabled'] = 'disabled';
            }

            if (count($counties) < 1) {
                $extra = new \stdClass();
                $extra->id = 0;
                $extra->megyenev = '(Nincs megadva)';
                $extra->orszag = $selectibleCountry->id;
                $allCounties[] = $extra;
            }
        }

        foreach ($allCounties as $selectibleCounty) {
            $options = [0 => 'Válassz/Nem tudom'];
            $cities = \Illuminate\Database\Capsule\Manager::table('varosok')
                            ->select('id', 'nev')
                            ->where('orszag', $selectibleCounty->orszag)
                            ->where('megye_id', $selectibleCounty->id)
                            ->orderBy('nev')->get();
            foreach ($cities as $selectibleCity) {
                $options[$selectibleCity->nev] = $selectibleCity->nev;
            }
            $this->form['cities'][$selectibleCounty->orszag . "-" . $selectibleCounty->id] = array(
                'type' => 'select',
                'name' => 'church[varos]',
                'id' => 'selectVarosCounty' . $selectibleCounty->orszag . "-" . $selectibleCounty->id,
                'class' => 'selectVarosCounty',
                'options' => $options,
                'selected' => $this->church->varos
            );
            if ($selectibleCounty->id == $this->church->megye AND $this->church->orszag == $selectibleCounty->orszag) {
                $this->form['cities'][$selectibleCounty->orszag . "-" . $selectibleCounty->id]['style'] = 'display: inline';
            } else {
                $this->form['cities'][$selectibleCounty->orszag . "-" . $selectibleCounty->id]['style'] = 'display: none';
                $this->form['cities'][$selectibleCounty->orszag . "-" . $selectibleCounty->id]['disabled'] = 'disabled';
            }
        }
    }

    function addFormReligiousAdministration() {
        $selected = ['diocese' => $this->church->egyhazmegye, 'deanery' => $this->church->espereskerulet];
        $selectReligiousAdministration = \Form::religiousAdministrationSelection($selected);
        $this->form['dioceses'] = $selectReligiousAdministration['dioceses'];
        $this->form['deaneries'] = $selectReligiousAdministration['deaneries'];
    }

    function addFormNewHolder() {
        $options = [];
        $users = \Illuminate\Database\Capsule\Manager::table('user')
                        ->select('login', 'nev', 'uid')
                        ->orderByRaw("CASE WHEN lastlogin > '" . date('Y-m-d H:i:s', strtotime('-6 month')) . "'     THEN 1 ELSE 0 END desc")
                        ->orderBy('login')->get();
        foreach ($users as $selectibleUser) {
            $options[$selectibleUser->uid] = $selectibleUser->login." (".$selectibleUser->nev.")";
        }
        $this->form['holder_uid'] = array(
            'type' => 'select',
            'name' => 'uid',
            'id' => 'combobox',
            'options' => $options
        );
        $this->form['holder_decription'] = array(
            'type' => 'text',
            'name' => 'description',
            'placeholder' => 'Megjegyzés / jogosultság / kapcsolat a templommal.'
        );        
        $this->form['holder_access'] = array(
            'type' => 'hidden',
            'name' => 'access',
            'value'=> 'allowed'
        );        
    }

}
