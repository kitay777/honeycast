<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CastShift extends Model
{
    protected $fillable = [
        'cast_profile_id','date','start_time','end_time','is_reserved',
    ];

    protected $casts = [
        'date'        => 'date',
        'is_reserved' => 'boolean',
        // time はそのまま文字列で扱う（TZずれ回避）
    ];

    public function castProfile()
    {
        return $this->belongsTo(\App\Models\CastProfile::class);
    }

    /** シフト開始の DateTime（アプリTZ） */
    public function getStartAtAttribute(): Carbon
    {
        return Carbon::createFromFormat('Y-m-d H:i', $this->date->format('Y-m-d').' '.$this->start_time, config('app.timezone'));
    }

    /** シフト終了の DateTime（アプリTZ） */
    public function getEndAtAttribute(): Carbon
    {
        // 24時跨ぎも一応ケア（end_time < start_time の場合は翌日扱い）
        $end = Carbon::createFromFormat('Y-m-d H:i', $this->date->format('Y-m-d').' '.$this->end_time, config('app.timezone'));
        if ($this->end_time < $this->start_time) {
            $end->addDay();
        }
        return $end;
    }

    /** 期間内スコープ */
    public function scopeBetweenDays(Builder $q, Carbon $from, Carbon $to): Builder
    {
        return $q->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
    }
}
