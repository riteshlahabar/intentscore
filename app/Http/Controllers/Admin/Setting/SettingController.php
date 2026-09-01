<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\Setting\Setting;
use App\Services\Common\UploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private readonly UploadService $upload)
    {
    }

    public function index(): View
    {
        return view('admin.settings.index', [
            'settings' => Setting::pluck('value', 'key'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:180'],
            'company_tagline' => ['nullable', 'string', 'max:255'],
            'company_email' => ['nullable', 'email', 'max:180'],
            'company_phone' => ['nullable', 'string', 'max:30'],
            'company_whatsapp' => ['nullable', 'string', 'max:30'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'company_about' => ['nullable', 'string', 'max:5000'],
            'privacy_notice' => ['nullable', 'string', 'max:10000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        foreach ($data as $key => $value) {
            if ($key === 'logo') {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['group' => 'company', 'value' => $value]
            );
        }

        if ($request->hasFile('logo')) {
            $oldLogo = Setting::where('key', 'company_logo')->value('value');
            $this->upload->delete($oldLogo);

            $path = $this->upload->store($request->file('logo'), 'company');

            Setting::updateOrCreate(
                ['key' => 'company_logo'],
                ['group' => 'company', 'value' => $path]
            );
        }

        return back()->with('success', 'Settings updated.');
    }
}
