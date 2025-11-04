<?php

namespace App\Http\Controllers;

use App\Models\SystemText;
use Illuminate\Http\Request;

class SystemTextController extends Controller
{
    /**
     * 一覧取得（Vue側で使用）
     */
    public function index()
    {
        return response()->json(SystemText::pluck('content', 'key'));
    }

    /**
     * 管理画面などから更新
     */
    public function update(Request $request, $key)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $text = SystemText::updateOrCreate(['key' => $key], ['content' => $validated['content']]);
        return response()->json($text);
    }
}
