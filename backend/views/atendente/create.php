<?php

/** @var yii\web\View $this */
/** @var common\models\Atendente $model */
/** @var array $empresas */

$this->title = 'Novo Atendente';
$this->params['breadcrumbs'][] = ['label' => 'Atendentes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="atendente-create">
    <?= $this->render('_form', ['model' => $model, 'empresas' => $empresas, 'accounts' => $accounts, 'selectedAccounts' => $selectedAccounts]) ?>
</div>
