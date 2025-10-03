<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Services\ProhibitedWordService as NG;

class NoProhibitedWords implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || $value === '') return;
        $hit = NG::check($value);
        if ($hit && $hit['severity'] === 'block') {
            $fail("禁止ワードに該当します: 「{$hit['word']}」");
        }
        // warn/mask 運用は将来ここで
    }
}
