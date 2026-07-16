<?php

namespace Eloquent;

class Cron extends \Illuminate\Database\Eloquent\Model {

    protected $fillable = array('class', 'function');

    public function success() {
        $this->attempts = 0;
        $this->lastsuccess_at = date('Y-m-d H:i:s');
        $this->renewDeadline();
        $this->save();
    }

    public function renewDeadline() {
        $this->deadline_at = date('Y-m-d H:i:s', strtotime('now +' . $this->frequency));
    }

    public function scopeNextJobs($query) {
        return $query->where('deadline_at', '<', date('Y-m-d H:i:s'))
                        ->where(function($query) {
                            $query->where('attempts', '<', 10)
                            ->orWhere('updated_at', '<', date('Y-m-d H:i:s', strtotime('-12 hour')));
                        })
                        ->orderBy('attempts', 'ASC')->orderBy('deadline_at', 'ASC');
    }

    public function initialize() {
       return true;
    }
    
    function run() {
        $className = $this->class;
        $functionName = $this->function;
        if (class_exists($className)) {
            $object = new $className();
        } else {
            throw new \Exception("Class '$className' does not exists.");
        }
        if (method_exists($object, $functionName)) {
            $object->$functionName();
        } else {
            throw new \Exception("Function " . $className . "->" . $functionName . "() does not exists.");
        }
        $this->success();
    }

}
