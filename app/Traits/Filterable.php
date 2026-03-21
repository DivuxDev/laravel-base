<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    /**
     * Apply search, sort, and pagination filters to a query.
     *
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  Request  $request
     * @param  list<string>  $searchable  Columns to search across
     * @param  list<string>  $sortable    Columns allowed for sorting
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    protected function applyFilters(
        Builder $query,
        Request $request,
        array $searchable = [],
        array $sortable = [],
    ): Builder {
        // Search
        $search = $request->string('search')->trim()->value();
        if ($search !== '' && count($searchable) > 0) {
            $query->where(function (Builder $q) use ($search, $searchable): void {
                foreach ($searchable as $column) {
                    $q->orWhere($column, 'like', '%' . $search . '%');
                }
            });
        }

        // Sort
        $sortColumn  = $request->string('sort')->trim()->value();
        $sortDir     = strtolower($request->string('sort_dir', 'asc')->trim()->value());
        $sortDir     = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc';

        if ($sortColumn !== '' && in_array($sortColumn, $sortable, true)) {
            $query->orderBy($sortColumn, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }

    /**
     * Resolve per_page value from the request, clamped between 1 and 100.
     */
    protected function resolvePerPage(Request $request, int $default = 15): int
    {
        $perPage = (int) $request->input('per_page', $default);

        return max(1, min(100, $perPage));
    }
}
