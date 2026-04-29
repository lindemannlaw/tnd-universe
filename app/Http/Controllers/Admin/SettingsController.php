<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View {
        $settingEmail = Setting::where('name', 'email')->first();

        return view('admin.settings.index', compact('settingEmail'));
    }

    public function updateEmail(\App\Http\Requests\Admin\Settings\Email\UpdateRequest $request, Setting $setting): RedirectResponse {
        $data = $request->validated();
        $data['data'] = [
            'email' => $request->input('email'),
        ];

        try {
            DB::beginTransaction();

            $setting->updateOrFail($data);

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            report($exception);
            return redirect()->back()->with('error', __('errors.general'));
        }

        return redirect()
            ->back()
            ->with('success', __('texts.successUpdateSetting', ['name' => __('buttons.email')]));
    }
}
