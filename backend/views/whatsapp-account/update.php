<?php

/** @var yii\web\View $this */
/** @var common\models\WhatsappAccount $model */
/** @var array $empresas */

use yii\helpers\Html;

$this->title = 'Editar Instancia: ' . ($model->session_name ?: $model->phone_number);
$this->params['breadcrumbs'][] = ['label' => 'Instancias WhatsApp', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->session_name ?: $model->phone_number, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>
<div class="whatsapp-account-update">
    <?= $this->render('_form', ['model' => $model, 'empresas' => $empresas]) ?>
</div>
