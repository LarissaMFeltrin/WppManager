<?php

/** @var yii\web\View $this */
/** @var int $instanciasOnline */
/** @var int $instanciasTotal */
/** @var int $filaCount */
/** @var int $emAtendimentoCount */
/** @var int $mensagensHoje */
/** @var common\models\WhatsappAccount[] $instancias */
/** @var common\models\Atendente[] $atendentes */
/** @var common\models\Conversa[] $conversas */
/** @var common\models\Message[] $ultimasMensagens */

use yii\helpers\Html;

$this->title = 'Monitor';
$this->params['breadcrumbs'][] = $this->title;
?>

<!-- Auto-refresh a cada 30s -->
<meta http-equiv="refresh" content="30">

<style>
.monitor-card .info-box { min-height: 80px; margin-bottom: 16px; }
.monitor-card .info-box-number { font-size: 1.6rem; }
.monitor-table th { font-size: 0.82rem; text-transform: uppercase; color: #6c757d; white-space: nowrap; }
.monitor-table td { font-size: 0.88rem; vertical-align: middle !important; }
.badge-online { background: #28a745; color: #fff; }
.badge-offline { background: #dc3545; color: #fff; }
.badge-ocupado { background: #ffc107; color: #333; }
.badge-aguardando { background: #17a2b8; color: #fff; }
.badge-em-atendimento { background: #28a745; color: #fff; }
.msg-direction { font-size: 0.75rem; }
.msg-direction.in { color: #28a745; }
.msg-direction.out { color: #007bff; }
.time-ago { color: #888; font-size: 0.82rem; }
.card-header { font-weight: 600; }
</style>

<!-- === CARDS DE METRICAS === -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="info-box monitor-card">
            <span class="info-box-icon bg-success elevation-1"><i class="fab fa-whatsapp"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Instancias Online</span>
                <span class="info-box-number"><?= $instanciasOnline ?> <small style="font-size:0.7rem;color:#888;">/ <?= $instanciasTotal ?></small></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box monitor-card">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Na Fila</span>
                <span class="info-box-number"><?= $filaCount ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box monitor-card">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-headset"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Em Atendimento</span>
                <span class="info-box-number"><?= $emAtendimentoCount ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box monitor-card">
            <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-envelope"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Mensagens Hoje</span>
                <span class="info-box-number"><?= number_format($mensagensHoje) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- === INSTANCIAS + ATENDENTES === -->
<div class="row">
    <!-- Instancias WhatsApp -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <i class="fab fa-whatsapp text-success"></i> Instancias WhatsApp
                <span class="badge badge-secondary float-right"><?= count($instancias) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm monitor-table mb-0">
                        <thead>
                            <tr>
                                <th>Sessao</th>
                                <th>Telefone</th>
                                <?php if (Yii::$app->user->identity->isAdmin()): ?>
                                <th>Empresa</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>Ultima Conexao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($instancias)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Nenhuma instancia cadastrada</td></tr>
                            <?php endif; ?>
                            <?php foreach ($instancias as $inst): ?>
                            <tr>
                                <td><?= Html::encode($inst->session_name) ?></td>
                                <td><?= Html::encode($inst->phone_number) ?></td>
                                <?php if (Yii::$app->user->identity->isAdmin()): ?>
                                <td><?= Html::encode($inst->empresa->nome ?? '-') ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($inst->is_connected): ?>
                                        <span class="badge badge-online">Online</span>
                                    <?php else: ?>
                                        <span class="badge badge-offline">Offline</span>
                                    <?php endif; ?>
                                </td>
                                <td class="time-ago">
                                    <?= $inst->last_connection ? Yii::$app->formatter->asRelativeTime($inst->last_connection) : '-' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Atendentes -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <i class="fas fa-headset text-info"></i> Atendentes
                <span class="badge badge-secondary float-right"><?= count($atendentes) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm monitor-table mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <?php if (Yii::$app->user->identity->isAdmin()): ?>
                                <th>Empresa</th>
                                <?php endif; ?>
                                <th>Status</th>
                                <th>Conversas</th>
                                <th>Ultimo Acesso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($atendentes)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Nenhum atendente cadastrado</td></tr>
                            <?php endif; ?>
                            <?php foreach ($atendentes as $at): ?>
                            <tr>
                                <td><?= Html::encode($at->nome) ?></td>
                                <?php if (Yii::$app->user->identity->isAdmin()): ?>
                                <td><?= Html::encode($at->empresa->nome ?? '-') ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php
                                    $statusBadge = [
                                        'online' => 'badge-online',
                                        'offline' => 'badge-offline',
                                        'ocupado' => 'badge-ocupado',
                                    ];
                                    $badgeClass = $statusBadge[$at->status] ?? 'badge-secondary';
                                    $statusLabel = ucfirst($at->status ?: 'offline');
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= $statusLabel ?></span>
                                </td>
                                <td>
                                    <strong><?= (int)$at->conversas_ativas ?></strong>
                                    <small class="text-muted">/ <?= (int)$at->max_conversas ?></small>
                                </td>
                                <td class="time-ago">
                                    <?= $at->ultimo_acesso ? Yii::$app->formatter->asRelativeTime($at->ultimo_acesso) : '-' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === CONVERSAS ATIVAS + ATIVIDADE RECENTE === -->
<div class="row">
    <!-- Conversas Ativas -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <i class="fas fa-comments text-success"></i> Conversas Ativas
                <span class="badge badge-secondary float-right"><?= count($conversas) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-sm monitor-table mb-0">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Atendente</th>
                                <th>Status</th>
                                <th>Inicio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($conversas)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">Nenhuma conversa ativa</td></tr>
                            <?php endif; ?>
                            <?php foreach ($conversas as $conv): ?>
                            <tr>
                                <td>
                                    <strong><?= Html::encode($conv->cliente_nome ?: $conv->cliente_numero) ?></strong>
                                    <?php if ($conv->cliente_nome): ?>
                                    <br><small class="text-muted"><?= Html::encode($conv->cliente_numero) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= Html::encode($conv->atendente->nome ?? '<em class="text-muted">Sem atendente</em>') ?></td>
                                <td>
                                    <?php if ($conv->status === 'aguardando'): ?>
                                        <span class="badge badge-aguardando">Aguardando</span>
                                    <?php elseif ($conv->status === 'em_atendimento'): ?>
                                        <span class="badge badge-em-atendimento">Em atendimento</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?= Html::encode(ucfirst($conv->status)) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="time-ago">
                                    <?= $conv->iniciada_em ? Yii::$app->formatter->asRelativeTime($conv->iniciada_em) : '-' ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Atividade Recente -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-light">
                <i class="fas fa-stream text-primary"></i> Atividade Recente
                <small class="float-right text-muted"><i class="fas fa-sync-alt"></i> Atualiza a cada 30s</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm monitor-table mb-0">
                        <tbody>
                            <?php if (empty($ultimasMensagens)): ?>
                            <tr><td class="text-center text-muted py-3">Nenhuma mensagem recente</td></tr>
                            <?php endif; ?>
                            <?php foreach ($ultimasMensagens as $msg): ?>
                            <tr>
                                <td style="width:24px;">
                                    <?php if ($msg->is_from_me): ?>
                                        <span class="msg-direction out" title="Enviada"><i class="fas fa-arrow-right"></i></span>
                                    <?php else: ?>
                                        <span class="msg-direction in" title="Recebida"><i class="fas fa-arrow-left"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= Html::encode($msg->chat->chat_name ?? $msg->chat->chat_id ?? '-') ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php
                                        $text = $msg->message_text;
                                        if (!$text) {
                                            $typeIcons = ['image' => 'Imagem', 'video' => 'Video', 'audio' => 'Audio', 'document' => 'Documento', 'sticker' => 'Sticker'];
                                            $text = $typeIcons[$msg->message_type] ?? $msg->message_type;
                                        }
                                        echo Html::encode(mb_strlen($text) > 60 ? mb_substr($text, 0, 60) . '...' : $text);
                                        ?>
                                    </small>
                                </td>
                                <td class="time-ago text-right" style="white-space:nowrap;">
                                    <?php
                                    if ($msg->timestamp) {
                                        echo date('H:i', $msg->timestamp);
                                    } elseif ($msg->created_at) {
                                        echo Yii::$app->formatter->asTime($msg->created_at, 'short');
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
