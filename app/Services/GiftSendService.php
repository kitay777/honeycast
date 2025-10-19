<?php
namespace App\Services;

use App\Models\{User, CastProfile, Gift, GiftSend, PointsEntry, CastPointsEntry};
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GiftSendService
{
    /**
     * @throws RuntimeException
     */
    public function send(User $sender, CastProfile $cast, Gift $gift, ?string $message=null): GiftSend
    {
        return DB::transaction(function () use ($sender, $cast, $gift, $message) {

            // 連投防止：直前のギフトが同一ならNG
            $last = GiftSend::where('sender_user_id', $sender->id)
                    ->where('cast_profile_id', $cast->id)
                    ->latest()->first();
            if ($last && $last->gift_id === $gift->id) {
                throw new RuntimeException('同じプレゼントを連続で送ることはできません。');
            }

            // ロック＆残高チェック（ユーザー）
            $u = User::whereKey($sender->id)->lockForUpdate()->first();
            if ($u->points < $gift->present_points) {
                throw new RuntimeException('ポイントが不足しています。');
            }
            $u->points -= $gift->present_points;
            $u->save();

            PointsEntry::create([
                'user_id'       => $u->id,
                'delta'         => -$gift->present_points,
                'balance_after' => $u->points,
                'reason'        => "プレゼント送付: {$gift->name} → Cast#{$cast->id}",
                'acted_by'      => $sender->id,
            ]);

            // ロック＆キャスト側付与
            $c = CastProfile::whereKey($cast->id)->lockForUpdate()->first();
            $c->points += $gift->cast_points;
            $c->save();

            CastPointsEntry::create([
                'cast_profile_id'=> $c->id,
                'delta'          => +$gift->cast_points,
                'balance_after'  => $c->points,
                'reason'         => "プレゼント受領: {$gift->name} ← User#{$sender->id}",
                'acted_by'       => $sender->id,
            ]);

            // 送付レコード
            $send = GiftSend::create([
                'sender_user_id'=> $u->id,
                'cast_profile_id'=> $c->id,
                'gift_id'       => $gift->id,
                'present_points'=> $gift->present_points,
                'cast_points'   => $gift->cast_points,
                'message'       => $message,
            ]);

            // 通知（DB通知 + 任意でLINE）
            $this->notifyCast($c, $u, $gift, $message);

            return $send;
        });
    }

    protected function notifyCast(CastProfile $cast, User $sender, Gift $gift, ?string $msg): void
    {
        // 1) DB通知（簡易）
        try {
            $user = $cast->user; // CastProfile -> belongsTo User で取れる想定
            if ($user) {
                $user->notify(new \App\Notifications\GiftReceivedNotification($sender, $gift, $msg));
            }
        } catch (\Throwable $e) {}

        // 2) LINE連携（任意）
        try {
            $lineId = $cast->user?->line_user_id;
            if ($lineId) {
                // ここは既存のPushロジックに合わせて
                app(\App\Services\LinePushService::class)
                  ->pushToUser($cast->user->id, "🎁 {$sender->name} から {$gift->name} を受け取りました！");
            }
        } catch (\Throwable $e) {}
    }
}
