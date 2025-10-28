<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">

    <title><?= lang('系统故障') ?></title>

    <style>
        <?= preg_replace('#[\r\n\t ]+#', ' ', file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'debug.css')) ?>
    </style>
</head>
<body>

    <div class="container text-center">

        <h1 class="headline"><?= lang('您的系统遇到了故障') ?></h1>

        <p class="lead"><?= lang('在index.php中开启开发者模式可以看到故障详细情况') ?></p>


    </div>

</body>

</html>
