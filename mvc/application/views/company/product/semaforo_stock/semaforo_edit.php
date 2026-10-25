<?php
/**
 * @var array|null $pageTexts the translated string conglomerated for this view
 * @var object|null $product The customer groups/ price lists available for the company
 * @var bool|null $show_leyend
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$product = is_object($product ?? null) ? $product : (object) [];
$showSemaforo = (int) ($product->show_semaforo_stock ?? 0);
$t = static function (string $key) use ($pageTexts): string {
    return htmlspecialchars((string) ($pageTexts[$key] ?? ''), ENT_QUOTES, 'UTF-8');
};
?>
<section class="fv-product-sheet mb-2" id="stock_traffic_light"<?php if ($showSemaforo === 0 && isset($show_leyend)): ?> style="display: none"<?php endif; ?> >
    <div class="card-header">
        <h6><?= $t('stock_traffic_light') ?></h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-4">
                <div class="form-group">
                    <div class="d-flex">
                        <label for="">
                            <div class="cuadro" style="background-color: #CF1C00;"></div>
                        </label>
                        <h6 class="text-bold">&nbsp; <?= $t('stock_traffic_light_red') ?> </h6>
                    </div>
                    <input type="number" class="form-control text-center" name="sem_red_hasta" value="<?= htmlspecialchars((string) ($product->sem_red_hasta ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="col-4">
                <div class="form-group">
                    <div class="d-flex">
                        <label for="">
                            <div class="cuadro" style="background-color: #ffc107;"></div>
                        </label>
                        <h6 class="text-bold">&nbsp; <?= $t('stock_traffic_light_yellow') ?> </h6>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <input readonly tabindex="-1" type="number" class="form-control text-center" value="<?= htmlspecialchars((string) ($product->sem_amarillo_desde ?? ''), ENT_QUOTES, 'UTF-8') ?>" name="sem_amarillo_desde">
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control text-center" value="<?= htmlspecialchars((string) ($product->sem_amarillo_hasta ?? ''), ENT_QUOTES, 'UTF-8') ?>" name="sem_amarillo_hasta">
                            <div class="invalid-feedback"><?= $t('validation_semaforo') ?>.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="form-group">
                    <div class="d-flex">
                        <label for="">
                            <div class="cuadro" style="background-color: #29b768;"></div>
                        </label>
                        <h6 class="text-bold">&nbsp; <?= $t('stock_traffic_light_green') ?> </h6>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <input readonly tabindex="-1" type="number" class="form-control text-center" name="sem_verde_desde" value="<?= htmlspecialchars((string) ($product->sem_verde_desde ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <?php if (@$show_leyend): ?>
                <?php $this->load->view("company/profile/leyenda-semaforo"); ?>
            <?php endif; ?>
        </div>
    </div>
</section>
