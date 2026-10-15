<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TableSearch
{
    public static function term(Request $request): string
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return trim((string) $request->query('search', ''));
    }

    /**
     * @param  array<int, string>  $columns  Trusted column names defined by the application.
     */
    public static function apply(QueryBuilder|EloquentBuilder $query, string $term, array $columns): QueryBuilder|EloquentBuilder
    {
        if ($term === '' || $columns === []) {
            return $query;
        }

        $driver = DB::connection()->getDriverName();
        $castType = match ($driver) {
            'mysql' => 'CHAR',
            'sqlsrv' => 'VARCHAR(MAX)',
            default => 'TEXT',
        };
        $operator = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
        $pattern = '%'.$term.'%';

        return $query->where(function ($nested) use ($columns, $castType, $operator, $pattern): void {
            foreach ($columns as $column) {
                $nested->orWhereRaw("CAST({$column} AS {$castType}) {$operator} ?", [$pattern]);
            }
        });
    }
}
