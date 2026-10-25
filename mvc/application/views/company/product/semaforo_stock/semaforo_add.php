<?php
/**
 * @var array $pageTexts the translated string conglomerated for this view
 * @var object $product The customer groups/ price lists available for the company
 */
?>
<div class="fv-product-sheet mb-2" id="stock_traffic_light">
    <div class="card-header">
        <h6><?= $pageTexts['stock_traffic_light'] ?></h6>
    </div>
    <div class="card-body">

        <div class="row">
            <div class="col-4">
                <div class="form-group">
                    <div class="d-flex">
                        <label for="">
                            <div class="cuadro" style="background-color: #CF1C00;"></div>
                        </label>
                        <h6 class="text-bold">&nbsp; <?= $pageTexts['stock_traffic_light_red'] ?> </h6>
                    </div>
                    <input type="number" class="form-control text-center" name="sem_red_hasta">
                </div>
            </div>
            <div class="col-4">
                <div class="form-group">
                    <div class="d-flex">
                        <label for="">
                            <div class="cuadro" style="background-color: #ffc107;"></div>
                        </label>
                        <h6 class="text-bold">&nbsp; <?= $pageTexts['stock_traffic_light_yellow'] ?> </h6>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <input type="number" tabindex="-1" readonly class="form-control text-center" name="sem_amarillo_desde">
                        </div>
                        <div class="col-6">
                            <input type="number" class="form-control text-center" name="sem_amarillo_hasta">
                            <div class="invalid-feedback"><?= $pageTexts['validation_semaforo'] ?>.</div>
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
                        <h6 class="text-bold">&nbsp; <?= $pageTexts['stock_traffic_light_green'] ?> </h6>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <input type="number" tabindex="-1" readonly class="form-control text-center" name="sem_verde_desde">
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
