<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index()
    {
        return response()->json(SystemSetting::orderBy('key')->get());
    }

    public function show(string $key)
    {
        $setting = SystemSetting::where('key', $key)->first();
        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }
        return response()->json($setting);
    }

    public function update(Request $request, string $key)
    {
        $validated = $request->validate([
            'value' => 'nullable|string|max:255',
        ]);

        if ($key === 'qc_policy') {
            $allowed = ['strict', 'medium', 'off'];
            if (!in_array($validated['value'], $allowed, true)) {
                return response()->json(['message' => 'Invalid QC policy value'], 422);
            }
        }

        $setting = SystemSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $validated['value']]
        );

        return response()->json($setting);
    }
}
