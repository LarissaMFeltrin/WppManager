<?php

/** @var yii\web\View $this */
/** @var common\models\User $model */
/** @var common\models\Atendente $atendente */
/** @var array $empresas */
/** @var array $accounts */
/** @var array $selectedAccounts */
/** @var yii\widgets\ActiveForm $form */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\models\User;
use common\models\Atendente;

$user = Yii::$app->user->identity;
$isAdmin = $user && $user->isAdmin();
?>

<div class="user-form">
    <?php $form = ActiveForm::begin(); ?>

    <?php if ($model->hasErrors()): ?>
    <div class="alert alert-danger">
        <h5><i class="icon fas fa-ban"></i> Corrija os erros abaixo:</h5>
        <?= Html::errorSummary($model, ['class' => 'mb-0']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user"></i> Dados do Usuário</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'username')->textInput(['maxlength' => true, 'placeholder' => 'Nome de usuário']) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'placeholder' => 'email@exemplo.com']) ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="password_plain"><?= $model->isNewRecord ? 'Senha *' : 'Nova Senha (deixe em branco para manter)' ?></label>
                        <input type="password" id="password_plain" name="password_plain" class="form-control" placeholder="<?= $model->isNewRecord ? 'Digite a senha' : 'Deixe em branco para manter' ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'role')->dropDownList([
                        User::ROLE_AGENT => 'Atendente',
                        User::ROLE_SUPERVISOR => 'Supervisor',
                        User::ROLE_ADMIN => 'Administrador',
                    ], ['prompt' => '-- Selecione o Perfil --', 'id' => 'user-role']) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'status')->dropDownList([
                        User::STATUS_ACTIVE => 'Ativo',
                        User::STATUS_INACTIVE => 'Inativo',
                    ]) ?>
                </div>
            </div>
            <?php if ($isAdmin): ?>
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'empresa_id')->dropDownList($empresas, ['prompt' => '-- Selecione a Empresa --']) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Card de configuracoes do Atendente -->
    <div class="card" id="atendente-config">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-headset"></i> Configurações de Atendimento</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($atendente, 'status')->dropDownList([
                        Atendente::STATUS_ONLINE => 'Online',
                        Atendente::STATUS_OFFLINE => 'Offline',
                        Atendente::STATUS_OCUPADO => 'Ocupado',
                    ], ['id' => 'atendente-status'])->label('Status Atendimento') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($atendente, 'max_conversas')->textInput(['type' => 'number', 'min' => 1, 'max' => 50, 'id' => 'atendente-max-conversas'])->label('Máximo de Conversas Simultâneas') ?>
                </div>
            </div>

            <?php if (!empty($accounts)): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label><i class="fab fa-whatsapp"></i> Instâncias WhatsApp</label>
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
                        <small class="form-text text-muted">Selecione quais instâncias este usuário pode atender.</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-footer">
            <?= Html::submitButton('<i class="fas fa-save"></i> Salvar', ['class' => 'btn btn-success']) ?>
            <?= Html::a('<i class="fas fa-arrow-left"></i> Voltar', ['index'], ['class' => 'btn btn-default']) ?>
        </div>
    </div>

    <?php ActiveForm::end(); ?>
</div>

