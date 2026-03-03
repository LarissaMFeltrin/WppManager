<?php

/** @var yii\web\View $this */
/** @var common\models\User $model */
/** @var common\models\Atendente $atendente */
/** @var array $empresas */
/** @var array $accounts */
/** @var array $selectedAccounts */

$this->title = 'Novo Usuario';
$this->params['breadcrumbs'][] = ['label' => 'Usuarios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-create">
    <?= $this->render('_form', [
        'model' => $model,
        'empresas' => $empresas,
        'accounts' => $accounts,
        'atendente' => $atendente,
        'selectedAccounts' => $selectedAccounts,
    ]) ?>
</div>
