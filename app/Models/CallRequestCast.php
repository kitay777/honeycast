<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallRequestCast extends Model
{
    protected $table = 'call_request_casts';
    protected $guarded = [];

    public function callRequest(): BelongsTo { return $this->belongsTo(CallRequest::class); }
    public function castProfile(): BelongsTo { return $this->belongsTo(CastProfile::class); }
    public function assignedBy(): BelongsTo { return $this->belongsTo(User::class, 'assigned_by'); }
}
