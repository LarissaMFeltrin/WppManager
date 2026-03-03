<?php

/** @var yii\web\View $this */
/** @var common\models\WhatsappAccount $model */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->session_name ?: $model->phone_number;
$this->params['breadcrumbs'][] = ['label' => 'Instancias WhatsApp', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="whatsapp-account-view">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
            <div class="card-tools">
                <?= Html::a('<i class="fab fa-whatsapp"></i> Conectar', ['connect', 'id' => $model->id], ['class' => 'btn btn-success btn-sm']) ?>
                <?= Html::a('<i class="fas fa-edit"></i> Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-primary btn-sm']) ?>
                <?= Html::a('<i class="fas fa-trash"></i> Excluir', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger btn-sm',
                    'data' => [
                        'confirm' => 'Tem certeza que deseja excluir esta instancia?',
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
                    [
                        'attribute' => 'empresa_id',
                        'value' => $model->empresa ? $model->empresa->nome : '-',
                    ],
                    'phone_number',
                    'session_name',
                    'owner_jid',
                    [
                        'attribute' => 'is_connected',
                        'format' => 'raw',
                        'value' => $model->is_connected
                            ? '<span class="badge badge-success"><i class="fas fa-circle"></i> Online</span>'
                            : '<span class="badge badge-danger"><i class="fas fa-circle"></i> Offline</span>',
                    ],
                    [
                        'attribute' => 'is_active',
                        'format' => 'raw',
                        'value' => $model->is_active
                            ? '<span class="badge badge-success">Ativo</span>'
                            : '<span class="badge badge-secondary">Inativo</span>',
                    ],
                    [
                        'attribute' => 'service_port',
                        'value' => $model->service_port ?: 'Nao configurada',
                    ],
                    'last_connection:datetime',
                    'last_full_sync:datetime',
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-headset"></i> Atendentes Vinculados</h3>
        </div>
        <div class="card-body">
            <?php $atendentes = $model->atendentes; ?>
            <?php if (empty($atendentes)): ?>
                <p class="text-muted">Nenhum atendente vinculado a esta instancia.</p>
            <?php else: ?>
                <ul class="list-unstyled">
                    <?php foreach ($atendentes as $at): ?>
                        <li class="mb-1">
                            <i class="fas fa-user"></i>
                            <?= Html::encode($at->nome) ?>
                            <?php
                            $statusBadges = [
                                'online' => '<span class="badge badge-success">Online</span>',
                                'offline' => '<span class="badge badge-secondary">Offline</span>',
                                'ocupado' => '<span class="badge badge-warning">Ocupado</span>',
                            ];
                            echo $statusBadges[$at->status] ?? '';
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

</div>
