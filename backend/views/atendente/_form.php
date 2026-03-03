<?php

/** @var yii\web\View $this */
/** @var common\models\Atendente $model */
/** @var array $empresas */
/** @var array $accounts */
/** @var array $selectedAccounts */
/** @var yii\widgets\ActiveForm $form */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\Atendente;

$user = Yii::$app->user->identity;
$isAdmin = $user && $user->isAdmin();
?>

<div class="atendente-form">
    <?php $form = ActiveForm::begin(); ?>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'nome')->textInput(['maxlength' => true, 'placeholder' => 'Nome completo']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'placeholder' => 'email@exemplo.com']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'senha')->passwordInput(['maxlength' => true, 'placeholder' => $model->isNewRecord ? 'Senha' : 'Deixe em branco para manter', 'value' => '']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'status')->dropDownList([
                        Atendente::STATUS_ONLINE => 'Online',
                        Atendente::STATUS_OFFLINE => 'Offline',
                        Atendente::STATUS_OCUPADO => 'Ocupado',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'max_conversas')->textInput(['type' => 'number', 'min' => 1, 'max' => 50]) ?>
                </div>
            </div>
            <?php if ($isAdmin): ?>
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'empresa_id')->dropDownList($empresas, ['prompt' => '-- Selecione a Empresa --']) ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($accounts)): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label><i class="fab fa-whatsapp"></i> Instancias WhatsApp</label>
                        <div class="row">
                            <?php foreach ($accounts as $accountId => $accountName): ?>
                            <div class="col-md-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="account-<?= $accountId ?>"
                                           name="account_ids[]"
                                           value="<?= $accountId ?>"
                                           <?= in_array($accountId, $selectedAccounts ?? []) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="account-<?= $accountId ?>">
                                        <?= Html::encode($accountName) ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="form-text text-muted">Selecione quais instancias este atendente pode atender.</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <?= Html::submitButton('<i class="fas fa-save"></i> Salvar', ['class' => 'btn btn-success']) ?>
            <?= Html::a('<i class="fas fa-arrow-left"></i> Voltar', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>
