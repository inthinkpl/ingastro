<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationSettingsController extends Controller
{
    public function edit()
    {
        return Inertia::render('Admin/Settings/PushNotifications', [
            'settings' => NotificationSetting::all()
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.id' => 'required|exists:notification_settings,id',
            'settings.*.title_template' => 'required|string|max:255',
            'settings.*.body_template' => 'required|string|max:500',
        ]);

        foreach ($request->settings as $item) {
            NotificationSetting::where('id', $item['id'])->update([
                'title_template' => $item['title_template'],
                'body_template'  => $item['body_template'],
            ]);
        }

        return back()->with('message', 'Szablony powiadomień zostały zaktualizowane.');
    }
}