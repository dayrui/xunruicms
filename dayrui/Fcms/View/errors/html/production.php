<!doctype html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">

    <title>系统故障</title>

    <style type="text/css">
        <?= preg_replace('#[\r\n\t ]+#', ' ', file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'debug.css')) ?>
    </style>
</head>
<body>

<div class="container text-center">

    <h1 class="headline">系统故障</h1>

    <p class="lead">您的系统遇到了故障，请联系管理员处理</p>
    <p class="lead">在index.php中开启开发者模式可以看到故障详细情况</p>

</div>

</body>

</html>
