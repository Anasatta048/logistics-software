<?php

namespace App\Http\Controllers\User;

use App\Enums\CommissionType;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function index(): View
    {
        return view('users.index', ['users' => $this->userService->paginate()]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => $this->userService->roles(),
            'statuses' => UserStatus::cases(),
            'commissionTypes' => CommissionType::cases(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name', 'mc_number']),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->userService->create($request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', "User {$user->name} created.");
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user->load('companies:id'),
            'roles' => $this->userService->roles(),
            'statuses' => UserStatus::cases(),
            'commissionTypes' => CommissionType::cases(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name', 'mc_number']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->update($user, $request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', "User {$user->name} updated.");
    }
}
