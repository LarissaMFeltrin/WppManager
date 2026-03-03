<?php

/** @var yii\web\View $this */
/** @var common\models\WhatsappAccount $model */
/** @var array $empresas */

$this->title = 'Nova Instancia WhatsApp';
$this->params['breadcrumbs'][] = ['label' => 'Instancias WhatsApp', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="whatsapp-account-create">
    <?= $this->render('_form', ['model' => $model, 'empresas' => $empresas]) ?>
</div>
