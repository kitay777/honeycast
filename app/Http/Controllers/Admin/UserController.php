<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = (string) $request->query('q', '');
        $users = User::with(['castProfile:id,user_id,nickname'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name','like',"%{$q}%")
                      ->orWhere('email','like',"%{$q}%")
                      ->orWhere('area','like',"%{$q}%")
                      ->orWhere('phone','like',"%{$q}%");
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users'   => $users,
            'filters' => ['q' => $q],
            'me'      => $request->user()->only(['id','email']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255','unique:users,email'],
            'phone'    => ['nullable','string','max:255'],
            'area'     => ['nullable','string','max:255'],
            'is_admin' => ['boolean'],
        ]);
        User::create($data + ['password' => bcrypt(str()->random(16))]);
        return back()->with('success','ユーザーを作成しました');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['sometimes','required','string','max:255'],
            'email'    => ['sometimes','required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'phone'    => ['nullable','string','max:255'],
            'area'     => ['nullable','string','max:255'],
            'is_admin' => ['boolean'],
        ]);
        $user->fill($data)->save();
        return back()->with('success','更新しました');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) abort(422,'自分自身は削除できません');
        $user->delete();
        return back()->with('success','削除しました');
    }
}
