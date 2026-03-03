<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Instancias WhatsApp';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="whatsapp-account-index">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
            <div class="card-tools">
                <?= Html::a('<i class="fas fa-plus"></i> Nova Instancia', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'layout' => "{items}\n{pager}",
                'tableOptions' => ['class' => 'table table-striped table-hover'],
                'columns' => [
                    'id',
                    'phone_number',
                    'session_name',
                    [
                        'attribute' => 'is_connected',
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->is_connected) {
                                return '<span class="badge badge-success"><i class="fas fa-circle"></i> Online</span>';
                            }
                            return '<span class="badge badge-danger"><i class="fas fa-circle"></i> Offline</span>';
                        },
                    ],
                    [
                        'attribute' => 'is_active',
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->is_active) {
                                return '<span class="badge badge-success">Ativo</span>';
                            }
                            return '<span class="badge badge-secondary">Inativo</span>';
                        },
                    ],
                    [
                        'attribute' => 'empresa_id',
                        'value' => function ($model) {
                            return $model->empresa ? $model->empresa->nome : '-';
                        },
                    ],
                    'last_connection:datetime',
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{connect} {view} {update} {delete}',
                        'buttons' => [
                            'connect' => function ($url, $model) {
                                return Html::a('<i class="fab fa-whatsapp"></i>', ['connect', 'id' => $model->id], [
                                    'class' => 'btn btn-success btn-xs',
                                    'title' => 'Conectar WhatsApp',
                                ]);
                            },
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
                                        'confirm' => 'Tem certeza que deseja excluir esta instancia?',
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
