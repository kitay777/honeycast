<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemText;

class SystemTextAdminController extends Controller
{
    // 一覧取得
    public function index()
    {
        return response()->json(SystemText::orderBy('key')->get());
    }

    // 単体更新
    public function update(Request $request, $key)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $text = SystemText::updateOrCreate(['key' => $key], ['content' => $validated['content']]);
        return response()->json($text);
    }
}
