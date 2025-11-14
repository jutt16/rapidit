<?php

namespace App\Services;

use App\Models\Zone;
use Illuminate\Validation\ValidationException;

class ZoneCoverageService
{
    /**
     * Validate that the coordinates sit inside an active zone.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function assertWithinActiveZone(?float $latitude, ?float $longitude, string $attribute = 'latitude'): Zone
    {
        if ($latitude === null || $longitude === null) {
            throw ValidationException::withMessages([
                $attribute => ['Latitude and longitude are required to validate service zones.'],
            ]);
        }

        $zone = $this->findZoneCoveringPoint($latitude, $longitude);

        if (!$zone) {
            throw ValidationException::withMessages([
                $attribute => ['Selected location is outside of our service zones. Please choose a location within an active zone.'],
            ]);
        }

        return $zone;
    }

    public function findZoneCoveringPoint(float $latitude, float $longitude): ?Zone
    {
        return Zone::query()
            ->where('is_active', true)
            ->get(['id', 'coordinates'])
            ->first(function (Zone $zone) use ($latitude, $longitude) {
                $coordinates = $zone->coordinates ?? [];

                if (!is_array($coordinates) || count($coordinates) < 3) {
                    return false;
                }

                return $this->isPointWithinPolygon($latitude, $longitude, $coordinates);
            });
    }

    /**
     * Ray-casting algorithm to check if a point lies inside a polygon.
     *
     * @param  array<int, array{lat: float, lng: float}>  $polygon
     */
    protected function isPointWithinPolygon(float $latitude, float $longitude, array $polygon): bool
    {
        $inside = false;
        $points = count($polygon);
        $x = $longitude;
        $y = $latitude;

        for ($i = 0, $j = $points - 1; $i < $points; $j = $i++) {
            $xi = (float) ($polygon[$i]['lng'] ?? 0);
            $yi = (float) ($polygon[$i]['lat'] ?? 0);
            $xj = (float) ($polygon[$j]['lng'] ?? 0);
            $yj = (float) ($polygon[$j]['lat'] ?? 0);

            $intersect = (($yi > $y) !== ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / (($yj - $yi) ?: 1e-9) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}

