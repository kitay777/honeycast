<?php

// database/migrations/xxxx_xx_xx_xxxxxx_backfill_tags_from_cast_profiles.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration {
  public function up(): void {
    if (!DB::getSchemaBuilder()->hasTable('cast_profiles')) return;

    $rows = DB::table('cast_profiles')->select('id','tags')->get();
    foreach ($rows as $r) {
      $raw = $r->tags;

      // 1) 安全にタグ配列を作る（JSON優先 → 文字列分割）
      $tokens = is_array($raw)
        ? $raw
        : (json_decode($raw, true) ?: preg_split('/[,\s、，]+/u', (string)$raw));

      $names = collect($tokens)
        ->map(fn($t) => trim((string)$t))                   // 前後空白
        ->map(fn($t) => trim($t, " \t\n\r\0\x0B\"'[]{}"))   // 括弧/クォート除去 ← 重要
        ->filter()                                          // 空を除外
        ->unique()
        ->values();

      // 2) tags を作成/再利用（slug が空なら NULL に）
      foreach ($names as $name) {
        $tagId = DB::table('tags')->where('name',$name)->value('id');
        if (!$tagId) {
          $slug = Str::slug($name, '-');
          if ($slug === '' || $slug === null) $slug = null; // ★ ここが肝：空slugは入れない

          $tagId = DB::table('tags')->insertGetId([
            'name'       => $name,
            'slug'       => $slug,
            'is_active'  => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
          ]);
        }

        // 3) pivot を重複なしで挿入
        $exists = DB::table('cast_profile_tag')
          ->where('cast_profile_id',$r->id)
          ->where('tag_id',$tagId)->exists();

        if (!$exists) {
          DB::table('cast_profile_tag')->insert([
            'cast_profile_id' => $r->id,
            'tag_id'          => $tagId,
            'created_at'      => now(),
            'updated_at'      => now(),
          ]);
        }
      }
    }
  }

  public function down(): void {
    // 安全のためデータ巻き戻しはしない（必要なら個別に削除）
  }
};

