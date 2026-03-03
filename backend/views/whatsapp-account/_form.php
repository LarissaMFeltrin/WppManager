<?php

/** @var yii\web\View $this */
/** @var common\models\WhatsappAccount $model */
/** @var array $empresas */
/** @var yii\widgets\ActiveForm $form */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$user = Yii::$app->user->identity;
$isAdmin = $user && $user->isAdmin();
?>

<div class="whatsapp-account-form">
    <?php $form = ActiveForm::begin(); ?>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <?php if ($isAdmin): ?>
                <div class="col-md-6">
                    <?= $form->field($model, 'empresa_id')->dropDownList($empresas, ['prompt' => '-- Selecione a Empresa --']) ?>
                </div>
                <?php endif; ?>
                <div class="col-md-6">
                    <?= $form->field($model, 'phone_number')->textInput(['maxlength' => true, 'placeholder' => '5511999999999']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'session_name')->textInput(['maxlength' => true, 'placeholder' => 'Nome identificador da sessao']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'is_active')->dropDownList([
                        1 => 'Ativo',
                        0 => 'Inativo',
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'is_connected')->dropDownList([
                        1 => 'Conectado',
                        0 => 'Desconectado',
                    ]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'owner_jid')->textInput(['maxlength' => true, 'placeholder' => 'JID do proprietario (preenchido automaticamente)']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'service_port')->textInput([
                        'type' => 'number',
                        'placeholder' => '3000',
                        'min' => 3000,
                        'max' => 3999,
                    ])->hint('Porta do servico Node.js (ex: 3000, 3001)') ?>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <?= Html::submitButton('<i class="fas fa-save"></i> Salvar', ['class' => 'btn btn-success']) ?>
            <?= Html::a('<i class="fas fa-arrow-left"></i> Voltar', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
