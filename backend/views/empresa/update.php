<?php

/** @var yii\web\View $this */
/** @var common\models\Empresa $model */

use yii\helpers\Html;

$this->title = 'Editar Empresa: ' . $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Empresas', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nome, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>
<div class="empresa-update">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
