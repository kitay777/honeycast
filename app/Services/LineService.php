<?php
// app/Services/LineService.php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineService
{
    public function __construct(
        private readonly string $token = '',
        private readonly string $secret = ''
    ){
        $this->token = config('services.line.channel_access_token');
        $this->secret = config('services.line.channel_secret');
    }

    public function pushText(string $to, string $text): void {
        $payload = ['to'=>$to, 'messages'=>[['type'=>'text','text'=>$text]]];
        $res = Http::withToken($this->token)->post('https://api.line.me/v2/bot/message/push', $payload);
        if (!$res->ok()) throw new \RuntimeException('LINE push failed: '.$res->body());
    }

    public function replyText(string $replyToken, string $text): void {
        $payload = ['replyToken'=>$replyToken, 'messages'=>[['type'=>'text','text'=>$text]]];
        $res = Http::withToken($this->token)->post('https://api.line.me/v2/bot/message/reply', $payload);
        if (!$res->ok()) Log::warning('LINE reply failed', ['body'=>$res->body()]);
    }

    public function getProfile(string $userId): ?array {
        $res = Http::withToken($this->token)->get("https://api.line.me/v2/bot/profile/{$userId}");
        return $res->ok() ? $res->json() : null;
    }

    public function verifySignature(string $rawBody, ?string $signature): bool {
        if (!$signature) return false;
        $calc = base64_encode(hash_hmac('sha256', $rawBody, $this->secret, true));
        return hash_equals($calc, $signature);
    }
}
