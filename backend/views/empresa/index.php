<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use common\models\Empresa;

$this->title = 'Empresas';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="empresa-index">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
            <div class="card-tools">
                <?= Html::a('<i class="fas fa-plus"></i> Nova Empresa', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
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
                    'cnpj',
                    'telefone',
                    'email',
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function ($model) {
                            if ($model->status == Empresa::STATUS_ATIVO) {
                                return '<span class="badge badge-success">Ativo</span>';
                            }
                            return '<span class="badge badge-danger">Inativo</span>';
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
                                        'confirm' => 'Tem certeza que deseja excluir esta empresa?',
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
