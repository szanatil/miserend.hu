<?php

namespace Eloquent;

/**
 * ChurchRelationship – kapcsolat két misézőhely között.
 *
 * A modell kizárólag angol kulcsszavakat kezel.
 * A fordítás (t()) kizárólag a Twig sablonokban és JS scriptekben történik.
 *
 * Tábla: church_relationships
 * Mezők: id, parent_church_id, child_church_id, type, created_at, updated_at
 */
class ChurchRelationship extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'church_relationships';

    protected $fillable = ['parent_church_id', 'child_church_id', 'type'];

    /**
     * A felsőbbrendű misézőhely (szülő).
     */
    public function parent() {
        return $this->belongsTo(Church::class, 'parent_church_id');
    }

    /**
     * Az alsóbbrendű misézőhely (gyerek).
     */
    public function child() {
        return $this->belongsTo(Church::class, 'child_church_id');
    }

    /**
     * Érvényes kapcsolat típus kulcsok (angol, DB enum értékek).
     */
    public static function validTypes(): array {
        return ['subordinate', 'associated', 'territorially_independent'];
    }

    /**
     * Érvényes rang kulcsok (angol, DB enum értékek).
     */
    public static function validRanks(): array {
        return ['parish', 'auxiliary', 'filial', 'rectoral'];
    }
}
