<?php

/** @var yii\web\View $this */
/** @var int $totalConversas */
/** @var int $totalFinalizadas */
/** @var int $totalEmAtendimento */
/** @var int $totalNaFila */
/** @var array $statsAtendentes */
/** @var common\models\Atendente[] $atendentes */
/** @var common\models\Conversa[] $conversas */
/** @var int|null $filtroAtendenteId */
/** @var string|null $filtroStatus */
/** @var string $filtroPeriodo */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Historico de Conversas';
$this->params['breadcrumbs'][] = ['label' => 'Monitor', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.monitor-card .info-box { min-height: 80px; margin-bottom: 16px; }
.monitor-card .info-box-number { font-size: 1.6rem; }
.stats-table th { font-size: 0.82rem; text-transform: uppercase; color: #6c757d; white-space: nowrap; }
.stats-table td { font-size: 0.88rem; vertical-align: middle !important; }
.badge-finalizada { background: #6c757d; color: #fff; }
.badge-em-atendimento { background: #28a745; color: #fff; }
.badge-aguardando { background: #ffc107; color: #333; }
.badge-devolvida { background: #dc3545; color: #fff; }
.time-muted { color: #888; font-size: 0.82rem; }
.filter-form { background: #f8f9fa; padding: 12px 16px; border-radius: 4px; margin-bottom: 16px; }
.filter-form select { margin-right: 8px; }
.hist-table td { font-size: 0.85rem; }
.hist-table th { font-size: 0.78rem; text-transform: uppercase; color: #6c757d; }
.hist-table tr { cursor: pointer; }
.hist-table tr:hover { background: #e8f4fd !important; }
.hist-table tr.active-row { background: #d1ecf1 !important; }

/* === DRAWER MENSAGENS === */
.msg-drawer-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.35);
    z-index: 1050;
}
.msg-drawer-overlay.open { display: block; }
.msg-drawer {
    display: none;
    position: fixed;
    top: 0; right: 0;
    width: 500px;
    height: 100%;
    background: #fff;
    box-shadow: -4px 0 20px rgba(0,0,0,0.18);
    z-index: 1051;
    flex-direction: column;
}
.msg-drawer.open { display: flex; }
.msg-drawer-header {
    padding: 14px 18px;
    background: #075e54;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.msg-drawer-header h5 { margin: 0; font-size: 1rem; font-weight: 600; }
.msg-drawer-header .drawer-client-info { font-size: 0.78rem; color: rgba(255,255,255,0.7); }
.msg-drawer-header .btn-close-drawer {
    background: none; border: none; color: rgba(255,255,255,0.8); font-size: 1.3rem; cursor: pointer;
}
.msg-drawer-header .btn-close-drawer:hover { color: #fff; }
.msg-drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    background-color: #efeae2;
    background-image: url("data:image/svg+xml,%3Csvg width='200' height='200' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='p' width='40' height='40' patternUnits='userSpaceOnUse' patternTransform='rotate(45)'%3E%3Ccircle cx='4' cy='4' r='1' fill='%23d6d0c5' opacity='0.5'/%3E%3Ccircle cx='24' cy='14' r='0.8' fill='%23d6d0c5' opacity='0.4'/%3E%3Ccircle cx='14' cy='28' r='1.2' fill='%23d6d0c5' opacity='0.35'/%3E%3Ccircle cx='34' cy='34' r='0.6' fill='%23d6d0c5' opacity='0.45'/%3E%3Cpath d='M8 20 Q10 18 12 20 Q10 22 8 20Z' fill='none' stroke='%23d6d0c5' stroke-width='0.5' opacity='0.3'/%3E%3Cpath d='M28 6 L30 4 L32 6 L30 8Z' fill='none' stroke='%23d6d0c5' stroke-width='0.5' opacity='0.25'/%3E%3Cpath d='M18 8 Q20 6 22 8' fill='none' stroke='%23d6d0c5' stroke-width='0.5' opacity='0.3'/%3E%3Cpath d='M2 34 L4 32 L6 34' fill='none' stroke='%23d6d0c5' stroke-width='0.5' opacity='0.3'/%3E%3C/pattern%3E%3C/defs%3E%3Crect width='200' height='200' fill='url(%23p)'/%3E%3C/svg%3E");
}
.msg-drawer-body .drawer-loading {
    text-align: center; padding: 40px; color: #999; font-size: 0.85rem;
}
.msg-drawer-body .drawer-empty {
    text-align: center; padding: 40px; color: #bbb;
}
.msg-drawer-body .drawer-date-sep {
    text-align: center; margin: 8px 0;
}
.msg-drawer-body .drawer-date-sep span {
    background: #e1f3fb; padding: 2px 10px; border-radius: 8px; font-size: 0.72rem; color: #54656f;
}
.msg-drawer-body .drawer-msg {
    display: flex; margin-bottom: 4px; clear: both;
}
.msg-drawer-body .drawer-msg.sent { flex-direction: row-reverse; }
.msg-drawer-body .drawer-bubble {
    max-width: 80%; padding: 6px 10px; border-radius: 8px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.08); font-size: 0.82rem; position: relative;
}
.msg-drawer-body .drawer-bubble.received { background: #fff; border-top-left-radius: 0; }
.msg-drawer-body .drawer-bubble.sent { background: #d9fdd3; border-top-right-radius: 0; }
.msg-drawer-body .drawer-bubble .drawer-sender {
    font-size: 0.7rem; font-weight: 600; color: #06cf9c; margin-bottom: 1px;
}
.msg-drawer-body .drawer-bubble .drawer-text {
    font-size: 0.82rem; color: #111b21; line-height: 1.35; word-wrap: break-word;
}
.msg-drawer-body .drawer-bubble .drawer-media-label {
    font-size: 0.78rem; color: #667781; font-style: italic;
}
.msg-drawer-body .drawer-bubble .drawer-meta {
    text-align: right; font-size: 0.62rem; color: #667781; margin-top: 1px;
}
.msg-drawer-body .drawer-bubble .drawer-meta .msg-status.read { color: #53bdeb; }
.msg-drawer-body .drawer-bubble .drawer-deleted {
    font-style: italic; color: #8696a0; font-size: 0.78rem;
}
.msg-drawer-body .drawer-bubble .drawer-sender-name {
    font-size: 0.6rem; color: #075e54; font-weight: 600; margin-right: 3px;
}
.msg-drawer-body .drawer-bubble .drawer-quoted {
    background: rgba(0,0,0,0.05); border-left: 3px solid #06cf9c; border-radius: 4px;
    padding: 4px 8px; margin-bottom: 4px; font-size: 0.75rem; color: #667781;
    max-height: 60px; overflow: hidden;
}
.msg-drawer-body .drawer-bubble.sent .drawer-quoted { border-left-color: #075e54; }

@media (max-width: 600px) {
    .msg-drawer { width: 100%; }
}
</style>

<!-- === CARDS DE RESUMO === -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="info-box monitor-card">
            <span class="info-box-icon bg-secondary elevation-1"><i class="fas fa-comments"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Conversas</span>
                <span class="info-box-number"><?= number_format($totalConversas) ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box monitor-card">
            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Finalizadas</span>
                <span class="info-box-number"><?= number_format($totalFinalizadas) ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box monitor-card">
            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-headset"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Em Atendimento</span>
                <span class="info-box-number"><?= number_format($totalEmAtendimento) ?></span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="info-box monitor-card">
            <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Na Fila</span>
                <span class="info-box-number"><?= number_format($totalNaFila) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- === TABELA ATENDENTES === -->
<div class="card">
    <div class="card-header bg-light">
        <i class="fas fa-users text-primary"></i> Desempenho por Atendente
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm stats-table mb-0">
                <thead>
                    <tr>
                        <th>Atendente</th>
                        <th>Status</th>
                        <th class="text-center">Em Atendimento</th>
                        <th class="text-center">Finalizadas</th>
                        <th class="text-center">Devolvidas</th>
                        <th class="text-center">Tempo Medio</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($statsAtendentes)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">Nenhum atendente cadastrado</td></tr>
                    <?php endif; ?>
                    <?php foreach ($statsAtendentes as $stat): ?>
                    <?php
                        $statusBadges = [
                            'online' => '<span class="badge badge-success">Online</span>',
                            'offline' => '<span class="badge badge-danger">Offline</span>',
                            'ocupado' => '<span class="badge badge-warning">Ocupado</span>',
                        ];
                        $badge = $statusBadges[$stat['atendente_status']] ?? '<span class="badge badge-secondary">-</span>';

                        $tempoMedio = '-';
                        if ($stat['tempo_medio_min'] !== null) {
                            $min = (int) $stat['tempo_medio_min'];
                            if ($min >= 60) {
                                $tempoMedio = floor($min / 60) . 'h ' . ($min % 60) . 'min';
                            } else {
                                $tempoMedio = $min . ' min';
                            }
                        }

                        $isSelected = ($filtroAtendenteId == $stat['id']);
                    ?>
                    <tr class="<?= $isSelected ? 'table-primary' : '' ?>">
                        <td><strong><?= Html::encode($stat['nome']) ?></strong></td>
                        <td><?= $badge ?></td>
                        <td class="text-center">
                            <?php if ((int)$stat['em_atendimento'] > 0): ?>
                                <span class="badge badge-em-atendimento"><?= $stat['em_atendimento'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ((int)$stat['finalizadas'] > 0): ?>
                                <span class="badge badge-finalizada"><?= $stat['finalizadas'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ((int)$stat['devolvidas'] > 0): ?>
                                <span class="badge badge-devolvida"><?= $stat['devolvidas'] ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center time-muted"><?= $tempoMedio ?></td>
                        <td class="text-right">
                            <a href="<?= Url::to(['conversas', 'atendente_id' => $stat['id']]) ?>" class="btn btn-xs btn-outline-primary">
                                Ver <i class="fas fa-arrow-right"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- === FILTROS + HISTORICO === -->
<div class="card">
    <div class="card-header bg-light">
        <i class="fas fa-history text-info"></i> Historico de Conversas
        <?php if ($filtroAtendenteId): ?>
            <?php
            $nomeAtendente = '-';
            foreach ($atendentes as $at) {
                if ($at->id == $filtroAtendenteId) { $nomeAtendente = $at->nome; break; }
            }
            ?>
            <span class="badge badge-primary ml-2"><?= Html::encode($nomeAtendente) ?></span>
        <?php endif; ?>
        <span class="badge badge-secondary float-right"><?= count($conversas) ?> registro(s)</span>
    </div>
    <div class="card-body pb-0">
        <!-- Filtros -->
        <form method="get" action="<?= Url::to(['conversas']) ?>" class="filter-form d-flex align-items-center flex-wrap">
            <div class="form-group mb-1 mr-2">
                <label class="sr-only">Buscar cliente</label>
                <input type="text" name="busca" class="form-control form-control-sm" placeholder="Buscar nome ou numero..." value="<?= Html::encode($filtroBusca ?? '') ?>" style="min-width: 180px;">
            </div>
            <div class="form-group mb-1 mr-2">
                <label class="sr-only">Atendente</label>
                <select name="atendente_id" class="form-control form-control-sm">
                    <option value="">Todos os atendentes</option>
                    <?php foreach ($atendentes as $at): ?>
                    <option value="<?= $at->id ?>" <?= $filtroAtendenteId == $at->id ? 'selected' : '' ?>>
                        <?= Html::encode($at->nome) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-1 mr-2 d-flex align-items-center">
                <?php
                $statusOptions = [
                    'aguardando' => ['label' => 'Aguardando', 'badge' => 'warning'],
                    'em_atendimento' => ['label' => 'Em Atendimento', 'badge' => 'info'],
                    'finalizada' => ['label' => 'Finalizada', 'badge' => 'success'],
                ];
                $filtroStatusArr = is_array($filtroStatus) ? $filtroStatus : ($filtroStatus ? [$filtroStatus] : []);
                foreach ($statusOptions as $val => $opt): ?>
                <div class="custom-control custom-checkbox mr-2">
                    <input type="checkbox" class="custom-control-input" id="status-<?= $val ?>" name="status[]" value="<?= $val ?>" <?= in_array($val, $filtroStatusArr) ? 'checked' : '' ?>>
                    <label class="custom-control-label badge badge-<?= $opt['badge'] ?>" for="status-<?= $val ?>" style="cursor:pointer; font-size: 0.85em; padding: 4px 8px;"><?= $opt['label'] ?></label>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="form-group mb-1 mr-2">
                <label class="sr-only">Periodo</label>
                <select name="periodo" class="form-control form-control-sm">
                    <option value="tudo" <?= $filtroPeriodo === 'tudo' ? 'selected' : '' ?>>Todo periodo</option>
                    <option value="hoje" <?= $filtroPeriodo === 'hoje' ? 'selected' : '' ?>>Hoje</option>
                    <option value="7dias" <?= $filtroPeriodo === '7dias' ? 'selected' : '' ?>>Ultimos 7 dias</option>
                    <option value="30dias" <?= $filtroPeriodo === '30dias' ? 'selected' : '' ?>>Ultimos 30 dias</option>
                </select>
            </div>
            <div class="form-group mb-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                <a href="<?= Url::to(['conversas']) ?>" class="btn btn-sm btn-default ml-1">Limpar</a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm hist-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Numero</th>
                        <th>Atendente</th>
                        <th>Status</th>
                        <th>Inicio</th>
                        <th>Atendida</th>
                        <th>Finalizada</th>
                        <th>Duracao</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($conversas)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-3">Nenhuma conversa encontrada</td></tr>
                    <?php endif; ?>
                    <?php foreach ($conversas as $conv): ?>
                    <?php
                        // Status badge
                        $statusBadges = [
                            'aguardando' => '<span class="badge badge-aguardando">Aguardando</span>',
                            'em_atendimento' => '<span class="badge badge-em-atendimento">Em atendimento</span>',
                            'finalizada' => '<span class="badge badge-finalizada">Finalizada</span>',
                        ];
                        $statusHtml = $statusBadges[$conv->status] ?? Html::encode($conv->status);

                        // Se foi devolvida, adicionar badge extra
                        if ($conv->devolvida_por && $conv->status === 'aguardando') {
                            $statusHtml .= ' <span class="badge badge-devolvida" title="Devolvida">Dev.</span>';
                        }

                        // Duracao
                        $duracao = '-';
                        if ($conv->atendida_em) {
                            $fim = $conv->finalizada_em ?: date('Y-m-d H:i:s');
                            $diff = strtotime($fim) - strtotime($conv->atendida_em);
                            if ($diff > 0) {
                                if ($diff >= 3600) {
                                    $duracao = floor($diff / 3600) . 'h ' . floor(($diff % 3600) / 60) . 'min';
                                } elseif ($diff >= 60) {
                                    $duracao = floor($diff / 60) . ' min';
                                } else {
                                    $duracao = $diff . 's';
                                }
                            }
                        }

                        // Formatar datas
                        $fmtDate = function($dt) {
                            if (!$dt) return '-';
                            return date('d/m H:i', strtotime($dt));
                        };
                    ?>
                    <tr class="conv-row"
                        data-chat-id="<?= (int)$conv->chat_id ?>"
                        data-cliente="<?= Html::encode($conv->cliente_nome ?: 'Cliente') ?>"
                        data-numero="<?= Html::encode($conv->cliente_numero) ?>"
                        data-atendente="<?= Html::encode($conv->atendente ? $conv->atendente->nome : '-') ?>">
                        <td class="text-muted"><?= $conv->id ?></td>
                        <td><strong><?= Html::encode($conv->cliente_nome ?: '-') ?></strong></td>
                        <td class="text-muted" style="font-size:0.8rem;"><?= Html::encode($conv->cliente_numero) ?></td>
                        <td><?= Html::encode($conv->atendente ? $conv->atendente->nome : '-') ?></td>
                        <td><?= $statusHtml ?></td>
                        <td class="time-muted"><?= $fmtDate($conv->iniciada_em) ?></td>
                        <td class="time-muted"><?= $fmtDate($conv->atendida_em) ?></td>
                        <td class="time-muted"><?= $fmtDate($conv->finalizada_em) ?></td>
                        <td class="time-muted"><?= $duracao ?></td>
                        <td class="text-right">
                            <?php if ($conv->chat_id): ?>
                            <button class="btn btn-xs btn-outline-info btn-ver-msgs" title="Ver mensagens">
                                <i class="fas fa-comments"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- === DRAWER DE MENSAGENS === -->
<div class="msg-drawer-overlay" id="msgDrawerOverlay"></div>
<div class="msg-drawer" id="msgDrawer">
    <div class="msg-drawer-header">
        <div>
            <h5><i class="fas fa-comments"></i> <span id="drawerClientName">-</span></h5>
            <div class="drawer-client-info">
                <span id="drawerClientNumber">-</span> &mdash; Atendente: <span id="drawerAtendente">-</span>
            </div>
        </div>
        <button class="btn-close-drawer" id="btnCloseDrawer"><i class="fas fa-times"></i></button>
    </div>
    <div class="msg-drawer-body" id="drawerBody">
        <div class="drawer-loading"><i class="fas fa-spinner fa-spin"></i> Carregando mensagens...</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var messagesUrl = '<?= Url::to(['/chat/messages']) ?>';
    var drawerOverlay = document.getElementById('msgDrawerOverlay');
    var drawer = document.getElementById('msgDrawer');
    var drawerBody = document.getElementById('drawerBody');
    var drawerClientName = document.getElementById('drawerClientName');
    var drawerClientNumber = document.getElementById('drawerClientNumber');
    var drawerAtendente = document.getElementById('drawerAtendente');

    function openDrawer(chatId, cliente, numero, atendente) {
        drawerClientName.textContent = cliente;
        drawerClientNumber.textContent = numero;
        drawerAtendente.textContent = atendente;
        drawerBody.innerHTML = '<div class="drawer-loading"><i class="fas fa-spinner fa-spin"></i> Carregando mensagens...</div>';
        drawerOverlay.classList.add('open');
        drawer.classList.add('open');

        var xhr = new XMLHttpRequest();
        xhr.open('GET', messagesUrl + '?chat_id=' + chatId, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status === 200) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.success && resp.messages && resp.messages.length > 0) {
                        renderMessages(resp.messages);
                    } else {
                        drawerBody.innerHTML = '<div class="drawer-empty"><i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>Nenhuma mensagem encontrada.</div>';
                    }
                } catch(e) {
                    drawerBody.innerHTML = '<div class="drawer-empty">Erro ao carregar mensagens.</div>';
                }
            } else {
                drawerBody.innerHTML = '<div class="drawer-empty">Erro ao carregar (HTTP ' + xhr.status + ').</div>';
            }
        };
        xhr.send();
    }

    function closeDrawer() {
        drawerOverlay.classList.remove('open');
        drawer.classList.remove('open');
        document.querySelectorAll('.conv-row').forEach(function(r) { r.classList.remove('active-row'); });
    }

    function renderMessages(messages) {
        var html = '';
        var lastDate = '';
        for (var i = 0; i < messages.length; i++) {
            var msg = messages[i];
            if (msg.date_formatted && msg.date_formatted !== lastDate) {
                html += '<div class="drawer-date-sep"><span>' + esc(msg.date_formatted) + '</span></div>';
                lastDate = msg.date_formatted;
            }
            var cls = msg.is_from_me ? 'sent' : 'received';
            html += '<div class="drawer-msg ' + cls + '">';
            html += '<div class="drawer-bubble ' + cls + '">';
            if (msg.is_deleted) {
                html += '<div class="drawer-deleted"><i class="fas fa-ban"></i> Mensagem apagada</div>';
            } else {
                if (!msg.is_from_me && msg.sender_name) {
                    html += '<div class="drawer-sender">' + esc(msg.sender_name) + '</div>';
                }
                if (msg.quoted_text) {
                    html += '<div class="drawer-quoted">' + esc(msg.quoted_text) + '</div>';
                }
                if (msg.message_type && msg.message_type !== 'text') {
                    var labels = {image:'Imagem',video:'Video',audio:'Audio',document:'Documento',sticker:'Sticker',location:'Localizacao',contact:'Contato'};
                    var label = labels[msg.message_type] || msg.message_type;
                    if (msg.media_url) {
                        if (msg.message_type === 'image' || msg.message_type === 'sticker') {
                            html += '<div><img src="' + esc(msg.media_url) + '" style="max-width:200px;max-height:150px;border-radius:6px;display:block;margin-bottom:2px;" onerror="this.style.display=\'none\'"></div>';
                        } else if (msg.message_type === 'audio') {
                            html += '<div><audio controls style="max-width:220px;height:32px;"><source src="' + esc(msg.media_url) + '"></audio></div>';
                        } else if (msg.message_type === 'video') {
                            html += '<div><video controls style="max-width:200px;max-height:150px;border-radius:6px;"><source src="' + esc(msg.media_url) + '"></video></div>';
                        } else {
                            html += '<div class="drawer-media-label"><i class="fas fa-file"></i> ' + esc(label) + '</div>';
                        }
                    } else {
                        html += '<div class="drawer-media-label"><i class="fas fa-file"></i> ' + esc(label) + '</div>';
                    }
                }
                if (msg.message_text) {
                    html += '<div class="drawer-text">' + formatText(msg.message_text) + '</div>';
                }
            }
            html += '<div class="drawer-meta">';
            if (msg.sent_by_user_name) {
                html += '<span class="drawer-sender-name">' + esc(msg.sent_by_user_name) + '</span>';
            }
            html += '<span>' + esc(msg.time_formatted || '') + '</span>';
            if (msg.is_from_me && msg.status) {
                var si = '', sc = '';
                if (msg.status === 'read') { si = 'fa-check-double'; sc = 'read'; }
                else if (msg.status === 'delivered') { si = 'fa-check-double'; }
                else if (msg.status === 'sent') { si = 'fa-check'; }
                else if (msg.status === 'pending') { si = 'fa-clock'; }
                if (si) html += ' <span class="msg-status ' + sc + '"><i class="fas ' + si + '"></i></span>';
            }
            html += '</div>';
            html += '</div></div>';
        }
        drawerBody.innerHTML = html;
        drawerBody.scrollTop = drawerBody.scrollHeight;
    }

    function esc(text) {
        if (!text) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(text));
        return d.innerHTML;
    }

    function formatText(text) {
        text = esc(text);
        text = text.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener" style="color:#027eb5;">$1</a>');
        text = text.replace(/\n/g, '<br>');
        text = text.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');
        text = text.replace(/_([^_]+)_/g, '<em>$1</em>');
        return text;
    }

    // Click on table row to open drawer
    document.querySelectorAll('.conv-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('button') || e.target.closest('a')) return;
            var chatId = this.getAttribute('data-chat-id');
            if (!chatId || chatId === '0') return;
            document.querySelectorAll('.conv-row').forEach(function(r) { r.classList.remove('active-row'); });
            this.classList.add('active-row');
            openDrawer(chatId, this.getAttribute('data-cliente'), this.getAttribute('data-numero'), this.getAttribute('data-atendente'));
        });

        var btn = row.querySelector('.btn-ver-msgs');
        if (btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var tr = this.closest('.conv-row');
                document.querySelectorAll('.conv-row').forEach(function(r) { r.classList.remove('active-row'); });
                tr.classList.add('active-row');
                openDrawer(tr.getAttribute('data-chat-id'), tr.getAttribute('data-cliente'), tr.getAttribute('data-numero'), tr.getAttribute('data-atendente'));
            });
        }
    });

    document.getElementById('btnCloseDrawer').addEventListener('click', closeDrawer);
    drawerOverlay.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeDrawer(); });
});
</script>
