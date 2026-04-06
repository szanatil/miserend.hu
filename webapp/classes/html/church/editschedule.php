<?php

namespace Html\Church;

use Illuminate\Database\Capsule\Manager as DB;

class EditSchedule extends \Html\Html {

    public function __construct($path) {
        $this->tid = $path[0];

        $this->church = \Eloquent\Church::find($this->tid)->append(['writeAccess']);;
        if (!$this->church) {
            throw new \Exception('Nincs ilyen templom.');
        }
        
        if (!$this->church->writeAccess) {
            throw new \Exception('Hiányzó jogosultság!');
            return;
        }

        // DIAGNOSTIC LOG: Check if the church has masses in ElasticSearch
        $search = new \Search('masses');
        $search->tids([$this->tid]);
        $search->dateRange(date('Y') . '-01-01', date('Y') + 1 . '-01-01');
        $results = $search->getResults(0, 10);
        $this->elasticMassesCount = $search->total;
        $this->elasticMassesExamples = $results;
                
        global $_tidsToWorkWith;
        $this->tids = $_tidsToWorkWith;
    }

}
