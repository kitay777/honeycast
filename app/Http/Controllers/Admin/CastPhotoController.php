<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CastPhoto;
use App\Models\CastProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CastPhotoController extends Controller
{
    public function index(CastProfile $cast)
    {
        // 最新の並びで返す
        $photos = $cast->photos()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'url'          => asset('storage/'.$p->path),
                'path'         => $p->path,
                'caption'      => $p->caption,
                'should_blur'  => (bool) $p->should_blur,
                'is_primary'   => (bool) $p->is_primary,
                'sort_order'   => (int)  $p->sort_order,
            ]);

        return response()->json($photos);
    }

    public function store(Request $request, CastProfile $cast)
    {
        $request->validate([
            'photos'          => ['required','array','min:1','max:12'],
            'photos.*'        => ['file','image','max:8192'],
            'should_blur'     => ['sometimes','boolean'],       // 一括指定したい時
            'captions'        => ['sometimes','array'],         // index => 文字列
            'captions.*'      => ['nullable','string','max:120'],
        ]);

        $created = [];
        $baseOrder = (int) ($cast->photos()->max('sort_order') ?? 0);

        foreach ($request->file('photos') as $i => $file) {
            $path = $file->store('cast_photos', 'public');
            $p = new CastPhoto();
            $p->cast_profile_id = $cast->id;
            $p->path            = $path;
            $p->sort_order      = $baseOrder + $i + 1;
            $p->is_primary      = false;
            $p->should_blur     = (bool) $request->boolean('should_blur', false);
            $p->caption         = $request->input("captions.$i");
            $p->save();
            $created[] = $p;
        }

        return response()->json([
            'ok'     => true,
            'photos' => $this->index($cast)->getData(true) // 新リスト
        ]);
    }

    public function update(Request $request, CastProfile $cast, CastPhoto $photo)
    {
        // cast に属するものかチェック
        abort_unless($photo->cast_profile_id === $cast->id, 404);

        $data = $request->validate([
            'caption'     => ['sometimes','nullable','string','max:120'],
            'should_blur' => ['sometimes','boolean'],
            'is_primary'  => ['sometimes','boolean'],
        ]);

        // メイン指定
        if (array_key_exists('is_primary', $data)) {
            if ($data['is_primary']) {
                // 既存のメインを外し自分をメインに
                CastPhoto::where('cast_profile_id', $cast->id)->update(['is_primary' => false]);
                $photo->is_primary = true;
            } else {
                $photo->is_primary = false;
            }
        }

        if (array_key_exists('should_blur', $data)) {
            $photo->should_blur = (bool) $data['should_blur'];
        }
        if (array_key_exists('caption', $data)) {
            $photo->caption = $data['caption'];
        }

        $photo->save();

        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, CastProfile $cast)
    {
        $payload = $request->validate([
            'order'   => ['required','array','min:1'],
            'order.*' => ['integer', Rule::exists('cast_photos','id')->where('cast_profile_id', $cast->id)],
        ]);

        foreach ($payload['order'] as $idx => $photoId) {
            CastPhoto::where('id', $photoId)->where('cast_profile_id', $cast->id)
                ->update(['sort_order' => $idx + 1]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(CastProfile $cast, CastPhoto $photo)
    {
        abort_unless($photo->cast_profile_id === $cast->id, 404);

        // 物理削除
        if ($photo->path) {
            Storage::disk('public')->delete($photo->path);
        }

        $photo->delete();

        return response()->json(['ok' => true]);
    }
}
