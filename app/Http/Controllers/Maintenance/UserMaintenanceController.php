<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StoreUserRequest;
use App\Http\Requests\Maintenance\UpdateUserRequest;
use App\Models\User;
use App\Services\UserMaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserMaintenanceController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected UserMaintenanceService $userMaintenanceService
    ) {}

    /**
     * Display a paginated, searchable list of users with tab filtering.
     */
    public function index(Request $request): Response
    {
        $tab = $request->input('tab', 'students');
        if ($tab !== 'employees') {
            $tab = 'students';
        }

        $search = $request->input('search', '');
        $perPage = max(1, min((int) $request->input('per_page', 10), 100));

        $users = $this->userMaintenanceService->getPaginatedUsers($tab, $search, $perPage);
        $users->withQueryString();

        return Inertia::render('user-maintenance/users', [
            'users' => $users,
            'tab' => $tab,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Return a single user's full details as JSON for the view modal.
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['studentDetail', 'employeeDetail']);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'rfid' => $user->rfid,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $user->profile_image,
                'status' => $user->status,
                'created_at' => $user->created_at?->toDateTimeString(),
                'updated_at' => $user->updated_at?->toDateTimeString(),
                'student_detail' => $user->studentDetail?->only([
                    'id', 'id_number', 'level', 'section',
                    'guardian_name', 'guardian_contact_number',
                ]),
                'employee_detail' => $user->employeeDetail?->only([
                    'id', 'employee_id', 'employee_role',
                ]),
            ],
        ]);
    }

    /**
     * Create a new user and optionally sync student/employee details.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userMaintenanceService->createUser($request->validated());

        return redirect()->back()->with('success', 'User created successfully.');
    }

    /**
     * Update an existing user and their student/employee details.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userMaintenanceService->updateUser($user, $request->validated());

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    /**
     * Soft-delete a user.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->userMaintenanceService->deleteUser($user);

        return redirect()->back()->with('success', 'User deleted successfully.');
    }
}
