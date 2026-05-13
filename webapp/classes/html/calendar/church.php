<?php
namespace html\calendar;

use ExternalApi\ElasticsearchApi;
use Eloquent\CalMass;
use RRule\RRule;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class Church extends \Html\Calendar\CalendarApi {

    protected $elastic;
    public $tid;
    public $church;

    public function __construct($path) {

        if (empty($path[0])) {
            $this->sendJsonError('Hiányzó templom azonosító.', 400);
            exit;
        }

        $this->tid = $path[0];

        $this->church = \Eloquent\Church::find($this->tid);
        if (!$this->church) {
            $this->sendJsonError('Nincs ilyen templom.', 404);
            exit;
        }

        $this->elastic = new ElasticsearchApi();

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'OPTIONS':
                http_response_code(200);
                exit();
            case 'GET':
                // Append external calendar info
                $this->church->append(['hasExternalCalendar']);
                $confessions = $this->church->getConfessions('-40 days', '20 hours');
                $c = 1;
                foreach ($confessions as &$confession) {
                    $confession['startDate'] = date('Y-m-d\TH:i:s', strtotime($confession['start'])); // TODO timezone kérdések
                    unset($confession['start']);
                    unset($confession['end']);
                    $confession['churchId'] = $this->church->id;
                    $confession['periodId'] = null;
                    $confession['title'] = 'Gyóntatás';
                    $confession['types'] = [];
                    $confession['rite'] = null;
                    $confession['id'] = 'confession_' . $c;
                    $c++;   
                }
                
                $response = [
                    'id' => $this->tid,
                    'name' => $this->church->nev,
                    'rite' => strtoupper($this->church->denomination),
                    'timeZone' => 'Europe/Budapest',
                    'hasExternalCalendar' => $this->church->hasExternalCalendar,
                    'eventsFromSensor' => $confessions,
                    'sensorEvents' => $confessions,
                    'masses' => $this->getEventsByChurchId($this->tid)
                    
                ];
                $this->content = json_encode($response);
                break;
            default:
                $this->sendJsonError('Method not allowed', 405);
        }
    }

    public function getEventsByChurchId(int $churchId): array {
        return CalMass::where('church_id', $churchId)->get()->toArray();
    }

    

}
