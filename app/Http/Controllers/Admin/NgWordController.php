<?php
// app/Http/Controllers/Admin/NgWordController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProhibitedWord;
use App\Services\ProhibitedWordService as NG;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class NgWordController extends Controller
{
    public function index(Request $r)
    {
        $q = $r->string('q')->toString();
        $words = ProhibitedWord::when($q, function($qq) use ($q){
                $n = NG::normalize($q);
                $qq->where(function($w) use ($q,$n){
                    $w->where('phrase','like',"%$q%")
                      ->orWhere('normalized','like',"%$n%");
                });
            })
            ->orderBy('is_active','desc')
            ->orderBy('match_type')
            ->orderBy('phrase')
            ->paginate(50)->withQueryString();

        return Inertia::render('Admin/NgWords/Index', [
            'words'   => $words,
            'filters' => ['q'=>$q],
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'phrase'     => ['required','string','max:255'],
            'match_type' => ['required', Rule::in(['contain','exact','regex'])],
            'severity'   => ['required', Rule::in(['block','warn','mask'])],
            'is_active'  => ['boolean'],
            'replacement'=> ['nullable','string','max:50'],
            'note'       => ['nullable','string','max:255'],
        ]);
        $data['normalized'] = $data['match_type'] === 'regex'
            ? '' : NG::normalize($data['phrase']); // regexはnormalized未使用
        $data['created_by'] = $r->user()?->id;

        // unique(normalized,match_type) で弾く
        ProhibitedWord::firstOrCreate(
            ['normalized'=>$data['normalized'], 'match_type'=>$data['match_type']],
            $data
        );

        Cache::forget('ngwords:active');
        return back()->with('success','追加しました');
    }

    public function update(Request $r, ProhibitedWord $word)
    {
        $data = $r->validate([
            'phrase'     => ['required','string','max:255'],
            'match_type' => ['required', Rule::in(['contain','exact','regex'])],
            'severity'   => ['required', Rule::in(['block','warn','mask'])],
            'is_active'  => ['boolean'],
            'replacement'=> ['nullable','string','max:50'],
            'note'       => ['nullable','string','max:255'],
        ]);

        $data['normalized'] = $data['match_type'] === 'regex'
            ? '' : NG::normalize($data['phrase']);

        // unique を自前で担保
        $duplicate = ProhibitedWord::where('id','<>',$word->id)
            ->where('match_type',$data['match_type'])
            ->where('normalized',$data['normalized'])
            ->exists();
        if ($duplicate) {
            return back()->withErrors(['phrase'=>'同じ照合キーのNGが既に存在します。'])->withInput();
        }

        $word->update($data + ['is_active'=>$r->boolean('is_active')]);
        Cache::forget('ngwords:active');
        return back()->with('success','更新しました');
    }

    public function destroy(ProhibitedWord $word)
    {
        $word->delete();
        Cache::forget('ngwords:active');
        return back()->with('success','削除しました');
    }
}
