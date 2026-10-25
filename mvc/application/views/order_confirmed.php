<?php
/**
 * Respuesta pública tras confirmar/rechazar orden desde el correo (products/order_confirm, order_reject).
 *
 * @var string $mensaje HTML desde cmp-orders
 * @var array $lang_order Bloque cmp-orders (api/lang/*.json)
 * @var array $order_meta Datos básicos de orden para mostrar en la pantalla pública
 */
if (!isset($lang_order) || !is_array($lang_order)) {
    $lang_order = [];
}
$note = isset($lang_order['order_confirmation_page_note']) ? (string) $lang_order['order_confirmation_page_note'] : '';
$pageTitle = isset($lang_order['corfimacion_orden']) ? (string) $lang_order['corfimacion_orden'] : '';
$btnBack = isset($lang_order['order_atrass']) ? (string) $lang_order['order_atrass'] : '';
$mensajeHtml = isset($mensaje) ? (string) $mensaje : '';
if (!isset($order_meta) || !is_array($order_meta)) {
    $order_meta = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle !== '' ? strip_tags($pageTitle) : ' ', ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --r: #c9162d;
            --r-soft: rgba(201, 22, 45, 0.08);
            --k: #0d0d0d;
            --w: #ffffff;
            --paper: #fafafa;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 1.0625rem;
            line-height: 1.65;
            color: var(--k);
            background:
                radial-gradient(1200px 600px at 10% -10%, var(--r-soft), transparent 55%),
                radial-gradient(900px 500px at 100% 30%, rgba(13, 13, 13, 0.04), transparent 50%),
                var(--paper);
        }

        .oc-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: clamp(1.5rem, 5vw, 3rem);
        }

        .oc-card {
            width: 100%;
            max-width: 40rem;
            background: var(--w);
            border-radius: 20px;
            box-shadow:
                0 2px 4px rgba(13, 13, 13, 0.04),
                0 20px 50px -20px rgba(13, 13, 13, 0.18);
            overflow: hidden;
            border: 1px solid rgba(13, 13, 13, 0.06);
            animation: oc-pop 0.55s cubic-bezier(0.22, 1, 0.36, 1);
        }

        @keyframes oc-pop {
            from { opacity: 0; transform: translateY(16px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .oc-top {
            position: relative;
            padding: 2rem 2rem 1.75rem;
            background: linear-gradient(145deg, var(--k) 0%, #1a1a1a 100%);
            color: var(--w);
        }

        .oc-top::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--r) 0%, #e11d48 50%, var(--r) 100%);
        }

        .oc-brand-line {
            width: 48px;
            height: 3px;
            border-radius: 2px;
            background: var(--r);
            margin-bottom: 1rem;
        }

        .oc-kicker {
            margin: 0;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            opacity: 0.92;
        }

        .oc-main {
            padding: 2rem 2rem 2.25rem;
        }

        .oc-lead {
            margin: 0 0 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--r);
        }

        .oc-message {
            margin: 0;
            font-size: clamp(1.375rem, 4vw, 1.75rem);
            font-weight: 700;
            line-height: 1.35;
            letter-spacing: -0.02em;
            color: var(--k);
        }

        .oc-message p {
            margin: 0 0 0.5rem;
        }

        .oc-message p:last-child {
            margin-bottom: 0;
        }

        .oc-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(13, 13, 13, 0.12), transparent);
            margin: 1.75rem 0;
        }

        .oc-note {
            margin: 0;
            font-size: 1rem;
            font-weight: 400;
            color: var(--k);
            opacity: 0.82;
        }

        .oc-actions {
            margin-top: 1.75rem;
        }

        .oc-meta {
            margin-top: 1.25rem;
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .oc-meta-item {
            border: 1px solid rgba(13, 13, 13, 0.1);
            border-left: 3px solid var(--r);
            border-radius: 10px;
            padding: 0.65rem 0.8rem;
            background: #fff;
        }

        .oc-meta-label {
            margin: 0 0 0.15rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: rgba(13, 13, 13, 0.7);
            font-weight: 700;
        }

        .oc-meta-value {
            margin: 0;
            font-size: 0.98rem;
            color: var(--k);
            font-weight: 600;
            word-break: break-word;
        }

        .oc-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.875rem;
            padding: 0 1.75rem;
            font-family: inherit;
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--w);
            text-decoration: none;
            background: var(--r);
            border: none;
            border-radius: 999px;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(201, 22, 45, 0.35);
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        .oc-btn:hover {
            background: #a01224;
            color: var(--w);
            transform: translateY(-1px);
            box-shadow: 0 8px 22px rgba(201, 22, 45, 0.4);
        }

        .oc-btn:focus-visible {
            outline: 3px solid var(--k);
            outline-offset: 3px;
        }

        @media (min-width: 640px) {
            .oc-card { max-width: 44rem; }
            .oc-main { padding: 2.25rem 2.5rem 2.5rem; }
            .oc-top { padding: 2.25rem 2.5rem 2rem; }
            .oc-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
</head>
<body>
<main class="oc-shell">
    <article class="oc-card">
        <header class="oc-top">
            <div class="oc-brand-line" aria-hidden="true"></div>
            <p class="oc-kicker"><?= $pageTitle !== '' ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : '—' ?></p>
        </header>
        <div class="oc-main">
            <div class="oc-message" role="status">
                <?php if ($mensajeHtml !== '') : ?>
                    <?= $mensajeHtml ?>
                <?php else : ?>
                    <p><?= htmlspecialchars($note !== '' ? $note : '—', ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
            <?php if ($note !== '' && $mensajeHtml !== '') : ?>
                <div class="oc-divider" aria-hidden="true"></div>
                <p class="oc-note"><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <?php
            $metaLabels = [
                'order_number' => (string) ($lang_order['order_number'] ?? ''),
                'customer' => (string) ($lang_order['customer'] ?? $lang_order['cutomer_order'] ?? ''),
                'company' => (string) ($lang_order['company_order'] ?? ''),
                'status' => (string) ($lang_order['status'] ?? ''),
                'date' => (string) ($lang_order['order_date'] ?? $lang_order['fecha_creacion_order'] ?? ''),
            ];
            $hasMeta = false;
            foreach ($order_meta as $mv) {
                if (is_string($mv) && trim($mv) !== '') {
                    $hasMeta = true;
                    break;
                }
            }
            ?>
            <?php if ($hasMeta) : ?>
                <div class="oc-meta" role="group">
                    <?php foreach ($metaLabels as $mk => $ml) : ?>
                        <?php $val = isset($order_meta[$mk]) ? trim((string) $order_meta[$mk]) : ''; ?>
                        <?php if ($val !== '' && $ml !== '') : ?>
                            <div class="oc-meta-item">
                                <p class="oc-meta-label"><?= htmlspecialchars($ml, ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="oc-meta-value"><?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($btnBack !== '') : ?>
                <div class="oc-actions">
                    <a href="#" class="oc-btn" id="oc-btn-back"><?= htmlspecialchars($btnBack, ENT_QUOTES, 'UTF-8') ?></a>
                </div>
            <?php endif; ?>
        </div>
    </article>
</main>
<script>
(function () {
    var btn = document.getElementById('oc-btn-back');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (window.history.length > 1) {
            window.history.back();
        } else {
            window.close();
        }
    });
})();
</script>
</body>
</html>
