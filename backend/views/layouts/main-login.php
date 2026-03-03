<?php

/** @var \yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;

\hail812\adminlte3\assets\AdminLteAsset::register($this);
\hail812\adminlte3\assets\FontAwesomeAsset::register($this);
$this->registerCssFile('https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback');
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> | <?= Yii::$app->params['adminlte']['title'] ?? 'WPP Manager' ?></title>
    <?php $this->head() ?>
    <style>
        .login-page {
            background: linear-gradient(135deg, #075e54 0%, #128c7e 50%, #25d366 100%);
            min-height: 100vh;
        }
        .login-box .card {
            border-radius: 10px;
            box-shadow: 0 0 30px rgba(0,0,0,0.3);
        }
        .login-logo a {
            color: #fff;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
        }
        .login-logo .brand-icon {
            font-size: 2rem;
            margin-right: 8px;
        }
    </style>
</head>
<body class="hold-transition login-page">
<?php $this->beginBody() ?>

<div class="login-box">
    <div class="login-logo">
        <a href="<?= Yii::$app->homeUrl ?>">
            <i class="fab fa-whatsapp brand-icon"></i>
            <b>WPP</b> Manager
        </a>
    </div>
    <!-- /.login-logo -->

    <?= $content ?>
</div>
<!-- /.login-box -->

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
