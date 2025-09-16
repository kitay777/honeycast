<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CastRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ゲスト可
    }

    public function rules(): array
    {
        return [
            // User
            'name'     => ['required','string','max:255'],
            'email'    => ['required','string','email','max:255','unique:users,email'],
            'password' => ['required', Password::defaults(), 'confirmed'],
            'area'     => ['nullable','string','max:255'],

            // CastProfile（必要に応じて必須化）
            'nickname'   => ['nullable','string','max:255'],
            'rank'       => ['nullable','integer','min:0','max:99'],
            'age'        => ['nullable','integer','min:18','max:99'],
            'height_cm'  => ['nullable','integer','min:120','max:220'],
            'cup'        => ['nullable','string','max:5'],
            'style'      => ['nullable','string','max:50'],
            'alcohol'    => ['nullable','string','max:50'],
            'mbti'       => ['nullable','string','max:4'],
            'cast_area'  => ['nullable','string','max:255'],
            'tags'       => ['nullable','array'],
            'tags.*'     => ['string','max:30'],
            'freeword'   => ['nullable','string','max:2000'],
            'photo'      => ['nullable','image','mimes:jpeg,png,jpg,gif,webp','max:4096'],
        ];
    }
}
