<?php

namespace App\Services\Ai;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AI Assistant read-only: membangun snapshot sesuai role lalu meminta ringkasan
 * ke provider LLM tanpa tools. Tidak ada jalur tulis ke database.
 */
class AiAssistantService
{
    private static bool $guardingWrites = false;

    public function __construct(
        private AiContextBuilder $contextBuilder
    ) {}

    /**
     * @return array{available: bool, role: string|null, answer: string}
     */
    public function answer(User $user, string $prompt): array
    {
        $role = AiContextBuilder::resolveRole($user);

        if ($role === null) {
            throw new RuntimeException('Role pengguna tidak diizinkan memakai AI Assistant.');
        }

        if (! config('ai.enabled') || ! config('ai.api_key')) {
            return [
                'available' => false,
                'role' => $role,
                'answer' => 'Fitur AI Assistant belum diaktifkan. Hubungi administrator untuk konfigurasi dan persetujuan kebijakan data.',
            ];
        }

        $context = $this->readOnly(function () use ($user, $role) {
            return $this->contextBuilder->build($user, $role);
        });

        $systemPrompt = $this->systemPrompt($role);
        $userInput = "Snapshot data ERP (JSON):\n".json_encode($context, JSON_UNESCAPED_UNICODE)
            ."\n\nPertanyaan pengguna:\n".$prompt;

        try {
            $response = Http::withToken(config('ai.api_key'))
                ->acceptJson()
                ->timeout((int) config('ai.timeout', 20))
                ->post(rtrim(config('ai.base_url'), '/').'/v1/responses', [
                    'model' => config('ai.model'),
                    'instructions' => $systemPrompt,
                    'input' => $userInput,
                    'max_output_tokens' => (int) config('ai.max_output_tokens', 600),
                ]);
        } catch (\Throwable $e) {
            Log::warning('AI assistant request failed: '.$e::class);

            return [
                'available' => false,
                'role' => $role,
                'answer' => 'Layanan AI sedang tidak dapat dihubungi. Silakan coba lagi atau gunakan modul ERP.',
            ];
        }

        if ($response->failed()) {
            Log::warning('AI assistant provider status: '.$response->status());

            return [
                'available' => false,
                'role' => $role,
                'answer' => 'Layanan AI sedang bermasalah. Silakan coba lagi atau gunakan modul ERP.',
            ];
        }

        $answer = trim($this->extractText($response->json()) ?? '');

        return [
            'available' => $answer !== '',
            'role' => $role,
            'answer' => $answer !== ''
                ? $answer
                : 'Jawaban AI tidak dapat dibaca. Silakan coba lagi atau verifikasi langsung pada modul ERP.',
        ];
    }

    /**
     * Jalankan snapshot di bawah listener yang menolak query non-SELECT.
     * shortcut: hanya mendeteksi SQL tulis pada koneksi default, bukan privilege DB.
     */
    private function readOnly(callable $callback): array
    {
        self::$guardingWrites = true;

        DB::listen(function ($query) {
            if (self::$guardingWrites && ! preg_match('/^\s*(select|show|describe|explain|with)\b/i', $query->sql)) {
                throw new RuntimeException('AI Assistant hanya boleh membaca data.');
            }
        });

        try {
            return $callback();
        } finally {
            self::$guardingWrites = false;
        }
    }

    private function systemPrompt(string $role): string
    {
        return <<<PROMPT
Anda adalah AI Assistant ERP PT TADCo Transport Perkasa. Tugas Anda hanya menjelaskan snapshot data
yang diberikan, dalam Bahasa Indonesia yang ringkas dan jelas.

Batas yang wajib dipatuhi:
1. Gunakan HANYA angka dan fakta dari snapshot JSON. Jangan mengarang angka, nama, atau tren.
2. Jika data yang ditanyakan tidak ada di snapshot, katakan datanya tidak tersedia pada ringkasan.
3. Anda read-only: tidak dapat membuat, mengubah, membatalkan, atau menyetujui transaksi. Tolak permintaan semacam itu dan arahkan pengguna ke modul ERP.
4. Jangan membahas data di luar cakupan role saat ini. Role aktif: {$role}.
5. Jangan menampilkan API key, kredensial, atau detail internal sistem.
6. Sebutkan bahwa angka penting dapat diverifikasi pada modul ERP terkait.
7. Jawab maksimal 6 kalimat atau 5 poin; sebutkan periode/status data bila relevan.
PROMPT;
    }

    private function extractText(?array $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        if (! empty($payload['output_text']) && is_string($payload['output_text'])) {
            return $payload['output_text'];
        }

        foreach ($payload['output'] ?? [] as $output) {
            foreach ($output['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                    return $content['text'];
                }
            }
        }

        return null;
    }
}
