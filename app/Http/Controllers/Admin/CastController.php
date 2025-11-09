<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CastProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CastController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $casts = CastProfile::with('user:id,name,email,area')
            // ← 検索は括弧でグルーピングして or の漏れを防止
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nickname', 'like', "%{$q}%")
                      ->orWhereHas('user', fn($uq) =>
                          $uq->where('name',  'like', "%{$q}%")
                             ->orWhere('email','like', "%{$q}%")
                             ->orWhere('area', 'like', "%{$q}%")
                      );
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Casts/Index', [
            'casts'   => $casts,
            'filters' => ['q' => $q],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $user = User::firstOrCreate(
            ['email' => $request->string('email')->toString()],
            [
                'name'     => $request->string('name')->toString(),
                'area'     => $request->string('area')->toString(),
                'password' => bcrypt(str()->random(16)),
            ]
        );

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('cast_photos', 'public');
        }

        CastProfile::create([
            'user_id'         => $user->id,
            'nickname'        => $data['nickname']   ?? null,
            'rank'            => $data['rank']       ?? null,
            'age'             => $data['age']        ?? null,
            'height_cm'       => $data['height_cm']  ?? null,
            'cup'             => $data['cup']        ?? null,
            'style'           => $data['style']      ?? null,
            'alcohol'         => $data['alcohol']    ?? null,
            'mbti'            => $data['mbti']       ?? null,
            'area'            => $data['area']       ?? null,
            'tags'            => $data['tags']       ?? null,
            'freeword'        => $data['freeword']   ?? null,
            'nomination_fee'  => $data['nomination_fee'] ?? 0,
            'photo_path'      => $photoPath,
            'is_blur_default' => array_key_exists('is_blur_default', $data)
                                  ? (bool)$data['is_blur_default'] : true,
        ]);

        return back()->with('success', 'キャストを作成しました');
    }

    public function update(Request $request, CastProfile $cast)
    {
        $data = $this->validated($request, updating: true);

        // User 側（filled の項目のみ）
        if ($request->filled('name') || $request->filled('email') || $request->filled('area')) {
            $cast->user->fill([
                'name'  => $request->filled('name')  ? $request->string('name')->toString()  : $cast->user->name,
                'email' => $request->filled('email') ? $request->string('email')->toString() : $cast->user->email,
                'area'  => $request->filled('area')  ? $request->string('area')->toString()  : $cast->user->area,
            ])->save();
        }

        // 画像更新
        if ($request->hasFile('photo')) {
            if ($cast->photo_path) {
                Storage::disk('public')->delete($cast->photo_path);
            }
            $cast->photo_path = $request->file('photo')->store('cast_photos', 'public');
        }

        // CastProfile 側（許可項目のみ反映）
        $cast->fill(Arr::only($data, [
            'nickname','rank','age','height_cm','cup','style','alcohol','mbti',
            'area','tags','freeword','nomination_fee',
        ]));

        if ($request->has('is_blur_default')) {
            $cast->is_blur_default = $request->boolean('is_blur_default');
        }

        $cast->save();
        return back()->with('success', '更新しました');
    }

    public function destroy(CastProfile $cast)
    {
        if ($cast->photo_path) {
            Storage::disk('public')->delete($cast->photo_path);
        }
        $cast->delete();
        return back()->with('success', '削除しました');
    }

    private function validated(Request $request, bool $updating = false): array
    {
        return $request->validate([
            // User 側（更新時は nullable + sometimes）
            'name'   => [$updating ? 'sometimes' : 'required', $updating ? 'nullable' : 'string', 'string','max:255'],
            'email'  => [$updating ? 'sometimes' : 'required', 'nullable','email','max:255'],
            'area'   => ['sometimes','nullable','string','max:255'],

            // CastProfile 側
            'nickname'        => ['nullable','string','max:255'],
            'rank'            => ['nullable','string','max:50'],
            'age'             => ['nullable','integer','min:0','max:120'],
            'height_cm'       => ['nullable','integer','min:0','max:300'],
            'cup'             => ['nullable','string','max:5'],
            'style'           => ['nullable','string','max:255'],
            'alcohol'         => ['nullable','string','max:50'],
            'mbti'            => ['nullable','string','max:4'],
            'tags'            => ['nullable','string','max:255'],
            'freeword'        => ['nullable','string','max:2000'],
            'photo'           => ['nullable','image','max:4096'],
            'nomination_fee'  => ['nullable','integer','min:0','max:1000000'],

            'is_blur_default' => ['sometimes','boolean'],
        ]);
    }
}
