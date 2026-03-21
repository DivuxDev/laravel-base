<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Traits\Filterable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    use Filterable;

    /**
     * GET /api/admin/audit-logs
     * Returns paginated audit logs with optional filtering.
     *
     * Query params:
     *   search    – filters by action, user name, or user email
     *   from      – ISO date string, lower bound on created_at
     *   to        – ISO date string, upper bound on created_at
     *   sort      – column to sort by (action, created_at)
     *   sort_dir  – asc | desc  (default: desc)
     *   per_page  – rows per page, 1–100 (default 15)
     *   page      – page number (default 1)
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user');

        // Date range filter
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        // Search by action OR user name / email (via relationship)
        $search = $request->string('search')->trim()->value();
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('action', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($uq) use ($search): void {
                      $uq->where('name', 'like', '%' . $search . '%')
                         ->orWhere('email', 'like', '%' . $search . '%');
                  });
            });
        }

        // Sorting (pass empty searchable so the search block is skipped inside applyFilters)
        $sortable = ['action', 'created_at'];
        $sortCol  = $request->string('sort')->trim()->value();
        $sortDir  = strtolower($request->string('sort_dir', 'desc')->trim()->value());
        $sortDir  = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc';

        if ($sortCol !== '' && in_array($sortCol, $sortable, true)) {
            $query->orderBy($sortCol, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage    = $this->resolvePerPage($request);
        $paginated  = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'audit_logs' => AuditLogResource::collection($paginated->items()),
                'meta'       => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ],
            'message' => 'Audit logs retrieved successfully.',
        ]);
    }
}
