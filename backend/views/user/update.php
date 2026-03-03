<?php

/** @var yii\web\View $this */
/** @var common\models\User $model */
/** @var common\models\Atendente $atendente */
/** @var array $empresas */
/** @var array $accounts */
/** @var array $selectedAccounts */

use yii\helpers\Html;

$this->title = 'Editar Usuario: ' . $model->username;
$this->params['breadcrumbs'][] = ['label' => 'Usuarios', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->username, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>
<div class="user-update">
    <?= $this->render('_form', [
        'model' => $model,
        'empresas' => $empresas,
        'accounts' => $accounts,
        'atendente' => $atendente,
        'selectedAccounts' => $selectedAccounts,
    ]) ?>
</div>
