<?php

/** @var yii\web\View $this */
/** @var common\models\Atendente $model */

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\Atendente;

$this->title = $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Atendentes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="atendente-view">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
            <div class="card-tools">
                <?= Html::a('<i class="fas fa-edit"></i> Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-primary btn-sm']) ?>
                <?= Html::a('<i class="fas fa-trash"></i> Excluir', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger btn-sm',
                    'data' => [
                        'confirm' => 'Tem certeza que deseja excluir este atendente?',
                        'method' => 'post',
                    ],
                ]) ?>
                <?= Html::a('<i class="fas fa-arrow-left"></i> Voltar', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
            </div>
        </div>
        <div class="card-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'nome',
                    'email',
                    [
                        'attribute' => 'empresa_id',
                        'value' => $model->empresa ? $model->empresa->nome : '-',
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function () use ($model) {
                            $badges = [
                                Atendente::STATUS_ONLINE => '<span class="badge badge-success">Online</span>',
                                Atendente::STATUS_OFFLINE => '<span class="badge badge-secondary">Offline</span>',
                                Atendente::STATUS_OCUPADO => '<span class="badge badge-warning">Ocupado</span>',
                            ];
                            return $badges[$model->status] ?? Html::encode($model->status);
                        },
                    ],
                    'max_conversas',
                    'conversas_ativas',
                    'ultimo_acesso:datetime',
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
        </div>
    </div>

</div>
