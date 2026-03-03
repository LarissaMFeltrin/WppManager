<?php

/** @var yii\web\View $this */
/** @var common\models\Empresa $model */

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\Empresa;

$this->title = $model->nome;
$this->params['breadcrumbs'][] = ['label' => 'Empresas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="empresa-view">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
            <div class="card-tools">
                <?= Html::a('<i class="fas fa-edit"></i> Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-primary btn-sm']) ?>
                <?= Html::a('<i class="fas fa-trash"></i> Excluir', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger btn-sm',
                    'data' => [
                        'confirm' => 'Tem certeza que deseja excluir esta empresa?',
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
                    'cnpj',
                    'telefone',
                    'email',
                    'logo',
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => $model->status == Empresa::STATUS_ATIVO
                            ? '<span class="badge badge-success">Ativo</span>'
                            : '<span class="badge badge-danger">Inativo</span>',
                    ],
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
        </div>
    </div>

</div>
