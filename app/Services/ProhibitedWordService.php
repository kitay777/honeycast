<?php
namespace App\Services;

use App\Models\ProhibitedWord;
use Illuminate\Support\Facades\Cache;

class ProhibitedWordService
{
    /** 入力の正規化（小文字、全角→半角、空白除去） */
    public static function normalize(string $text): string
    {
        $t = mb_strtolower(trim($text), 'UTF-8');
        // K: カタカナ, V: 濁点結合, a: 半角へ, s: 空白を半角に
        $t = mb_convert_kana($t, 'KVas', 'UTF-8');
        $t = preg_replace('/\s+/u', '', $t); // すべての空白を削除
        return $t ?? '';
    }

    /** 有効なNGワード一覧（キャッシュ） */
    public static function active(): \Illuminate\Support\Collection
    {
        return Cache::remember('ngwords:active', 300, function () {
            return ProhibitedWord::where('is_active', true)
                ->orderBy('match_type')->orderBy('normalized')->get();
        });
    }

    /** テキストをチェック。ヒットしたら ['word'=>..., 'match_type'=>...] を返す */
    public static function check(string $text): ?array
    {
        $normalized = self::normalize($text);
        foreach (self::active() as $w) {
            if ($w->match_type === 'regex') {
                // 正規表現は raw テキストに対して（u フラグ必須運用）
                set_error_handler(function(){});
                $ok = @preg_match($w->phrase, $text) === 1;
                restore_error_handler();
                if ($ok) return ['word'=>$w->phrase, 'match_type'=>'regex', 'severity'=>$w->severity];
            } elseif ($w->match_type === 'exact') {
                if ($normalized !== '' && $normalized === $w->normalized) {
                    return ['word'=>$w->phrase, 'match_type'=>'exact', 'severity'=>$w->severity];
                }
            } else { // contain
                if ($w->normalized !== '' && str_contains($normalized, $w->normalized)) {
                    return ['word'=>$w->phrase, 'match_type'=>'contain', 'severity'=>$w->severity];
                }
            }
        }
        return null;
    }
}
