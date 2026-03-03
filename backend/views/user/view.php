<?php

/** @var yii\web\View $this */
/** @var common\models\User $model */

use yii\helpers\Html;
use yii\widgets\DetailView;
use common\models\User;
use common\models\Atendente;

$this->title = $model->username;
$this->params['breadcrumbs'][] = ['label' => 'Usuarios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$atendente = $model->atendente;
?>
<div class="user-view">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= Html::encode($this->title) ?></h3>
            <div class="card-tools">
                <?= Html::a('<i class="fas fa-edit"></i> Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-primary btn-sm']) ?>
                <?php if ($model->id != Yii::$app->user->id): ?>
                <?= Html::a('<i class="fas fa-trash"></i> Excluir', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger btn-sm',
                    'data' => [
                        'confirm' => 'Tem certeza que deseja excluir este usuario?',
                        'method' => 'post',
                    ],
                ]) ?>
                <?php endif; ?>
                <?= Html::a('<i class="fas fa-arrow-left"></i> Voltar', ['index'], ['class' => 'btn btn-default btn-sm']) ?>
            </div>
        </div>
        <div class="card-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    'username',
                    'email',
                    [
                        'attribute' => 'role',
                        'format' => 'raw',
                        'value' => function () use ($model) {
                            $badges = [
                                User::ROLE_ADMIN => '<span class="badge badge-danger">Administrador</span>',
                                User::ROLE_SUPERVISOR => '<span class="badge badge-warning">Supervisor</span>',
                                User::ROLE_AGENT => '<span class="badge badge-info">Atendente</span>',
                            ];
                            return $badges[$model->role] ?? Html::encode($model->role);
                        },
                    ],
                    [
                        'attribute' => 'empresa_id',
                        'value' => $model->empresa ? $model->empresa->nome : '-',
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => $model->status == User::STATUS_ACTIVE
                            ? '<span class="badge badge-success">Ativo</span>'
                            : '<span class="badge badge-danger">Inativo</span>',
                    ],
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
        </div>
    </div>

    <?php if ($atendente): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-headset"></i> Configurações de Atendimento</h3>
        </div>
        <div class="card-body">
            <?php
            $statusBadges = [
                Atendente::STATUS_ONLINE => '<span class="badge badge-success">Online</span>',
                Atendente::STATUS_OFFLINE => '<span class="badge badge-secondary">Offline</span>',
                Atendente::STATUS_OCUPADO => '<span class="badge badge-warning">Ocupado</span>',
            ];
            ?>
            <?= DetailView::widget([
                'model' => $atendente,
                'attributes' => [
                    [
                        'attribute' => 'status',
                        'label' => 'Status Atendimento',
                        'format' => 'raw',
                        'value' => $statusBadges[$atendente->status] ?? $atendente->status,
                    ],
                    [
                        'attribute' => 'max_conversas',
                        'label' => 'Máximo de Conversas',
                    ],
                    [
                        'attribute' => 'conversas_ativas',
                        'label' => 'Conversas Ativas',
                    ],
                    [
                        'label' => 'Instâncias WhatsApp',
                        'format' => 'raw',
                        'value' => function () use ($atendente) {
                            $accounts = $atendente->whatsappAccounts;
                            if (empty($accounts)) {
                                return '<span class="text-muted">Nenhuma instância vinculada</span>';
                            }
                            $badges = [];
                            foreach ($accounts as $acc) {
                                $badges[] = '<span class="badge badge-success mr-1"><i class="fab fa-whatsapp"></i> ' . Html::encode($acc->session_name) . '</span>';
                            }
                            return implode(' ', $badges);
                        },
                    ],
                ],
            ]) ?>
        </div>
    </div>
    <?php endif; ?>

</div>
