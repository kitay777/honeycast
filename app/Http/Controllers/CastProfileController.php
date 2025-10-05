<?php

namespace App\Http\Controllers;

use App\Models\CastPhoto;
use App\Models\CastProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\CastPhotoViewPermission; 
use App\Models\Tag;
use App\Rules\NoProhibitedWords;


class CastProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        $profile = $user->castProfile ?? CastProfile::where('user_id', $user->id)->first();
        if (!$profile) {
            return Inertia::render('Cast/ProfileEdit', [
                'cast' => null,
                'profile' => null,
                'pendingPermissions' => [],
                'pendingPhotoPermissions' => [], 
            ]);
        }

        $pendingPhotoPermissions = [];
        if (auth()->user()->can('manage', $profile)) {
            $photoIds = $profile->photos()->pluck('id'); // このキャストの写真ID群
            if ($photoIds->isNotEmpty()) {
                $pendingPhotoPermissions = CastPhotoViewPermission::whereIn('cast_photo_id', $photoIds)
                    ->where('status', 'pending')
                    ->with(['viewer:id,name', 'photo:id,path'])
                    ->latest()->take(100)->get()
                    ->map(fn($perm) => [
                        'id'        => $perm->id,
                        'photo_id'  => $perm->cast_photo_id,
                        'viewer'    => ['id' => $perm->viewer?->id, 'name' => $perm->viewer?->name],
                        'message'   => $perm->message,
                        'created_at'=> optional($perm->created_at)->toDateTimeString(),
                        'thumb'     => $perm->photo?->path ? Storage::disk('public')->url($perm->photo->path) : null,
                    ]);
            }
        }
        // 承認待ち（ぼかし解除）
        $pendingPermissions = [];
        if (auth()->user()->can('manage', $profile)) {
            $pendingPermissions = $profile->viewPermissions()
                ->where('status', 'pending')
                ->with('viewer:id,name')
                ->latest()->take(50)->get();
        }

        $selectedIds = $profile->tagsRel()->pluck('tags.id')->toArray();
        $availableTags = Tag::where('is_active',true)->orderBy('sort_order')->orderBy('name')
        ->get(['id','name']);
        // タグ配列化（文字列でも壊れない）
        $tags = $profile->tags;
        if (!is_array($tags)) {
            $tags = collect(preg_split('/[,\s、，]+/u', (string) $tags, -1, PREG_SPLIT_NO_EMPTY))
                ->values()->all();
        }

        // 写真一覧
        $photos = $profile->photos()
            ->orderBy('sort_order')->get(['id','path','sort_order','is_primary'])
            ->map(fn($p) => [
                'id'         => $p->id,
                'url'        => Storage::disk('public')->url($p->path),
                'sort_order' => $p->sort_order,
                'is_primary' => (bool)$p->is_primary,
            ]);

        $cast = [
            'id'         => $profile->id,
            'nickname'   => $profile->nickname,
            'rank'       => $profile->rank,
            'age'        => $profile->age,
            'height_cm'  => $profile->height_cm,
            'cup'        => $profile->cup,
            'style'      => $profile->style,
            'alcohol'    => $profile->alcohol,
            'mbti'       => $profile->mbti,
            'area'       => $profile->area,
            'freeword'   => $profile->freeword,
            'photo_path' => $profile->photo_path, // 後方互換（表紙）
            'photos'     => $photos,              // ギャラリー
            'tag_ids' => $selectedIds,          // ★ 追加
            'tags'    => $profile->tagsRel()
                          ->select('tags.id as id','tags.name')   // ← ここがポイント
                  ->orderBy('tags.name')
                  ->get(),
        ];

        return Inertia::render('Cast/ProfileEdit', [
            'cast' => $cast,
            'pendingPermissions' => $pendingPermissions,
            'pendingPhotoPermissions'  => $pendingPhotoPermissions,
            'available_tags' => $availableTags,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'nickname'   => ['nullable', 'string', 'max:255', new NoProhibitedWords],
            'rank'       => ['nullable', 'integer', 'min:0', 'max:99'],
            'age'        => ['nullable', 'integer', 'min:18', 'max:99'],
            'height_cm'  => ['nullable', 'integer', 'min:120', 'max:220'],
            'cup'        => ['nullable', 'string', 'max:5'],
            'style'      => ['nullable', 'string', 'max:50'],
            'alcohol'    => ['nullable', 'string', 'max:50'],
            'mbti'       => ['nullable', 'string', 'max:4'],
            'area'       => ['nullable', 'string', 'max:255'],
            'tags'       => ['nullable', 'array'],
            'tags.*'     => ['string', 'max:30'],
            'freeword'   => ['nullable', 'string', 'max:2000', new NoProhibitedWords],

            // 旧：単発アップロード互換
            'photo'      => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:4096'],

            // 新：複数アップロード
            'photos'         => ['sometimes','array'],
            'photos.*'       => ['image','mimes:jpeg,png,jpg,gif,webp','max:4096'],
            'delete_photo_ids'      => ['sometimes','array'],
            'delete_photo_ids.*'    => ['integer'],
            'orders'         => ['sometimes','array'],
            'orders.*.id'    => ['integer'],
            'orders.*.order' => ['integer'],
            'primary_photo_id' => ['nullable','integer'],

            'tag_ids' => ['sometimes','array'],
            'tag_ids.*' => ['integer','exists:tags,id'],
        ]);

        $profile = CastProfile::firstOrCreate(['user_id' => $user->id]);

        // ▼ 基本プロフを更新（ここでは画像以外）
        $profile->fill($data)->save();
        if ($request->has('tag_ids')) {
        $profile->tagsRel()->sync($request->input('tag_ids', []));
        }
        if ($request->filled('tags') && is_array($request->input('tags'))) {
        $names = array_filter($request->input('tags'), fn($v)=>trim((string)$v)!=='');
        $ids = \App\Models\Tag::whereIn('name',$names)->pluck('id')->all();
        $profile->tagsRel()->sync($ids);
        }
        // ▼ 写真関連の更新はトランザクションでまとめて
        DB::transaction(function () use ($request, $profile, &$data) {

            // a) 旧：単発 photo → 既存は消さず「追加」扱い。初回のみ表紙にする
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('cast_photos', 'public');
                $hasAny = $profile->photos()->exists();
                $nextOrder = (int) ($profile->photos()->max('sort_order') ?? 0) + 1;

                $added = $profile->photos()->create([
                    'path'       => $path,
                    'sort_order' => $nextOrder,
                    'is_primary' => !$hasAny, // 初回のみ表紙
                ]);

                if (!$hasAny && empty($profile->photo_path)) {
                    $profile->update(['photo_path' => $path]); // 後方互換
                }
            }

            // b) 新規複数追加
            if ($request->hasFile('photos')) {
                $start = (int) ($profile->photos()->max('sort_order') ?? 0) + 1;
                foreach ($request->file('photos') as $i => $file) {
                    $path = $file->store('cast_photos', 'public');
                    $profile->photos()->create([
                        'path'       => $path,
                        'sort_order' => $start + $i,
                        'is_primary' => false,
                    ]);
                }
            }

            // c) 削除
            $delIds = collect($request->input('delete_photo_ids', []))->map('intval')->all();
            if ($delIds) {
                $toDel = $profile->photos()->whereIn('id', $delIds)->get();
                foreach ($toDel as $p) {
                    Storage::disk('public')->delete($p->path);
                }
                $profile->photos()->whereIn('id', $delIds)->delete();
            }

            // d) 並び替え
            $orders = collect($request->input('orders', []));
            if ($orders->count()) {
                foreach ($orders as $o) {
                    $id  = (int)($o['id'] ?? 0);
                    $ord = (int)($o['order'] ?? 0);
                    if ($id) {
                        $profile->photos()->where('id', $id)->update(['sort_order' => $ord]);
                    }
                }
            }

            // e) 表紙選択（指定がある時だけ更新）
            $primaryId = (int)$request->input('primary_photo_id', 0);
            if ($primaryId) {
                $owned = $profile->photos()->where('id', $primaryId)->exists();
                if ($owned) {
                    $profile->photos()->update(['is_primary' => false]);
                    $profile->photos()->where('id', $primaryId)->update(['is_primary' => true]);
                }
            }

            // f) 後方互換：photo_path を現在の表紙に同期（表紙が無ければそのまま）
            $primary = $profile->photos()->where('is_primary', true)->first()
                    ?? $profile->photos()->orderBy('sort_order')->first();
            if ($primary) {
                $profile->update(['photo_path' => $primary->path]);
            }
        });
        //return back()->with('success', 'プロフィールを更新しました。');
        return redirect()->route('cast.profile.edit')->with('success', 'プロフィールを更新しました。');
    }
}
