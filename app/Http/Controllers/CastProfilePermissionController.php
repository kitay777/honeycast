<?php

// app/Http/Controllers/CastProfilePermissionController.php
namespace App\Http\Controllers;

use App\Models\CastProfile;
use App\Models\CastProfileViewPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CastProfilePermissionController extends Controller
{
    // 閲覧者 → 申請
    public function store(Request $request, CastProfile $castProfile) {
        $data = $request->validate([
            'message' => ['nullable','string','max:1000'],
        ]);

        $perm = CastProfileViewPermission::updateOrCreate(
            ['cast_profile_id' => $castProfile->id, 'viewer_user_id' => Auth::id()],
            ['status' => 'pending', 'message' => $data['message'] ?? null, 'granted_by_user_id' => null, 'expires_at' => null]
        );

        // 通知（任意）
        // optional: $castProfile->user->notify(new UnblurRequested($perm));

        return back()->with('success', '閲覧申請を送信しました。');
    }

    // キャスト → 承認
    public function approve(Request $request, CastProfile $castProfile, CastProfileViewPermission $permission) {
        $this->authorize('manage', $castProfile);

        $request->validate([
            'expires_at' => ['nullable','date']
        ]);

        $permission->update([
            'status' => 'approved',
            'granted_by_user_id' => Auth::id(),
            'expires_at' => $request->input('expires_at'),
        ]);

        // 通知（任意）
        // optional: $permission->viewer->notify(new UnblurApproved($permission));

        return back()->with('success', '承認しました。');
    }

    // キャスト → 否認
    public function deny(Request $request, CastProfile $castProfile, CastProfileViewPermission $permission) {
        $this->authorize('manage', $castProfile);

        $permission->update([
            'status' => 'denied',
            'granted_by_user_id' => Auth::id(),
            'expires_at' => null,
        ]);

        // 通知（任意）
        return back()->with('success', '否認しました。');
    }
}
