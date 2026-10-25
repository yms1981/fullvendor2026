<?php

namespace App\Observers;

use App\Exceptions\FifoCreditException;
use App\Models\Order;
use App\Services\CustomerOrderProductFifoService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mantiene el control FIFO al crear/actualizar órdenes.
 * Las ventas (D) solo generan capas al completarse; las NC (C) intentan consumir al guardarse
 * en modo permisivo (no abortan la creación por qty/precio).
 */
class CustomerOrderProductFifoObserver
{
    public function __construct(
        private readonly CustomerOrderProductFifoService $fifo,
    ) {
    }

    public function saved(Order $order): void
    {
        if (! $this->shouldSync($order)) {
            return;
        }

        $tipo = strtoupper((string) ($order->tipo_d ?? 'D'));
        // NC: nunca bloquear el guardado por FIFO (app warehouse / móvil).
        $strict = $tipo !== 'C';

        try {
            $this->fifo->syncOrder(
                $order->fresh(['order_details.product', 'statusOrder']) ?? $order,
                $strict
            );
        } catch (FifoCreditException $e) {
            if ($tipo === 'C') {
                Log::warning('CustomerOrderProductFifoObserver::saved NC FIFO skipped', [
                    'order_id' => $order->order_id ?? null,
                    'error' => $e->errorKey,
                    'context' => $e->context,
                ]);

                return;
            }
            throw $e;
        } catch (Throwable $e) {
            Log::warning('CustomerOrderProductFifoObserver::saved failed', [
                'order_id' => $order->order_id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleted(Order $order): void
    {
        try {
            if (! $this->fifo->tablesReady()) {
                return;
            }
            $this->fifo->syncOrder($order, false);
        } catch (Throwable $e) {
            Log::warning('CustomerOrderProductFifoObserver::deleted failed', [
                'order_id' => $order->order_id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function shouldSync(Order $order): bool
    {
        if (! $this->fifo->tablesReady()) {
            return false;
        }

        $tipo = strtoupper((string) ($order->tipo_d ?? 'D'));
        if ($tipo === 'C') {
            return true;
        }

        if ($tipo !== 'D') {
            return false;
        }

        // Ventas: al crear, al cambiar estado (completado) o cliente/tipo.
        return $order->wasRecentlyCreated
            || $order->wasChanged('status_id')
            || $order->wasChanged('customer_id')
            || $order->wasChanged('tipo_d');
    }
}
