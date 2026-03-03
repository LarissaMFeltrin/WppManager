<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap4\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\bootstrap4\ActiveForm;
use yii\helpers\Html;

$this->title = 'Login';
?>
<div class="card card-outline card-primary">
    <div class="card-header text-center">
        <a href="<?= Yii::$app->homeUrl ?>" class="h1">
            <b>WPP</b> Manager
        </a>
    </div>
    <div class="card-body">
        <p class="login-box-msg">Faca login para iniciar sua sessao</p>

        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'fieldConfig' => [
                'template' => '<div class="input-group mb-3">{input}<div class="input-group-append"><div class="input-group-text"><span class="{icon}"></span></div></div></div>{error}',
            ],
        ]); ?>

        <div class="input-group mb-3">
            <?= $form->field($model, 'username', [
                'template' => '{input}<div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>{error}',
            ])->textInput(['autofocus' => true, 'placeholder' => 'Usuario', 'class' => 'form-control']) ?>
        </div>

        <div class="input-group mb-3">
            <?= $form->field($model, 'password', [
                'template' => '{input}<div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>{error}',
            ])->passwordInput(['placeholder' => 'Senha', 'class' => 'form-control']) ?>
        </div>

        <div class="row">
            <div class="col-8">
                <?= $form->field($model, 'rememberMe')->checkbox([
                    'template' => '<div class="icheck-primary">{input}{label}</div>',
                    'label' => 'Lembrar-me',
                ]) ?>
            </div>
            <div class="col-4">
                <?= Html::submitButton('Entrar', ['class' => 'btn btn-primary btn-block', 'name' => 'login-button']) ?>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
    <!-- /.card-body -->
</div>
<!-- /.card -->
