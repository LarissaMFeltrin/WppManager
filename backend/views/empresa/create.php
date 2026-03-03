<?php

/** @var yii\web\View $this */
/** @var common\models\Empresa $model */

$this->title = 'Nova Empresa';
$this->params['breadcrumbs'][] = ['label' => 'Empresas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="empresa-create">
    <?= $this->render('_form', ['model' => $model]) ?>
</div>
