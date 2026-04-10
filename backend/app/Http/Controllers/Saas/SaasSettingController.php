<?php

namespace App\Http\Controllers\Saas;

use App\Http\Controllers\Controller;
use App\Models\SaasSetting;
use Illuminate\Http\Request;

class SaasSettingController extends Controller
{
    /**
     * Get all platform settings.
     */
    public function index()
    {
        return response()->json(SaasSetting::all()->pluck('value', 'key'));
    }

    /**
     * Bulk update platform settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.bank_name' => 'nullable|string',
            'settings.bank_account_number' => 'nullable|string',
            'settings.bank_account_name' => 'nullable|string',
            'settings.contact_email' => 'nullable|email',
            'settings.contact_whatsapp' => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            SaasSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return response()->json(['message' => 'Pengaturan platform berhasil diperbarui.']);
    }
}
