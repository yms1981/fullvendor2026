<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">

        <style>
            .figure-fila {
                width: 6em !important;   
                height: 6em !important; 
                overflow:hidden !important; 
                margin:auto !important;
            }
        </style>
</head>

<body>
    <button onclick="imprimirPagina()">Imprimir esta página</button>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Id Producto</th>
                    <th>Imagen</th>
                    <th>Producto</th>
                    <th>SKU</th>
                </tr>
            </thead>
            <tbody id="tbody">
            </tbody>
        </table>
    </div>
    <div class="w-100 d-flex justify-content-center align-items-center" style="height: 100vh;">
        <div class="spinner-border spinner-border-lg" style="width: 4rem;height: 4rem;" role="status">
            <span class="visually-hidden"></span>
        </div>
    </div>

    <script>
    let catalog_id = '<?= $catalog_id ?>';
    </script>

    <script src="<?= base_url('companyasset/company/app.js?v=' . $version) ?>"></script>
    <?php if(isset($js)): ?>
    <?php foreach($js as $js): ?>
    <script src="<?= base_url('companyasset/company/'.$js. '?v='.$version)?>"></script>
    <?php endforeach;?>
    <?php endif;?>
</body>

</html>