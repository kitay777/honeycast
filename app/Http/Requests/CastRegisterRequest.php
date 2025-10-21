<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CastRegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // User
            'name'                  => ['required','string','max:60'],
            'email'                 => ['required','string','email','max:255','unique:users,email'],
            'password'              => ['required','string','min:8','confirmed'],
            'password_confirmation' => ['required','string','min:8'],
            'area'                  => ['nullable','string','max:120'],

            // CastProfile
            'nickname'   => ['required','string','max:60'],
            'age'        => ['required','integer','min:18','max:99'],
            'height_cm'  => ['required','integer','min:120','max:220'],
            'style'      => ['nullable','string','max:60'],
            'alcohol'    => ['nullable','string','max:30'],
            'mbti'       => ['nullable','string','max:4'],
            'cast_area'  => ['nullable','string','max:120'],
            'tags'       => ['nullable','array'],
            'tags.*'     => ['string','max:30'],
            'freeword'   => ['nullable','string','max:2000'],
            'photo'      => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string)$this->email)),
            'mbti'  => $this->mbti ? strtoupper((string)$this->mbti) : null,
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'お名前を入力してください。',
            'email.required' => 'メールを入力してください。',
            'email.email' => 'メール形式が正しくありません。',
            'email.unique' => 'このメールは既に使われています。',
            'password.required' => 'パスワードを入力してください。',
            'password.min' => 'パスワードは8文字以上で入力してください。',
            'password.confirmed' => '確認用パスワードが一致しません。',
            'password_confirmation.required' => '確認用パスワードを入力してください。',
            'nickname.required' => 'ニックネームを入力してください。',
            'photo.image' => '画像ファイルを選択してください。',
            'photo.max' => '画像サイズは5MB以内にしてください。',
            'age.min' => '年齢は18歳以上のみ登録できます。',
            'age.max' => '年齢は99歳以下のみ登録できます。',
            'age.required' => '年齢を入力してください。',
            'height_cm.required' => '身長を入力してください。',
            'height_cm.min' => '身長は120cm以上のみ登録できます。',
            'height_cm.max' => '身長は220cm以下のみ登録できます。', 
        ];
    }
}
