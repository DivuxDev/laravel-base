<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * GET /api/admin/users
     * Lista todos los usuarios ordenados por fecha de creación.
     */
    public function index(): JsonResponse
    {
        $users = User::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => ['users' => UserResource::collection($users)],
            'message' => 'Users retrieved successfully.',
        ]);
    }

    /**
     * PATCH /api/admin/users/{id}/role
     * Cambia el rol de un usuario.
     * Un admin no puede quitarse su propio rol.
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

        $user = User::findOrFail($id);
        $user->update(['role' => $request->role]);

        return response()->json([
            'success' => true,
            'data'    => ['user' => new UserResource($user)],
            'message' => 'Role updated successfully.',
        ]);
    }

    /**
     * POST /api/admin/users/{id}/reset-password
     * Genera y establece una contraseña aleatoria para el usuario.
     * Devuelve la nueva contraseña en texto plano (solo en esta respuesta).
     */
    public function resetPassword(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $newPassword = Str::random(12);

        $user->update(['password' => bcrypt($newPassword)]);

        // Revocar todos los tokens del usuario (forzar re-login)
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'data'    => ['new_password' => $newPassword],
            'message' => 'Password reset successfully.',
        ]);
    }

    /**
     * DELETE /api/admin/users/{id}
     * Elimina un usuario. Un admin no puede eliminarse a sí mismo.
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
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'User deleted successfully.',
        ]);
    }
}
