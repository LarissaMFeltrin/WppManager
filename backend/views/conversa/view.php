<?php

/** @var yii\web\View $this */
/** @var common\models\Conversa $model */

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\Conversa;

$this->title = 'Conversa #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Fila de Espera', 'url' => ['fila']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="conversa-view">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
            <div class="card-tools">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Voltar', ['fila'], ['class' => 'btn btn-default btn-sm']) ?>
            </div>
        </div>
        <div class="card-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'cliente_nome',
                    'cliente_numero',
                    [
                        'attribute' => 'atendente_id',
                        'value' => $model->atendente ? $model->atendente->nome : '-',
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => function () use ($model) {
                            $badges = [
                                Conversa::STATUS_AGUARDANDO => '<span class="badge badge-warning">Aguardando</span>',
                                Conversa::STATUS_EM_ATENDIMENTO => '<span class="badge badge-info">Em Atendimento</span>',
                                Conversa::STATUS_FINALIZADA => '<span class="badge badge-secondary">Finalizada</span>',
                            ];
                            return $badges[$model->status] ?? Html::encode($model->status);
                        },
                    ],
                    [
                        'attribute' => 'bloqueada',
                        'format' => 'raw',
                        'value' => $model->bloqueada ? '<span class="badge badge-danger">Sim</span>' : '<span class="badge badge-success">Nao</span>',
                    ],
                    'notas:ntext',
                    'iniciada_em:datetime',
                    'atendida_em:datetime',
                    'finalizada_em:datetime',
                    'ultima_msg_em:datetime',
                ],
            ]) ?>
        </div>
    </div>

</div>
