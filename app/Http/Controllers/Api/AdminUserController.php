<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use App\Traits\Filterable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    use Filterable;

    /** Columns that can be searched via ?search= */
    private const SEARCHABLE = ['name', 'email'];

    /** Columns that can be sorted via ?sort= */
    private const SORTABLE = ['name', 'email', 'role', 'created_at'];

    /**
     * GET /api/admin/users
     * Returns paginated, filterable list of users.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->applyFilters(
            User::query(),
            $request,
            self::SEARCHABLE,
            self::SORTABLE,
        );

        $perPage   = $this->resolvePerPage($request);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'users' => UserResource::collection($paginated->items()),
                'meta'  => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ],
            'message' => 'Users retrieved successfully.',
        ]);
    }

    /**
     * PATCH /api/admin/users/{id}/role
     * Change a user's role. An admin cannot change their own role.
     */
    public function changeRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role' => ['required', Rule::in(['admin', 'user'])],
        ]);

        /** @var \App\Models\User $authUser */
        $authUser = $request->user();

        if ($authUser->id === $id) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'You cannot change your own role.',
            ], 422);
        }

        $user    = User::findOrFail($id);
        $oldRole = $user->role;

        $user->update(['role' => $request->role]);

        AuditService::log('admin.user.role_changed', [
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
            'old_values'     => ['role' => $oldRole],
            'new_values'     => ['role' => $request->role],
        ]);

        return response()->json([
            'success' => true,
            'data'    => ['user' => new UserResource($user)],
            'message' => 'Role updated successfully.',
        ]);
    }

    /**
     * POST /api/admin/users/{id}/reset-password
     * Generate and set a random password for the user.
     * Returns the new plain-text password (only in this response).
     */
    public function resetPassword(int $id): JsonResponse
    {
        $user        = User::findOrFail($id);
        $newPassword = Str::random(12);

        $user->update(['password' => bcrypt($newPassword)]);

        // Revoke all tokens to force re-login with new password
        $user->tokens()->delete();

        AuditService::log('admin.user.password_reset', [
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'data'    => ['new_password' => $newPassword],
            'message' => 'Password reset successfully.',
        ]);
    }

    /**
     * DELETE /api/admin/users/{id}
     * Delete a user. An admin cannot delete themselves.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = $request->user();

        if ($authUser->id === $id) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'You cannot delete your own account.',
            ], 422);
        }

        $user = User::findOrFail($id);

        AuditService::log('admin.user.deleted', [
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
            'old_values'     => [
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'User deleted successfully.',
        ]);
    }
}
