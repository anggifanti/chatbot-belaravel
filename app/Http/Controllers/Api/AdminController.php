<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private AdminService $adminService
    ) {}

    /**
     * Get admin dashboard overview stats
     */
    public function getDashboardStats(): JsonResponse
    {
        try {
            $stats = $this->adminService->getDashboardStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all users for admin panel
     */
    public function getUsers(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'is_premium' => $request->get('is_premium'),
            ];

            $perPage = $request->get('per_page', 15);
            $users = $this->adminService->getUsers($filters, $perPage);

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($users),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed chat statistics
     */
    public function getChatStats(): JsonResponse
    {
        try {
            $stats = $this->adminService->getChatStats();

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve chat statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific user details
     */
    public function getUserDetails(Request $request, int $userId): JsonResponse
    {
        try {
            $userDetails = $this->adminService->getUserDetails($userId);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => new UserResource($userDetails['user']),
                    'recent_conversations' => $userDetails['recent_conversations'],
                    'message_activity' => $userDetails['message_activity'],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user details',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
