<?php

namespace Eloquent;

class Boundary extends \Illuminate\Database\Eloquent\Model {

    #protected $table = 'osmtags';
    protected $fillable = array('osmtype', 'osmid','boundary','denomination','admin_level','name');
    protected $appends = array('url', 'type', 'color');
    
    function getUrlAttribute($value) {
        return 'https://www.openstreetmap.org/'.$this->osmtype.'/'.$this->osmid;
    }
    
    public function getTypeAttribute() {
        return $this->generateType();
    }
    
    public function getColorAttribute() {
        return $this->generateColor();
    }
    
    /**
     * Centralized boundary definitions with name and color information
     */
    private function getBoundaryDefinitions() {
        return [
            'religious_administration' => [
                'greek_catholic' => [
                    6 => ['name' => 'egyházmegye', 'color' => '#9370DB'],
                    7 => ['name' => 'metropólia', 'color' => '#B19CD9'],  
                ],
                'roman_catholic' => [
                    5 => ['name' => 'érseki tartomány', 'color' => '#B19CD9'],
                    6 => ['name' => 'egyházmegye', 'color' => '#9370DB'],
                    7 => ['name' => 'espereskerület', 'color' => '#7B68EE'],
                ],
            ],
            'administrative' => [
                    2 => ['name' => 'ország', 'color' => '#D9534F'],
                    4 => ['name' => 'országrész', 'color' => '#6C8EBF'],
                    5 => ['name' => 'régió', 'color' => '#A4C2F4'],
                    6 => ['name' => 'vármegye', 'color' => '#7FB3D5'],
                    7 => ['name' => 'járás', 'color' => '#82B366'],
                    8 => ['name' => 'település', 'color' => '#5E8C61'],
                    9 => ['name' => 'kerület', 'color' => '#C9A876'],
                    10 => ['name' => 'városrész', 'color' => '#E8D4A8'],                    
            ],
            'postal_code' => [
                    'default' => ['name' => 'postai kód', 'color' => '#F0D966'],
            ],
        ];
    }
    
    /**
     * Get boundary info (name and color) from definitions
     */
    private function getBoundaryInfo() {
        $definitions = $this->getBoundaryDefinitions();
        $boundary = $this->boundary;
        $adminLevel = $this->admin_level;
        $denomination = $this->denomination;
        
        // Religious administration with denomination handling
        if ($boundary === 'religious_administration' && isset($definitions[$boundary][$denomination])) {
            if (isset($definitions[$boundary][$denomination][$adminLevel])) {
                return $definitions[$boundary][$denomination][$adminLevel];
            }                        
        }
        
        // Administrative boundaries
        if ($boundary === 'administrative' && isset($definitions[$boundary][$adminLevel])) {
            return $definitions[$boundary][$adminLevel];
        }
        
        // Postal code
        if ($boundary === 'postal_code') {
            return $definitions[$boundary]['default'] ?? ['name' => ucfirst($boundary), 'color' => '#9E9E9E'];
        }
        
        // Default fallback
        if($boundary === 'religious_administration') {
            return ['name' => ucfirst($boundary) . ' (' . $denomination . ')', 'color' => '#9E9E9E'];
        }


        return ['name' => ucfirst($boundary), 'color' => '#9E9E9E'];
    }
    
    private function generateType() {
        return $this->getBoundaryInfo()['name'];
    }
    
    private function generateColor() {
         return $this->getBoundaryInfo()['color'];
     }
     
     public function toSimpleArray() {
         return [
             'id' => $this->id,
             'name' => $this->name,
             'type' => $this->type,
             'color' => $this->color
         ];
     }
     
     public function churches()
    {
        return $this->belongsToMany('Eloquent\Church', 'lookup_boundary_church')
                ->withTimestamps();
    }
    
}
