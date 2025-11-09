<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidEwktLineString implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid EWKT LineString.');

            return;
        }

        if (! preg_match('/^SRID=\d+;LINESTRING\(/', $value)) {
            $fail('The :attribute must be a valid EWKT LineString format (SRID=4326;LINESTRING(...)).');

            return;
        }

        try {
            $result = DB::selectOne(
                'SELECT ST_IsValid(ST_GeomFromEWKT(?)) as is_valid',
                [$value]
            );

            if (! $result || ! $result->is_valid) {
                $fail('The :attribute is not a valid PostGIS geometry.');

                return;
            }

            $geometryType = DB::selectOne(
                'SELECT ST_GeometryType(ST_GeomFromEWKT(?)) as geom_type',
                [$value]
            );

            if (! $geometryType || $geometryType->geom_type !== 'ST_LineString') {
                $fail('The :attribute must be a LineString geometry, not '.$geometryType->geom_type.'.');

                return;
            }
        } catch (Exception $e) {
            $fail('The :attribute contains invalid PostGIS geometry: '.$e->getMessage());
        }
    }
}
