<?php

/** @var yii\web\View $this */
/** @var common\models\LogSistema $model */

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\LogSistema;

$this->title = 'Log #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Logs do Sistema', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="log-sistema-view">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
            <div class="card-tools">
                <?= Html::a('<i class="fas fa-arrow-left"></i> Voltar', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
            </div>
        </div>
        <div class="card-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    [
                        'attribute' => 'tipo',
                        'format' => 'raw',
                        'value' => function () use ($model) {
                            $colors = [
                                LogSistema::TIPO_AUTENTICACAO => 'primary',
                                LogSistema::TIPO_WHATSAPP => 'success',
                                LogSistema::TIPO_ATENDIMENTO => 'info',
                                LogSistema::TIPO_SISTEMA => 'secondary',
                                LogSistema::TIPO_ERRO => 'danger',
                            ];
                            $color = $colors[$model->tipo] ?? 'light';
                            return '<span class="badge badge-' . $color . '">' . Html::encode($model->tipo) . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'nivel',
                        'format' => 'raw',
                        'value' => function () use ($model) {
                            $colors = [
                                LogSistema::NIVEL_INFO => 'info',
                                LogSistema::NIVEL_WARNING => 'warning',
                                LogSistema::NIVEL_ERROR => 'danger',
                                LogSistema::NIVEL_DEBUG => 'secondary',
                            ];
                            $color = $colors[$model->nivel] ?? 'light';
                            return '<span class="badge badge-' . $color . '">' . Html::encode($model->nivel) . '</span>';
                        },
                    ],
                    'mensagem:ntext',
                    [
                        'attribute' => 'dados',
                        'format' => 'raw',
                        'value' => function () use ($model) {
                            if (empty($model->dados)) return '-';
                            $decoded = json_decode($model->dados, true);
                            if ($decoded !== null) {
                                return '<pre class="bg-light p-2" style="max-height:400px;overflow:auto;">' . Html::encode(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                            }
                            return '<pre class="bg-light p-2" style="max-height:400px;overflow:auto;">' . Html::encode($model->dados) . '</pre>';
                        },
                    ],
                    'ip_origem',
                    [
                        'attribute' => 'user_agent',
                        'value' => function () use ($model) {
                            return $model->user_agent ?: '-';
                        },
                    ],
                    'criada_em:datetime',
                ],
            ]) ?>
        </div>
    </div>

</div>
