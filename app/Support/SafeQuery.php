<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SafeQuery
{
    /**
     * Match a comma-separated column the way MySQL FIND_IN_SET does,
     * with a bound value. Other drivers use equivalent LIKE checks so
     * the same query can run in tests.
     */
    public static function orFindInSet($query, $column, $value)
    {
        $column = preg_replace('/[^A-Za-z0-9_]/', '', (string) $column);
        $value  = (string) $value;

        if(DB::connection()->getDriverName() === 'mysql')
        {
            return $query->orWhereRaw('FIND_IN_SET(?, ' . $column . ')', [$value]);
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);

        return $query->orWhere(function ($inner) use ($column, $value, $escaped){
            $inner->where($column, $value)
                ->orWhere($column, 'LIKE', $escaped . ',%')
                ->orWhere($column, 'LIKE', '%,' . $escaped)
                ->orWhere($column, 'LIKE', '%,' . $escaped . ',%');
        });
    }
}
