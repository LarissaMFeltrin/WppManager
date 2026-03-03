<?php

/** @var yii\web\View $this */
/** @var common\models\Atendente $model */
/** @var array $empresas */

use yii\helpers\Html;

$this->title = 'Editar Atendente: ' . $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Atendentes', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nome, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>
<div class="atendente-update">
    <?= $this->render('_form', ['model' => $model, 'empresas' => $empresas, 'accounts' => $accounts, 'selectedAccounts' => $selectedAccounts]) ?>
</div>
