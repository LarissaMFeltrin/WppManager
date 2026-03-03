<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;
use common\models\Atendente;

$this->title = 'Atendentes';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="atendente-index">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-tie"></i> <?= Html::encode($this->title) ?></h3>
            <div class="card-tools">
                <?= Html::a('<i class="fas fa-plus"></i> Novo Atendente', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'layout' => "{items}\n{pager}",
                'tableOptions' => ['class' => 'table table-striped table-hover'],
                'columns' => [
                    'id',
                    'nome',
                    'email',
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $badges = [
                                Atendente::STATUS_ONLINE => '<span class="badge badge-success">Online</span>',
                                Atendente::STATUS_OFFLINE => '<span class="badge badge-secondary">Offline</span>',
                                Atendente::STATUS_OCUPADO => '<span class="badge badge-warning">Ocupado</span>',
                            ];
                            return $badges[$model->status] ?? '<span class="badge badge-light">' . Html::encode($model->status) . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'empresa_id',
                        'label' => 'Empresa',
                        'value' => function ($model) {
                            return $model->empresa ? $model->empresa->nome : '-';
                        },
                    ],
                    [
                        'label' => 'Conversas',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $pct = $model->max_conversas > 0 ? round(($model->conversas_ativas / $model->max_conversas) * 100) : 0;
                            $color = $pct >= 80 ? 'danger' : ($pct >= 50 ? 'warning' : 'success');
                            return '<span class="badge badge-' . $color . '">' . $model->conversas_ativas . '/' . $model->max_conversas . '</span>';
                        },
                    ],
                    'ultimo_acesso:datetime',
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {update} {delete}',
                        'buttons' => [
                            'view' => function ($url) {
                                return Html::a('<i class="fas fa-eye"></i>', $url, ['class' => 'btn btn-info btn-xs', 'title' => 'Visualizar']);
                            },
                            'update' => function ($url) {
                                return Html::a('<i class="fas fa-edit"></i>', $url, ['class' => 'btn btn-primary btn-xs', 'title' => 'Editar']);
                            },
                            'delete' => function ($url) {
                                return Html::a('<i class="fas fa-trash"></i>', $url, [
                                    'class' => 'btn btn-danger btn-xs',
                                    'title' => 'Excluir',
                                    'data' => [
                                        'confirm' => 'Tem certeza que deseja excluir este atendente?',
                                        'method' => 'post',
                                    ],
                                ]);
                            },
                        ],
                    ],
                ],
            ]) ?>
        </div>
    </div>

</div>
