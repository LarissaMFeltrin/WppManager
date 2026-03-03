<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $search */
/** @var string $accountId */
/** @var array $accounts */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

$this->title = 'Contatos';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="contact-index">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-address-book"></i> <?= Html::encode($this->title) ?></h3>
        </div>
        <div class="card-body">
            <!-- Filtros -->
            <form method="get" action="<?= Url::to(['index']) ?>" class="mb-3">
                <div class="row">
                    <div class="col-md-5">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" name="search" class="form-control" placeholder="Buscar por nome ou numero..." value="<?= Html::encode($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select name="account_id" class="form-control">
                            <option value="">-- Todas as Instancias --</option>
                            <?php foreach ($accounts as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $accountId == $id ? 'selected' : '' ?>><?= Html::encode($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <?= Html::submitButton('<i class="fas fa-filter"></i> Filtrar', ['class' => 'btn btn-primary']) ?>
                        <?= Html::a('<i class="fas fa-times"></i> Limpar', ['index'], ['class' => 'btn btn-default']) ?>
                    </div>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'layout' => "{items}\n{pager}",
                'tableOptions' => ['class' => 'table table-striped table-hover'],
                'emptyText' => '<div class="text-center p-4 text-muted"><i class="fas fa-address-book" style="font-size:2rem;"></i><p class="mt-2">Nenhum contato encontrado.</p></div>',
                'columns' => [
                    [
                        'attribute' => 'name',
                        'label' => 'Nome',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $name = $model->name ?: '-';
                            $badges = '';
                            if ($model->is_business) $badges .= ' <span class="badge badge-info" style="font-size:0.65rem;">Business</span>';
                            if ($model->is_blocked) $badges .= ' <span class="badge badge-danger" style="font-size:0.65rem;">Bloqueado</span>';
                            return '<strong>' . \yii\helpers\Html::encode($name) . '</strong>' . $badges;
                        },
                    ],
                    [
                        'attribute' => 'phone_number',
                        'label' => 'Numero',
                    ],
                    [
                        'attribute' => 'account_id',
                        'label' => 'Instancia',
                        'value' => function ($model) {
                            return $model->whatsappAccount ? ($model->whatsappAccount->session_name ?: $model->whatsappAccount->phone_number) : '-';
                        },
                    ],
                    [
                        'attribute' => 'is_blocked',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return $model->is_blocked ? '<span class="badge badge-danger">Sim</span>' : '<span class="badge badge-success">Não</span>';
                        },
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => '',
                        'template' => '{enviar}',
                        'buttons' => [
                            'enviar' => function ($url, $model) {
                                if (strpos($model->jid, '@g.us') !== false) return '';
                                return Html::a(
                                    '<i class="fas fa-paper-plane"></i> Enviar Mensagem',
                                    ['/conversa/iniciar-conversa', 'contact_id' => $model->id],
                                    [
                                        'class' => 'btn btn-sm btn-success',
                                        'title' => 'Iniciar conversa com ' . ($model->name ?: $model->phone_number),
                                        'data-confirm' => 'Iniciar conversa com ' . ($model->name ?: $model->phone_number) . '?',
                                    ]
                                );
                            },
                        ],
                    ],
                ],
            ]) ?>
        </div>
    </div>

</div>
