<?php

/** @var yii\web\View $this */
/** @var common\models\Atendente[] $atendentes */
/** @var int $filaCount */
/** @var int $emAtendimentoCount */
/** @var int $atendentesOnline */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Supervisao em Tempo Real';
$this->params['breadcrumbs'][] = ['label' => 'Monitor', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$supervisaoJsonUrl = Url::to(['/monitor/supervisao-json']);
$messagesUrl = Url::to(['/chat/messages']);
?>

<style>
/* === TOPBAR === */
.sv-topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 16px; background: #075e54; color: #fff; border-radius: 4px 4px 0 0; margin-bottom: 0;
}
.sv-topbar .sv-title { font-size: 1rem; font-weight: 600; margin: 0; }
.sv-topbar .sv-stats { display: flex; gap: 16px; font-size: 0.78rem; }
.sv-topbar .sv-stats .sv-stat { display: flex; align-items: center; gap: 4px; }
.sv-topbar .sv-stats .sv-stat .badge { font-size: 0.7rem; }
.sv-topbar .sv-filter select { font-size: 0.78rem; padding: 2px 8px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.15); color: #fff; }
.sv-topbar .sv-filter select option { color: #333; }

/* === GRID === */
.sv-grid {
    display: flex; flex-wrap: wrap; gap: 8px; padding: 10px;
    background: #f4f4f4; min-height: calc(100vh - 180px); align-content: flex-start;
    border-radius: 0 0 4px 4px;
}

/* === MINI-SLOT === */
.sv-slot {
    width: 280px; height: 340px;
    background: #fff; border-radius: 6px; border: 1px solid #e0e0e0;
    display: flex; flex-direction: column; overflow: hidden;
    transition: box-shadow 0.2s, border-color 0.2s;
    position: relative;
}
.sv-slot:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    border-color: #075e54;
}

/* Header */
.sv-slot-header {
    padding: 6px 10px; background: #f8f9fa; border-bottom: 1px solid #e8e8e8;
    display: flex; align-items: center; gap: 8px; flex-shrink: 0; min-height: 42px;
}
.sv-slot-avatar { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 700; color: #fff; flex-shrink: 0; }
.sv-slot-avatar img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }
.sv-slot-info { flex: 1; min-width: 0; }
.sv-slot-name { font-size: 0.78rem; font-weight: 600; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sv-slot-number { font-size: 0.65rem; color: #999; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Atendente tooltip on hover */
.sv-slot-atendente {
    position: absolute; top: 0; left: 0; right: 0;
    background: rgba(7,94,84,0.92); color: #fff; padding: 6px 10px;
    font-size: 0.75rem; font-weight: 600; text-align: center; z-index: 5;
    opacity: 0; transition: opacity 0.2s; pointer-events: none;
    border-radius: 6px 6px 0 0;
}
.sv-slot:hover .sv-slot-atendente { opacity: 1; }

/* Messages area */
.sv-slot-msgs {
    flex: 1; overflow-y: auto; padding: 6px 8px;
    background-color: #efeae2;
    background-image: url("data:image/svg+xml,%3Csvg width='200' height='200' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='p' width='40' height='40' patternUnits='userSpaceOnUse' patternTransform='rotate(45)'%3E%3Ccircle cx='4' cy='4' r='1' fill='%23d6d0c5' opacity='0.5'/%3E%3Ccircle cx='24' cy='14' r='0.8' fill='%23d6d0c5' opacity='0.4'/%3E%3C/pattern%3E%3C/defs%3E%3Crect width='200' height='200' fill='url(%23p)'/%3E%3C/svg%3E");
    font-size: 0.72rem;
}
.sv-slot-msgs .sv-loading { text-align: center; padding: 20px; color: #999; font-size: 0.75rem; }

/* Mini bubbles */
.sv-slot-msgs .sv-msg { display: flex; margin-bottom: 2px; }
.sv-slot-msgs .sv-msg.sent { flex-direction: row-reverse; }
.sv-slot-msgs .sv-bubble {
    max-width: 85%; padding: 3px 7px; border-radius: 6px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.06); font-size: 0.72rem; line-height: 1.3;
}
.sv-slot-msgs .sv-bubble.received { background: #fff; border-top-left-radius: 0; }
.sv-slot-msgs .sv-bubble.sent { background: #d9fdd3; border-top-right-radius: 0; }
.sv-slot-msgs .sv-bubble .sv-sender { font-size: 0.6rem; font-weight: 600; color: #06cf9c; }
.sv-slot-msgs .sv-bubble .sv-text { color: #111b21; word-wrap: break-word; }
.sv-slot-msgs .sv-bubble .sv-media { font-size: 0.65rem; color: #667781; font-style: italic; }
.sv-slot-msgs .sv-bubble .sv-meta { text-align: right; font-size: 0.55rem; color: #667781; }
.sv-slot-msgs .sv-bubble .sv-meta .read { color: #53bdeb; }
.sv-slot-msgs .sv-bubble .sv-deleted { font-style: italic; color: #8696a0; font-size: 0.65rem; }
.sv-slot-msgs .sv-bubble .sv-sender-name { font-size: 0.55rem; color: #075e54; font-weight: 600; margin-right: 2px; }
.sv-slot-msgs .sv-date-sep { text-align: center; margin: 4px 0; }
.sv-slot-msgs .sv-date-sep span { background: #e1f3fb; padding: 1px 8px; border-radius: 6px; font-size: 0.6rem; color: #54656f; }
.sv-slot-msgs .sv-bubble .sv-quoted { background: rgba(0,0,0,0.05); border-left: 2px solid #06cf9c; border-radius: 3px; padding: 2px 5px; margin-bottom: 2px; font-size: 0.62rem; color: #667781; max-height: 30px; overflow: hidden; }

/* Footer (read-only) */
.sv-slot-footer {
    padding: 4px 10px; background: #f0f0f0; border-top: 1px solid #e0e0e0;
    font-size: 0.6rem; color: #aaa; text-align: center; flex-shrink: 0;
}

/* Empty state */
.sv-empty { display: flex; align-items: center; justify-content: center; width: 100%; min-height: 200px; color: #bbb; flex-direction: column; gap: 8px; }
.sv-empty i { font-size: 2rem; }

/* Flash on new msg */
.sv-slot-flash { animation: sv-flash 0.5s ease; }
@keyframes sv-flash { 0% { border-color: #e74c3c; box-shadow: 0 0 8px rgba(231,76,60,0.3); } 100% { border-color: #e0e0e0; box-shadow: none; } }

/* Responsive */
@media (max-width: 900px) { .sv-slot { width: calc(50% - 8px); } }
@media (max-width: 600px) { .sv-slot { width: 100%; height: 280px; } }
</style>

<!-- === TOPBAR === -->
<div class="sv-topbar">
    <div class="d-flex align-items-center" style="gap:12px;">
        <h5 class="sv-title"><i class="fas fa-eye"></i> Supervisao</h5>
        <div class="sv-stats">
            <span class="sv-stat"><i class="fas fa-user-check"></i> <span id="svOnline"><?= $atendentesOnline ?></span> online</span>
            <span class="sv-stat"><i class="fas fa-headset"></i> <span id="svAtendimento"><?= $emAtendimentoCount ?></span> atendendo</span>
            <span class="sv-stat"><i class="fas fa-inbox"></i> <span id="svFila"><?= $filaCount ?></span> na fila</span>
        </div>
    </div>
    <div class="sv-filter">
        <select id="svFiltro">
            <option value="">Todos atendentes</option>
            <?php foreach ($atendentes as $at): ?>
            <option value="<?= $at->id ?>"><?= Html::encode($at->nome) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- === GRID DE MINI-SLOTS === -->
<div class="sv-grid" id="svGrid">
    <div class="sv-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var supervisaoJsonUrl = '<?= $supervisaoJsonUrl ?>';
    var messagesUrl = '<?= $messagesUrl ?>';

    // Estado por conversa: { chatId: { lastMsgId, el } }
    var slotState = {};

    // === HELPERS ===
    function esc(t) { if (!t) return ''; var d = document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }
    function fmt(t) {
        t = esc(t);
        t = t.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" style="color:#027eb5;">$1</a>');
        t = t.replace(/\n/g, '<br>'); t = t.replace(/\*([^*]+)\*/g, '<b>$1</b>'); t = t.replace(/_([^_]+)_/g, '<i>$1</i>');
        return t;
    }
    function ajaxGet(url, cb) {
        var x = new XMLHttpRequest(); x.open('GET', url, true);
        x.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        x.onreadystatechange = function() { if (x.readyState === 4 && x.status === 200) { try { cb(JSON.parse(x.responseText)); } catch(e){} } };
        x.send();
    }
    var COLORS = ['#e17055','#00b894','#0984e3','#6c5ce7','#fdcb6e','#e84393','#00cec9','#d63031','#a29bfe','#55efc4','#fab1a0','#74b9ff'];
    function avatarColor(n) { var s=0; for(var i=0;i<(n||'').length;i++) s+=n.charCodeAt(i); return COLORS[s%COLORS.length]; }
    function initials(n) { if(!n) return '?'; var p=n.trim().split(/\s+/); return p.length>=2 ? (p[0][0]+p[p.length-1][0]).toUpperCase() : n[0].toUpperCase(); }

    // === BUILD SLOT HTML ===
    function buildSlot(conv) {
        var color = avatarColor(conv.cliente_nome);
        var ini = initials(conv.cliente_nome);
        var avatarHtml = conv.profile_picture_url
            ? '<img src="' + esc(conv.profile_picture_url) + '" onerror="this.outerHTML=\'<div style=background:' + color + ';width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.65rem;font-weight:700;color:#fff>' + esc(ini) + '</div>\'">'
            : '';
        var avatarDiv = conv.profile_picture_url
            ? '<div class="sv-slot-avatar">' + avatarHtml + '</div>'
            : '<div class="sv-slot-avatar" style="background:' + color + ';">' + esc(ini) + '</div>';

        var statusDot = conv.atendente_status === 'online' ? '#28a745' : (conv.atendente_status === 'ocupado' ? '#ffc107' : '#dc3545');

        return '<div class="sv-slot" data-chat-id="' + conv.chat_id + '" data-conv-id="' + conv.conversa_id + '">'
            + '<div class="sv-slot-atendente"><i class="fas fa-headset"></i> ' + esc(conv.atendente_nome) + ' <span style="opacity:0.7;">| ' + esc(conv.tempo_atendimento) + '</span></div>'
            + '<div class="sv-slot-header">'
            + avatarDiv
            + '<div class="sv-slot-info">'
            + '<div class="sv-slot-name">' + esc(conv.cliente_nome) + '</div>'
            + '<div class="sv-slot-number">' + esc(conv.cliente_numero) + '</div>'
            + '</div>'
            + '<span style="width:8px;height:8px;border-radius:50%;background:' + statusDot + ';flex-shrink:0;" title="' + esc(conv.atendente_nome) + ' (' + esc(conv.atendente_status) + ')"></span>'
            + '</div>'
            + '<div class="sv-slot-msgs" id="msgs-' + conv.chat_id + '">'
            + '<div class="sv-loading"><i class="fas fa-spinner fa-spin"></i></div>'
            + '</div>'
            + '<div class="sv-slot-footer"><i class="fas fa-eye"></i> ' + esc(conv.atendente_nome) + '</div>'
            + '</div>';
    }

    // === RENDER MESSAGES ===
    function renderMsgs(container, messages, append) {
        var html = '';
        var existingDates = container.querySelectorAll('.sv-date-sep span');
        var lastDate = existingDates.length > 0 ? existingDates[existingDates.length - 1].textContent : '';

        for (var i = 0; i < messages.length; i++) {
            var m = messages[i];
            if (container.querySelector('[data-mid="' + m.id + '"]')) continue;

            if (m.date_formatted && m.date_formatted !== lastDate) {
                html += '<div class="sv-date-sep"><span>' + esc(m.date_formatted) + '</span></div>';
                lastDate = m.date_formatted;
            }
            var cls = m.is_from_me ? 'sent' : 'received';
            html += '<div class="sv-msg ' + cls + '" data-mid="' + m.id + '">';
            html += '<div class="sv-bubble ' + cls + '">';

            if (m.is_deleted) {
                html += '<div class="sv-deleted"><i class="fas fa-ban"></i> Apagada</div>';
            } else {
                if (!m.is_from_me && m.sender_name) {
                    html += '<div class="sv-sender">' + esc(m.sender_name) + '</div>';
                }
                if (m.quoted_text) {
                    html += '<div class="sv-quoted">' + esc(m.quoted_text) + '</div>';
                }
                if (m.message_type && m.message_type !== 'text') {
                    var labels = {image:'Imagem',video:'Video',audio:'Audio',document:'Documento',sticker:'Sticker'};
                    var lbl = labels[m.message_type] || m.message_type;
                    if (m.media_url && (m.message_type === 'image' || m.message_type === 'sticker')) {
                        html += '<div><img src="' + esc(m.media_url) + '" style="max-width:140px;max-height:100px;border-radius:4px;display:block;margin-bottom:1px;" onerror="this.style.display=\'none\'"></div>';
                    } else {
                        html += '<div class="sv-media"><i class="fas fa-file"></i> ' + esc(lbl) + '</div>';
                    }
                }
                if (m.message_text) {
                    html += '<div class="sv-text">' + fmt(m.message_text) + '</div>';
                }
            }

            html += '<div class="sv-meta">';
            if (m.sent_by_user_name) html += '<span class="sv-sender-name">' + esc(m.sent_by_user_name) + '</span>';
            html += esc(m.time_formatted || '');
            if (m.is_from_me && m.status === 'read') html += ' <i class="fas fa-check-double read"></i>';
            else if (m.is_from_me && m.status === 'delivered') html += ' <i class="fas fa-check-double"></i>';
            else if (m.is_from_me && m.status === 'sent') html += ' <i class="fas fa-check"></i>';
            html += '</div></div></div>';
        }

        if (html) container.insertAdjacentHTML('beforeend', html);
    }

    // === REFRESH GRID ===
    function refreshGrid() {
        ajaxGet(supervisaoJsonUrl, function(resp) {
            if (!resp.success) return;

            // Update stats
            document.getElementById('svFila').textContent = resp.fila_count || 0;
            document.getElementById('svAtendimento').textContent = (resp.conversas || []).length;
            var onl = 0; (resp.atendentes||[]).forEach(function(a){ if(a.status==='online') onl++; });
            document.getElementById('svOnline').textContent = onl;

            // Filter
            var filtro = document.getElementById('svFiltro').value;
            var convs = resp.conversas || [];
            if (filtro) convs = convs.filter(function(c) { return String(c.atendente_id) === filtro; });

            var grid = document.getElementById('svGrid');

            if (convs.length === 0) {
                grid.innerHTML = '<div class="sv-empty"><i class="fas fa-check-circle" style="color:#28a745;"></i><span>Nenhuma conversa em atendimento</span></div>';
                slotState = {};
                return;
            }

            // Track existing slots
            var existingChatIds = {};
            var currentSlots = grid.querySelectorAll('.sv-slot');
            currentSlots.forEach(function(el) { existingChatIds[el.getAttribute('data-chat-id')] = el; });

            // Build new chat id set
            var newChatIds = {};
            convs.forEach(function(c) { newChatIds[c.chat_id] = c; });

            // Remove slots that no longer exist
            currentSlots.forEach(function(el) {
                var cid = el.getAttribute('data-chat-id');
                if (!newChatIds[cid]) {
                    el.remove();
                    delete slotState[cid];
                }
            });

            // Add new slots
            convs.forEach(function(c) {
                if (!existingChatIds[c.chat_id]) {
                    // Remove empty message if present
                    var emptyEl = grid.querySelector('.sv-empty');
                    if (emptyEl) emptyEl.remove();

                    grid.insertAdjacentHTML('beforeend', buildSlot(c));
                    slotState[c.chat_id] = { lastMsgId: 0, loaded: false };
                }
            });
        });
    }

    // === POLL MESSAGES FOR ALL SLOTS ===
    function pollAllMessages() {
        var slots = document.querySelectorAll('.sv-slot');
        slots.forEach(function(el) {
            var chatId = el.getAttribute('data-chat-id');
            if (!chatId) return;
            var state = slotState[chatId] || { lastMsgId: 0, loaded: false };
            slotState[chatId] = state;

            var url = messagesUrl + '?chat_id=' + chatId;
            if (state.loaded && state.lastMsgId > 0) {
                url += '&after_id=' + state.lastMsgId;
            }

            ajaxGet(url, function(resp) {
                if (!resp.success || !resp.messages) return;
                var container = document.getElementById('msgs-' + chatId);
                if (!container) return;

                if (!state.loaded) {
                    container.innerHTML = '';
                    state.loaded = true;
                }

                if (resp.messages.length > 0) {
                    var hadMsgs = state.lastMsgId > 0;
                    renderMsgs(container, resp.messages, true);
                    state.lastMsgId = resp.messages[resp.messages.length - 1].id;
                    container.scrollTop = container.scrollHeight;

                    // Flash if new msgs arrived after initial load
                    if (hadMsgs) {
                        el.classList.add('sv-slot-flash');
                        setTimeout(function() { el.classList.remove('sv-slot-flash'); }, 600);
                    }
                } else if (!state.loaded || container.querySelector('.sv-loading')) {
                    container.innerHTML = '<div style="text-align:center;padding:20px;color:#bbb;font-size:0.7rem;">Sem mensagens</div>';
                }
            });
        });
    }

    // === INIT ===
    document.getElementById('svFiltro').addEventListener('change', function() {
        slotState = {};
        document.getElementById('svGrid').innerHTML = '<div class="sv-empty"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';
        refreshGrid();
        setTimeout(pollAllMessages, 500);
    });

    refreshGrid();
    setTimeout(pollAllMessages, 800);

    // Polling: grid a cada 10s, mensagens a cada 5s
    setInterval(refreshGrid, 10000);
    setInterval(pollAllMessages, 5000);
});
</script>
