<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>


    <style type="text/css">
    body {
        padding: 0;
        font-family: sans-serif;
        font-size: <?=$font_size ?>;
        line-height: 1.5em;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        padding: 0;
        font-family: sans-serif !important;
    }

    table {
        border-collapse: collapse;
    }

    #table-detalle thead tr th {
        background-color: #e2e6ea;
    }

    #table-detalle thead tr th,
    #table-detalle tbody tr td {
        vertical-align: top !important;
        padding: 3px !important;
        border: 0.3px solid #6c757d !important;
    }

    .underline-text {
        text-decoration: underline;
    }
    </style>

</head>

<body>
    <table style="width: 100%;">
        <tbody>
            <tr>
                <?php if(isset($data_extra["title"])): ?>
                <td style="text-align: center; width: 100%;">
                    <h3 class="underline-text"><?= strtoupper($data_extra["title"]) ?></h3>
                </td>
                <?php endif; ?>
            </tr>
        </tbody>
    </table>
    <table style="width: 100%;">
        <tbody>
            <tr>
                <?php if(isset($data_extra["bussiness_name"])): ?>
                <td>
                    <h3><?= strtoupper($lang['company'] .": ". $company->name) ?></h3>
                </td>
                <?php endif; ?>

                <?php if(isset($data_extra["date"])): ?>
                <td style="text-align: right;">
                    <h3><?= strtoupper($lang['date_emition'] .": ". $date) ?></h3>
                </td>
                <?php endif; ?>
            </tr>
        </tbody>

    </table>

    <table id="table-detalle" style="width: 100%;">
        <thead>
            <tr>
                <?php foreach($thead as $th): ?>
                <th><?= $th ?></th>
                <?php endforeach;?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($tbody as $tr): ?>
            <tr>
                <?php foreach($tr as $td): ?>
                <td><?= $td ?></td>
                <?php endforeach;?>
            </tr>
            <?php endforeach;?>
        </tbody>
    </table>



</body>

</html>