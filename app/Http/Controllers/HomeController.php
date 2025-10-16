<?php

namespace App\Http\Controllers;

use App\Models\CastProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\TextBanner;
use App\Models\AdBanner;
use App\Models\NewsItem;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $viewer = $request->user();

        // いいね済みキャストID
        $likedIds = $viewer
            ? DB::table('cast_likes')->where('user_id', $viewer->id)->pluck('cast_profile_id')->all()
            : [];

        // カード化（liked を含める）
        $toCard = fn(CastProfile $c) => [
            'id'         => $c->id,
            'nickname'   => $c->nickname,
            'photo_path' => $c->photo_path,
            'is_blur_default'          => true,
            'viewer_has_unblur_access' => false,
            'should_blur'              => false,
            'liked'      => in_array($c->id, $likedIds, true),
        ];

        $textBanners = TextBanner::active()->get(['id','message','url','speed','bg_color','text_color']);
        $adBanners   = AdBanner::active()->get(['id','image_path','url','height','priority']);
        $news        = NewsItem::active()->take(10)->get(['id','title','body','url','published_at']);

        // ====== 検索条件 ======
        $f = $request->only([
            'freeword','rank_min','rank_max','age_min','age_max','area',
            'height_min','height_max','cup_min','cup_max','style','alcohol','mbti','tags',
        ]);
        $hasFilter = collect($f)->filter(fn($v) => is_array($v) ? count(array_filter($v)) : strlen((string)$v) > 0)->isNotEmpty();

        $search = [];
        if ($hasFilter) {
            $q = CastProfile::query();

            if (!empty($f['freeword'])) {
                $q->where(function($qq) use ($f) {
                    $qq->where('nickname','like','%'.$f['freeword'].'%')
                       ->orWhere('freeword','like','%'.$f['freeword'].'%');
                });
            }
            if ($f['rank_min'] ?? null) $q->where('rank','>=',(int)$f['rank_min']);
            if ($f['rank_max'] ?? null) $q->where('rank','<=',(int)$f['rank_max']);
            if ($f['age_min']  ?? null) $q->where('age','>=',(int)$f['age_min']);
            if ($f['age_max']  ?? null) $q->where('age','<=',(int)$f['age_max']);
            if (!empty($f['area']))      $q->where('area',$f['area']);
            if ($f['height_min']?? null) $q->where('height_cm','>=',(int)$f['height_min']);
            if ($f['height_max']?? null) $q->where('height_cm','<=',(int)$f['height_max']);

            $rankCup = fn($c) => array_search(strtoupper($c), ['A','B','C','D','E','F','G','H']);
            if (!empty($f['cup_min'])) {
                $cupMin = $rankCup($f['cup_min']);
                $q->whereRaw("INSTR('ABCDEFGH', UPPER(cup)) - 1 >= ?", [$cupMin]);
            }
            if (!empty($f['cup_max'])) {
                $cupMax = $rankCup($f['cup_max']);
                $q->whereRaw("INSTR('ABCDEFGH', UPPER(cup)) - 1 <= ?", [$cupMax]);
            }

            if (!empty($f['style']))   $q->where('style',$f['style']);
            if (!empty($f['alcohol'])) $q->where('alcohol',$f['alcohol']);
            if (!empty($f['mbti']))    $q->where('mbti', strtoupper($f['mbti']));
            if (!empty($f['tags']) && is_array($f['tags'])) {
                foreach ($f['tags'] as $tag) {
                    if ($tag !== '') $q->whereJsonContains('tags', $tag);
                }
            }

            $search = $q->orderByDesc('updated_at')
                        ->take(60)
                        ->get(['id','nickname','photo_path','is_blur_default'])
                        ->map($toCard)->values()->all();
        }

        // ====== ダッシュボード用 ======
        $today   = CastProfile::select('id','nickname','photo_path','is_blur_default')->latest('updated_at')->take(9)->get()->map($toCard)->values()->all();
        $login   = CastProfile::select('id','nickname','photo_path','is_blur_default')->latest('updated_at')->take(9)->get()->map($toCard)->values()->all();
        $newbies = CastProfile::select('id','nickname','photo_path','is_blur_default')->latest('created_at')->take(9)->get()->map($toCard)->values()->all();
        $roster  = CastProfile::select('id','nickname','photo_path','is_blur_default')->orderBy('nickname')->take(9)->get()->map($toCard)->values()->all();

        return Inertia::render('Dashboard', [
            'search_applied' => $hasFilter,
            'search_results' => $search,
            'search_filters' => $f,
            'today'   => $today,
            'login'   => $login,
            'newbies' => $newbies,
            'roster'  => $roster,
            'text_banners' => $textBanners,
            'ad_banners'   => $adBanners->map(fn($b) => [
                'id'     => $b->id,
                'url'    => $b->url,
                'height' => $b->height,
                'src'    => $b->public_url,
            ]),
            'news' => $news,
        ]);
    }
}
