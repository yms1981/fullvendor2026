<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Dispara el scheduler de Laravel por HTTP (cron del hosting / panel → curl).
 * Equivalente a fullvendorERP `/cron/schedule`.
 */
class ScheduleTickController extends Controller
{
    public const CACHE_KEY_LAST_TICK = 'scheduler:last_tick_at';

    public const LOCK_KEY = 'scheduler:http_tick_lock';

    public function __invoke(Request $request): JsonResponse
    {
        $configured = (string) config('scheduler.token', '');
        if ($configured === '') {
            Log::channel('scheduler')->warning('scheduler.tick.disabled', [
                'reason' => 'SCHEDULE_TOKEN empty',
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Scheduler token not configured (SCHEDULE_TOKEN).',
            ], 503);
        }

        $provided = (string) (
            $request->query('token')
            ?? $request->header('X-Schedule-Token')
            ?? $request->bearerToken()
            ?? ''
        );

        if ($provided === '' || ! hash_equals($configured, $provided)) {
            Log::channel('scheduler')->warning('scheduler.tick.unauthorized', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        // Evitar saturar PHP-FPM si el cron se solapa. Si el lock quedó colgado, forzar.
        $lock = null;
        try {
            $lock = Cache::lock(self::LOCK_KEY, 90);
            if (! $lock->get()) {
                // Lock viejo (>2 min sin tick): liberar y reintentar una vez.
                $last = Cache::get(self::CACHE_KEY_LAST_TICK);
                $stale = true;
                if (is_string($last)) {
                    try {
                        $stale = \Carbon\Carbon::parse($last)->lt(now()->subMinutes(2));
                    } catch (Throwable) {
                        $stale = true;
                    }
                }
                if ($stale) {
                    try {
                        $lock->forceRelease();
                    } catch (Throwable) {
                    }
                    $lock = Cache::lock(self::LOCK_KEY, 90);
                    if (! $lock->get()) {
                        return response()->json([
                            'ok' => true,
                            'skipped' => true,
                            'message' => 'Scheduler tick already running',
                            'last_tick_at' => Cache::get(self::CACHE_KEY_LAST_TICK),
                        ]);
                    }
                } else {
                    return response()->json([
                        'ok' => true,
                        'skipped' => true,
                        'message' => 'Scheduler tick already running',
                        'last_tick_at' => Cache::get(self::CACHE_KEY_LAST_TICK),
                    ]);
                }
            }
        } catch (Throwable $e) {
            // Cache sin locks (p. ej. array): continuar sin mutex.
            Log::channel('scheduler')->warning('scheduler.tick.lock_unavailable', [
                'error' => $e->getMessage(),
            ]);
            $lock = null;
        }

        $started = microtime(true);

        try {
            @set_time_limit(0);
            Artisan::call('schedule:run');
            $scheduleOutput = trim(Artisan::output());

            // Un lote en este request; si queda cola, el comando spawnea procesos
            // en background (sin necesidad de tener el panel MVC abierto).
            $queueLimit = max(1, min(20, (int) $request->query('image_limit', 12)));
            Artisan::call('erp:process-image-queue', [
                '--limit' => $queueLimit,
                '--chain' => 0,
            ]);
            $queueOutput = trim(Artisan::output());

            $output = trim(implode("\n", array_filter([
                $scheduleOutput !== '' ? $scheduleOutput : null,
                $queueOutput !== '' ? 'erp:process-image-queue '.$queueOutput : null,
            ])));

            $elapsedMs = (int) round((microtime(true) - $started) * 1000);

            Cache::forever(self::CACHE_KEY_LAST_TICK, now()->toIso8601String());

            Log::channel('scheduler')->info('scheduler.tick.ok', [
                'elapsed_ms' => $elapsedMs,
                'output' => mb_substr($output, 0, 2000),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'ok' => true,
                'elapsed_ms' => $elapsedMs,
                'output' => $output !== '' ? $output : 'No scheduled commands are ready to run.',
                'last_tick_at' => Cache::get(self::CACHE_KEY_LAST_TICK),
            ]);
        } catch (Throwable $e) {
            report($e);
            Log::channel('scheduler')->error('scheduler.tick.failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 500);
        } finally {
            try {
                $lock?->release();
            } catch (Throwable) {
            }
        }
    }
}
