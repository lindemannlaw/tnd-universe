<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RolesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Manager\StoreRequest;
use App\Http\Requests\Admin\Manager\UpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ManagerController extends Controller
{
    public function index(): View {
        $user = auth()->user();
        $withoutForSuperAdmin = [RolesEnum::SUPERADMIN->value];
        $withoutForAny = [RolesEnum::SUPERADMIN->value, RolesEnum::ADMIN->value];
        $managers = User::withoutRole($user->hasRole(RolesEnum::SUPERADMIN->value) ? $withoutForSuperAdmin : $withoutForAny)->get();

        return view('admin.managers.index', compact('managers'));
    }

    public function create(): View {
        $roles = $this->getFilteredRoles();

        return view('admin.managers.create', compact('roles'));
    }

    public function store(StoreRequest $request): RedirectResponse {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $manager = User::create($data);

            if ($request->hasFile('avatar')) {
                $manager->addMediaFromRequest('avatar')
                    ->toMediaCollection($manager->mediaCollection);
            }

            $manager->assignRole($request->role);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        return redirect()->route('admin.manager')->with('success', __('texts.successCreateManager', ['name' => $manager->first_name . ' ' . $manager->last_name]));
    }

    public function edit(User $user): View {
        $manager = $user;
        $roles = $this->getFilteredRoles();

        return view('admin.managers.edit', compact('manager', 'roles'));
    }

    public function update(UpdateRequest $request, User $user): RedirectResponse {
        $data = $request->validated();
        $manager = $user;

        if(empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($request->password);
        }

        try {
            DB::beginTransaction();

            if ($request->hasFile('avatar')) {
                $user->clearMediaCollection($user->mediaCollection);

                $user->addMediaFromRequest('avatar')
                    ->toMediaCollection($user->mediaCollection);
            }

            $manager->syncRoles($request->role);

            $manager->updateOrFail($data);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        return redirect()->route('admin.manager')->with('success', __('texts.successUpdatedManager', ['name' => $manager->first_name . ' ' . $manager->last_name]));
    }

    public function delete(User $user): RedirectResponse {
        $manager = $user;

        try {
            DB::beginTransaction();

            $manager->deleteOrFail();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        return redirect()->back()->with('success', __('texts.successDeleteManager', ['name' => $manager->first_name . ' ' . $manager->last_name]));
    }

    public function getFilteredRoles(): array {
        $roleCases = RolesEnum::cases();

        $roleArray = array_combine(
            array_map(fn($role) => $role->value, $roleCases),
            array_map(fn($role) => $role->label(), $roleCases)
        );

        $roles = array_filter($roleArray, function ($key) {
            $user = auth()->user();

            if ($user->hasRole(RolesEnum::SUPERADMIN->value)) {
                return $key !== 'super-admin';
            } elseif ($user->hasRole(RolesEnum::ADMIN->value)) {
                return $key !== 'super-admin' && $key !== 'admin';
            }
        }, ARRAY_FILTER_USE_KEY);

        return $roles;
    }
}
