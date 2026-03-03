<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;
use common\models\User;

$this->title = 'Usuarios';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users"></i> <?= Html::encode($this->title) ?></h3>
            <div class="card-tools">
                <?= Html::a('<i class="fas fa-plus"></i> Novo Usuario', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'layout' => "{items}\n{pager}",
                'tableOptions' => ['class' => 'table table-striped table-hover'],
                'columns' => [
                    'id',
                    'username',
                    'email',
                    [
                        'attribute' => 'role',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $badges = [
                                User::ROLE_ADMIN => '<span class="badge badge-danger">Administrador</span>',
                                User::ROLE_SUPERVISOR => '<span class="badge badge-warning">Supervisor</span>',
                                User::ROLE_AGENT => '<span class="badge badge-info">Atendente</span>',
                            ];
                            return $badges[$model->role] ?? '<span class="badge badge-secondary">' . Html::encode($model->role) . '</span>';
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
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return $model->status == User::STATUS_ACTIVE
                                ? '<span class="badge badge-success">Ativo</span>'
                                : '<span class="badge badge-danger">Inativo</span>';
                        },
                    ],
                    'created_at:datetime',
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
                                        'confirm' => 'Tem certeza que deseja excluir este usuario?',
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
