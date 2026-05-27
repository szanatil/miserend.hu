<?php


namespace Html;
//use Illuminate\Database\DatabaseManager as DB;
//use \Illuminate\Support\Facades\DB as DB;
use Illuminate\Database\Capsule\Manager as DB;

class Home extends Html {
    public $photo;
    public $favorites;
    public $searchform;
    public $admindashboard;
    public $boundaryDataJson;

    public function __construct() {
        global $user, $config;

        $espkers = DB::table('espereskerulet')
                    ->select('id','ehm','nev')
                    ->get();
            
        foreach ($espkers as $espker) {
            $espkerT[$espker->ehm][$espker->id] = $espker->nev;# code...
        }
    
        //MISEREND űRLAP
       
        $boundaryIds =  \Request::StringArray('boundaries', []);                
        if (!empty($boundaryIds)) {
            $this->boundaryDataJson = json_encode(\Eloquent\Boundary::whereIn('id', $boundaryIds)->get()->map->toSimpleArray());
        }
         

        $searchform = array(
            'kulcsszo' => array(
                'name' => "kulcsszo",
                'id' => 'keyword',
                'value' => \Request::Text('kulcsszo'),
                'size' => 20,
                'class' => 'keresourlap',
                'placeholder' => 'név, település, kulcsszó'),           
            'hely' => array(
                'name' => "hely",
                'size' => 20,
                'id' => 'varos',
                'class' => 'keresourlap',
                'value' => \Request::Text('hely')
            ),
            'tavolsag' => array(
                'name' => "tavolsag",
                'size' => 1,
                'id' => 'tavolsag',
                'class' => 'keresourlap',
                'value' => \Request::IntegerwDefault('tavolsag',4)
            )
        );
        
        $searchform['ehm'] = array(
            'name' => "ehm",
            'selected' => \Request::IntegerwDefault('ehm', 0),
            'class' => 'keresourlap'
        );
        $searchform['ehm']['options'][0] = 'mindegy';
        
        $egyhmegyes = DB::table('egyhazmegye')
                    ->select('id','nev')
                    ->where('ok','i')
                    ->orderBy('sorrend')
                    ->get();
                    foreach ($egyhmegyes as $egyhmegye) {
                        $searchform['ehm']['options'][$egyhmegye->id] = $egyhmegye->nev;
                    }                               
        
        // Összegyűjtés: hányféle calmass.lang van az eloquent-ben, csökkenő sorrend szerint
        $langStats = \Eloquent\CalMass::select('lang')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('lang')
            ->where('lang', '!=', '')
            ->groupBy('lang')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('lang')
            ->toArray();
        $this->langs = $langStats;
        
        $this->photo = \Eloquent\Photo::big()->vertical()->where('flag', 'i')->orderbyRaw('RAND()')->first();
        if($this->photo->church) //TODO: Van, hogy a random képhez nem is tartozik templom. Valami régi hiba miatt.
            $this->photo->church->location;

        $this->favorites = $user->getFavorites();
        $this->searchform = $searchform;
        
        // Load rites and categories from mass-definitions.json
        $massDefinitionsPath = PATH . 'mass-definitions.json';
        if (file_exists($massDefinitionsPath)) {
            $massDefinitionsJson = file_get_contents($massDefinitionsPath);
            $massDefinitionsData = json_decode($massDefinitionsJson, true);
            if (isset($massDefinitionsData['rites']) && is_array($massDefinitionsData['rites'])) {
                $this->rites = $massDefinitionsData['rites'];
            } else {
                $this->rites = [];
            }
            if (isset($massDefinitionsData['categories']) && is_array($massDefinitionsData['categories'])) {
                $this->categories = $massDefinitionsData['categories'];
            } else {
                $this->categories = [];
            }
        } else {
            $this->rites = [];
            $this->categories = [];
        }
		try {
            $this->alert = (new \ExternalApi\NapilelkibatyuApi())->liturgicalAlert();            
        } catch (\Exception $e) {
            addMessage('Nem sikerült a Napi Lelki Batyuból megtudni, hogy van-e ma különleges ünnep.', "warning");
        }
		
							
		// Adminok számára "dashboard"
		if ( $user->checkrole('miserend') ) {
		
			$this->admindashboard = [];
		
			$this->admindashboard['holders'] = \Eloquent\ChurchHolder::where('updated_at', '>', $user->lastlogin)
                             ->orWhere('status', 'asked')
                             ->orderBy('created_at', 'asc')
                             ->get();

            $this->admindashboard['suggestion_packages'] = \Eloquent\CalSuggestionPackage::where('updated_at', '>', $user->lastlogin)
                             ->orWhere('state', 'PENDING')
                             ->orderBy('created_at', 'asc')
                             ->get();
                                              
		}
        
    }

}
