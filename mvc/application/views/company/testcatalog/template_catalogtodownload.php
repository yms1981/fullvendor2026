<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <style type="text/css">
    body {
        padding: 0;
        font-family: sans-serif;
        font-size: 12px;
        line-height: 1.5em;
        color: #202124;

    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        padding: 0;
        font-family: sans-serif !important;
        color: #202124;
    }

    table {
        border-collapse: collapse;
    }

    .borde-address {
        border: 0.3px solid #6c757d;
        padding: 10px;
        border-radius: 10px;
        padding-top: 0;
        color: #202124;

    }

    ul {
        list-style: none;
        padding: 0;

    }

    ul li {
        border-bottom: 0.3px solid #6c757d;
        padding-bottom: 5px;
        color: #202124;

    }

    #table-detalle thead tr th {
        font-size: 14px;
    }

    #table-detalle thead tr th,
    #table-detalle tbody tr td {
        border-bottom: 0.3px solid #6c757d !important;
        color: #202124;
    }

    #table-encabezado thead tr th,
    #table-encabezado tbody tr td {
        border: none;
        color: #202124;
    }

    .border-si {
        border: none !important;
    }

    .text-center {
        text-align: center !important;
    }

    .text-right {
        text-align: right !important;
    }

    .btn {
        display: inline-block;
        font-weight: 400;
        color: #212529;
        text-align: center;
        vertical-align: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }

    .btn-success {

        color: #fff;
        background-color: #1EA910;
        border-color: #1EA910;
    }

    .btn-danger {
        color: #fff;
        background-color: #CF1C00;
        border-color: #CF1C00;
    }


    .badge {
        border-radius: 4px;
        font-size: 8px;
        font-weight: initial;
        line-height: 1;
        padding: 0.375rem 0.5625rem;
        font-family: sans-serif !important;
    }

    .badge-pill {
        padding-right: 0.6em;
        padding-left: 0.6em;
        border-radius: 10rem;
    }

    strong {
        font-size: 14px !important;
    }
    </style>
</head>

<body>

    <table>
        <thead>
            <th></th>
            <th>Products</th>
            <th>SKU</th>
        </thead>
        <tbody>
            <?php foreach($products as $pro): ?>
            <tr>
                <td>
                    <figure style="width: 6em; height: 6em; overflow:hidden; margin:auto;padding: 5px;">
                        <img style="width: 30px;height: 30px;"
                            src="<?= (($pro->images!= null && $pro->images!= '') && file_exists('./uploads/products/'.$company->company_id . '/'.$pro->images))?base_url('uploads/products/'.@$company->company_id.'/'.$pro->images).'?v='.$version: base_url('uploads/noimg.png') .'?v=' . $version ?>">
                    </figure>
                </td>
                <td><?= $pro->name ?></td>
                <td><?= $pro->sku ?></td>
                <td><?= $company->company_id ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>

</html>