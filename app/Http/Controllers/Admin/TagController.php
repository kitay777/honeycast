<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TagController extends Controller
{
  public function index() {
    $tags = Tag::orderBy('sort_order')->orderBy('name')->get(['id','name','slug','is_active','sort_order']);
    return Inertia::render('Admin/Tags/Index', ['tags'=>$tags]);
  }
  public function store(Request $r) {
    $data = $r->validate([
      'name'=>['required','string','max:50','unique:tags,name'],
      'slug'=>['nullable','string','max:80','unique:tags,slug'],
      'is_active'=>['boolean'],
      'sort_order'=>['integer','min:0'],
    ]);
    Tag::create($data + ['is_active'=>$r->boolean('is_active')]);
    return back()->with('success','Tag created');
  }
  public function update(Request $r, Tag $tag) {
    $data = $r->validate([
      'name'=>['required','string','max:50','unique:tags,name,'.$tag->id],
      'slug'=>['nullable','string','max:80','unique:tags,slug,'.$tag->id],
      'is_active'=>['boolean'],
      'sort_order'=>['integer','min:0'],
    ]);
    $tag->update($data + ['is_active'=>$r->boolean('is_active')]);
    return back()->with('success','Tag updated');
  }
  public function destroy(Tag $tag) {
    $tag->delete();
    return back()->with('success','Tag deleted');
  }
}

