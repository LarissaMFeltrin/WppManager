<?php

/** @var yii\web\View $this */
/** @var common\models\Empresa $model */
/** @var yii\widgets\ActiveForm $form */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\Empresa;
?>

<div class="empresa-form">
    <?php $form = ActiveForm::begin(); ?>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'nome')->textInput(['maxlength' => true, 'placeholder' => 'Nome da empresa']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'cnpj')->textInput(['maxlength' => true, 'placeholder' => '00.000.000/0000-00']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'telefone')->textInput(['maxlength' => true, 'placeholder' => '(00) 00000-0000']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'placeholder' => 'email@empresa.com']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'status')->dropDownList([
                        Empresa::STATUS_ATIVO => 'Ativo',
                        Empresa::STATUS_INATIVO => 'Inativo',
                    ]) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'logo')->textInput(['maxlength' => true, 'placeholder' => 'URL do logo']) ?>
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
