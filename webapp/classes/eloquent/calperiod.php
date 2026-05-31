<?php
namespace Eloquent;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * @property string $name
 * @property int $weight
 * @property string $start_month_day
 * @property string $end_month_day
 * @property int $start_period_id
 * @property int $end_period_id
 * @property bool $all_inclusive
 * @property bool $multi_day
 * @property string $color
 */
class CalPeriod extends CalModel
{
    protected $table = 'cal_periods';

    protected $fillable = [
        'name', 'weight', 'start_month_day', 'end_month_day', 'start_period_id', 'end_period_id', 'all_inclusive', 'multi_day', 'color'
    ];

    protected $casts = [
        'name' => 'string',
        'weight' => 'integer',
        'start_month_day' => 'string',
        'end_month_day' => 'string',
        'start_period_id' => 'integer',
        'end_period_id' => 'integer',
        'all_inclusive' => 'boolean',
        'multi_day' => 'boolean',
        'color' => 'string',
    ];

    public function generatedPeriods(): HasMany
    {
        return $this->hasMany(CalGeneratedPeriod::class, 'period_id');
    }

    public function startPeriod(): BelongsTo
    {
        return $this->belongsTo(CalPeriod::class, 'start_period_id');
    }

    public function endPeriod(): BelongsTo
    {
        return $this->belongsTo(CalPeriod::class, 'end_period_id');
    }

    static public function generateCalGeneratedPeriods(int $year): void
    {
        CalGeneratedPeriod::whereYear('start_date', $year)->delete();

        $generated = [];

        $years = CalPeriodYear::whereIn('start_year', [$year, $year + 1])->get()->groupBy('start_year')->map->keyBy('period_id');
        $allPeriods = CalPeriod::all()->keyBy('id');

        // #304: a 4 kombinációs ágat (start_month_day vs start_period_id × end_month_day vs end_period_id)
        // egyetlen, egységesített body kezeli. A start- és end-dátumokat self::resolveStartDate /
        // resolveEndDate-tel oldjuk fel, így a (start_period_id + end_month_day) és a
        // (start_month_day + end_period_id) kombinációk is jól működnek.
        $linkedOrFixedPeriods = CalPeriod::where(function ($q) {
            $q->whereNotNull('start_month_day')->orWhereNotNull('start_period_id');
        })->get();

        foreach ($linkedOrFixedPeriods as $period) {
            $startDate = self::resolveStartDate($period, $year, $years, $allPeriods);
            if (!$startDate) {
                continue;
            }

            $endDate = self::resolveEndDate($period, $startDate, $year, $years, $allPeriods);
            if (!$endDate) {
                continue;
            }

            if ($endDate->equalTo($startDate) || $period->all_inclusive) {
                $endDate->addDay();
            }

            $generated[] = [
                'period_id' => $period->id,
                'name' => $period->name,
                'weight' => $period->weight,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'color' => $period->color,
            ];
        }

        // Ha nem fix minden évben, és nem másik időszaktól függenek
        $periodsWithYearData = CalPeriod::whereNull('start_month_day')
            ->whereNull('start_period_id')
            ->whereNull('end_period_id')
            ->get();

        foreach ($periodsWithYearData as $period) {
            $yearData = $years[$year][$period->id] ?? null;

            if (!$yearData) {
                throw new \Exception("Hiányzó CalPeriodYear adat (period_id: {$period->id}, év: $year)");
            }

            try {
                $startDate = Carbon::parse($yearData->start_date);
            } catch (\Exception) {
                throw new \Exception("Érvénytelen start_date a CalPeriodYear-ben (period_id: {$period->id})");
            }

            if ($yearData->end_date) {
                $endDate = Carbon::parse($yearData->end_date);

                // Ha end_date < start_date → nézzük a következő évet
                if ($endDate->lt($startDate)) {
                    $nextYearData = $years[$year + 1][$period->id] ?? null;
                    if (!$nextYearData || !$nextYearData->end_date) {
                        continue; // skip, ha nincs értelmes end_date
                    }
                    $endDate = Carbon::parse($nextYearData->end_date);
                }
            } else {
                $endDate = (clone $startDate)->addDay();
            }

            if ($endDate->equalTo($startDate)) {
                $endDate->addDay();
            }

            $generated[] = [
                'period_id' => $period->id,
                'name' => $period->name,
                'weight' => $period->weight,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'color' => $period->color,
            ];
        }

        // Tömeges beszúrás
        if (!empty($generated)) {
            foreach (array_chunk($generated, 1000) as $chunk) {
                CalGeneratedPeriod::insert($chunk);
            }
        }
    }

    /**
     * Kiszámolja a start-dátumot egyetlen periódusra. A három forrás közül egyet használ:
     *   - start_month_day (fix MM-DD az aktuális évben), VAGY
     *   - start_period_id (egy másik periódus start-dátumából származtatva), VAGY
     *   - null (a hívó CalPeriodYear-ágra esik).
     * Visszatérési érték `null`, ha a kapcsolódó periódus év-adata hiányzik (kihagyás).
     */
    private static function resolveStartDate(CalPeriod $period, int $year, $years, $allPeriods): ?Carbon
    {
        if ($period->start_month_day) {
            try {
                return Carbon::createFromFormat('Y-m-d', "$year-" . $period->start_month_day);
            } catch (\Exception) {
                throw new \Exception("Hibás start_month_day formátum: {$period->start_month_day}");
            }
        }

        if ($period->start_period_id) {
            $startSource = $allPeriods[$period->start_period_id] ?? null;
            if (!$startSource) {
                throw new \Exception("Hiányzó start_period for CalPeriod {$period->id}");
            }
            return self::resolveReferencedDate($startSource, 'start', $year, $years);
        }

        return null;
    }

    /**
     * Kiszámolja a end-dátumot egyetlen periódusra. Három forrás:
     *   - end_month_day (fix MM-DD), VAGY
     *   - end_period_id (másik periódus start-dátumából — ami a referált periódus kezdete a vég itt), VAGY
     *   - null → addDay() a startDate-hez.
     * Ha a számolt endDate < startDate (éven átívelő), addYear()-rel javítjuk.
     * Visszatérés `null`, ha a kapcsolódó periódus év-adata hiányzik.
     */
    private static function resolveEndDate(CalPeriod $period, Carbon $startDate, int $year, $years, $allPeriods): ?Carbon
    {
        if ($period->end_month_day) {
            try {
                $endDate = Carbon::createFromFormat('Y-m-d', "$year-" . $period->end_month_day);
            } catch (\Exception) {
                throw new \Exception("Hibás end_month_day formátum: {$period->end_month_day}");
            }
            if ($endDate->lt($startDate)) {
                $endDate->addYear();
            }
            return $endDate;
        }

        if ($period->end_period_id) {
            $endSource = $allPeriods[$period->end_period_id] ?? null;
            if (!$endSource) {
                throw new \Exception("Hiányzó end_period for CalPeriod {$period->id}");
            }
            $endDate = self::resolveReferencedDate($endSource, 'end', $year, $years, $startDate);
            if ($endDate && $endDate->lt($startDate)) {
                $endDate->addYear();
            }
            return $endDate;
        }

        return (clone $startDate)->addDay();
    }

    /**
     * Megoldja egy referált periódus dátumát. A referált periódus saját start_month_day-ja
     * (vagy CalPeriodYear-startDate-ja) a forrás. Ha a referált periódusra az adott évre
     * nincs adat, megpróbáljuk a következő évet. Visszatérés `null` ha sehol nincs adat.
     *
     * A `$which` jelzi hogy a referált periódus melyik dátumát akarjuk:
     *   - 'start' → mindig a referált periódus start-dátumát
     *   - 'end' → szintén a referált periódus start-dátumát (megfelel a régi viselkedésnek)
     */
    private static function resolveReferencedDate(CalPeriod $source, string $which, int $year, $years, ?Carbon $referenceStart = null): ?Carbon
    {
        if ($source->start_month_day) {
            return Carbon::createFromFormat('Y-m-d', "$year-" . $source->start_month_day);
        }

        $yearData = $years[$year][$source->id] ?? null;
        if ($which === 'end' && $referenceStart && $yearData && $yearData->start_date) {
            $candidate = Carbon::parse($yearData->start_date);
            if (!$candidate->lt($referenceStart)) {
                return $candidate;
            }
        } elseif ($yearData && $yearData->start_date) {
            return Carbon::parse($yearData->start_date);
        }

        $nextYearData = $years[$year + 1][$source->id] ?? null;
        if ($nextYearData && $nextYearData->start_date) {
            return Carbon::parse($nextYearData->start_date);
        }

        return null;
    }
}
