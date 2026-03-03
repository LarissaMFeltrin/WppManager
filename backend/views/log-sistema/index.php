<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var string $tipo */
/** @var string $nivel */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use common\models\LogSistema;

$this->title = 'Logs do Sistema';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="log-sistema-index">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-clipboard-list"></i> <?= Html::encode($this->title) ?></h3>
        </div>
        <div class="card-body">
            <!-- Filtros -->
            <form method="get" action="<?= Url::to(['index']) ?>" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <select name="tipo" class="form-control">
                            <option value="">-- Todos os Tipos --</option>
                            <option value="<?= LogSistema::TIPO_WEBHOOK ?>" <?= $tipo === LogSistema::TIPO_WEBHOOK ? 'selected' : '' ?>>Webhook</option>
                            <option value="<?= LogSistema::TIPO_API ?>" <?= $tipo === LogSistema::TIPO_API ? 'selected' : '' ?>>API</option>
                            <option value="<?= LogSistema::TIPO_ATENDIMENTO ?>" <?= $tipo === LogSistema::TIPO_ATENDIMENTO ? 'selected' : '' ?>>Atendimento</option>
                            <option value="<?= LogSistema::TIPO_INFO ?>" <?= $tipo === LogSistema::TIPO_INFO ? 'selected' : '' ?>>Info</option>
                            <option value="<?= LogSistema::TIPO_ERRO ?>" <?= $tipo === LogSistema::TIPO_ERRO ? 'selected' : '' ?>>Erro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="nivel" class="form-control">
                            <option value="">-- Todos os Niveis --</option>
                            <option value="<?= LogSistema::NIVEL_DEBUG ?>" <?= $nivel === LogSistema::NIVEL_DEBUG ? 'selected' : '' ?>>Debug</option>
                            <option value="<?= LogSistema::NIVEL_INFO ?>" <?= $nivel === LogSistema::NIVEL_INFO ? 'selected' : '' ?>>Info</option>
                            <option value="<?= LogSistema::NIVEL_WARNING ?>" <?= $nivel === LogSistema::NIVEL_WARNING ? 'selected' : '' ?>>Warning</option>
                            <option value="<?= LogSistema::NIVEL_ERROR ?>" <?= $nivel === LogSistema::NIVEL_ERROR ? 'selected' : '' ?>>Error</option>
                            <option value="<?= LogSistema::NIVEL_CRITICAL ?>" <?= $nivel === LogSistema::NIVEL_CRITICAL ? 'selected' : '' ?>>Critical</option>
                        </select>
                    </div>
                    <div class="col-md-4">
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
                'tableOptions' => ['class' => 'table table-striped table-hover table-sm'],
                'emptyText' => '<div class="text-center p-4 text-muted"><i class="fas fa-clipboard-check" style="font-size:2rem;"></i><p class="mt-2">Nenhum log encontrado.</p></div>',
                'columns' => [
                    'id',
                    [
                        'attribute' => 'tipo',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $colors = [
                                LogSistema::TIPO_WEBHOOK => 'primary',
                                LogSistema::TIPO_API => 'success',
                                LogSistema::TIPO_ATENDIMENTO => 'info',
                                LogSistema::TIPO_INFO => 'secondary',
                                LogSistema::TIPO_ERRO => 'danger',
                            ];
                            $color = $colors[$model->tipo] ?? 'light';
                            return '<span class="badge badge-' . $color . '">' . Html::encode($model->tipo) . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'nivel',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $colors = [
                                LogSistema::NIVEL_DEBUG => 'secondary',
                                LogSistema::NIVEL_INFO => 'info',
                                LogSistema::NIVEL_WARNING => 'warning',
                                LogSistema::NIVEL_ERROR => 'danger',
                                LogSistema::NIVEL_CRITICAL => 'dark',
                            ];
                            $color = $colors[$model->nivel] ?? 'light';
                            return '<span class="badge badge-' . $color . '">' . Html::encode($model->nivel) . '</span>';
                        },
                    ],
                    [
                        'attribute' => 'mensagem',
                        'value' => function ($model) {
                            return mb_substr($model->mensagem, 0, 80) . (mb_strlen($model->mensagem) > 80 ? '...' : '');
                        },
                    ],
                    'ip_origem',
                    'criada_em:datetime',
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view}',
                        'buttons' => [
                            'view' => function ($url) {
                                return Html::a('<i class="fas fa-eye"></i> Detalhes', $url, ['class' => 'btn btn-info btn-xs']);
                            },
                        ],
                    ],
                ],
            ]) ?>
        </div>
    </div>

</div>
