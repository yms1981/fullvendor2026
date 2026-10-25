
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"
    />

    <title></title>
    <style>

        @import url('https://fonts.googleapis.com/css?family=Poppins:200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i');
        @import url('https://fonts.googleapis.com/css?family=Rubik:300,300i,400,400i,500,500i,700,700i,900,900i');

        body {
            color: #828282;
            font-size: 15px;
            line-height: 25px;
            font-weight: 400;
            font-family: 'Rubik', sans-serif;
        }

        .error-content {
            position: relative;
            display: block;
            z-index: 3;
        }

        .error-content .title {
            position: relative;
            display: block;
            color: #e9212e;
            font-size: 25px;
            line-height: 30px;
            font-weight: 700;
            /*text-transform: uppercase;*/
            margin: 13px 0 14px;
        }

        .text-center {
            text-align: center!important;
        }
        * {
            margin: 0px;
            padding: 0px;
            border: none;
            outline: none;
            font-size: 100%;
        }


        .btn-one {
            position: relative;
            display: inline-block;
            background: #e9212e;
            color: #ffffff;
            font-size: 15px;
            line-height: 60px;
            font-weight: 500;
            text-transform: capitalize;
            font-family: 'Rubik', sans-serif;
            padding: 0 30px 0;
            transition: all 500ms ease;
        }

    </style>
</head>
<body>
    <div class="vh-100 vw-100 d-flex align-items-center justify-content-center">
       <div class="error-content text-center shadow p-5 mb-5 bg-body-tertiary rounded animate__animated animate__zoomIn" style="--animate-duration:0.2s;">
        <span style="font-size: 100px;">
            <i class="fa-regular fa-face-frown"></i>
        </span>
        <div class="title" style="font-family: Arial, Helvetica, sans-serif;"><?= $mensaje ?></div>
        <p>&nbsp;</p>
        <div class="button">
            <a class="btn-one" style="font-family: Arial, Helvetica, sans-serif;" href="https://mail.google.com"><?= $lang_order['order_atrass'] ?></a>
        </div>
    </div>
</div>
</body>
</html>

