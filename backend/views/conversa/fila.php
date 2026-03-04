<?php

/** @var yii\web\View $this */
/** @var common\models\Conversa[] $conversas */
/** @var common\models\Message[] $previews */
/** @var common\models\Atendente|null $atendente */
/** @var int $filaCount */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Fila de Espera';
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.fila-card {
    border-left: 4px solid #ffc107;
    transition: box-shadow 0.2s;
    margin-bottom: 12px;
}
.fila-card:hover {
    box-shadow: 0 2px 12px rgba(0,0,0,0.12);
}
.fila-card.selected {
    border-left-color: #dc3545;
    background: #fff8f8;
}
.fila-card .card-body {
    padding: 12px 16px;
}
.fila-cliente {
    font-weight: 600;
    font-size: 1rem;
    color: #333;
}
.fila-numero {
    color: #888;
    font-size: 0.85rem;
}
.fila-preview {
    color: #666;
    font-size: 0.85rem;
    margin-top: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 400px;
}
.fila-tempo {
    color: #dc3545;
    font-size: 0.82rem;
    font-weight: 500;
}
.fila-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 10px;
    flex-shrink: 0;
}
.fila-avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #dfe6e9;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    flex-shrink: 0;
    color: #636e72;
    font-size: 1rem;
}
.fila-empty {
    text-align: center;
    padding: 60px 20px;
    color: #aaa;
}
.fila-empty i {
    font-size: 3rem;
    margin-bottom: 12px;
    color: #28a745;
}
.fila-badge {
    font-size: 1.1rem;
    padding: 4px 12px;
}
.bulk-bar {
    display: none;
    position: sticky;
    top: 0;
    z-index: 100;
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 4px;
    padding: 10px 16px;
    margin-bottom: 12px;
    align-items: center;
    justify-content: space-between;
}
.bulk-bar.visible {
    display: flex;
}
.fila-check {
    width: 18px;
    height: 18px;
    cursor: pointer;
    margin-right: 12px;
    flex-shrink: 0;
}
</style>

<div class="conversa-fila">
    <!-- Header com contadores -->
    <div class="row mb-3">
        <div class="col-md-8">
            <h4>
                <i class="fas fa-inbox text-warning"></i>
                <?= Html::encode($this->title) ?>
                <span class="badge badge-warning fila-badge" id="fila-count"><?= $filaCount ?></span>
            </h4>
        </div>
        <div class="col-md-4 text-right">
            <?php if ($atendente): ?>
                <span class="text-muted">
                    <i class="fas fa-headset"></i> <?= Html::encode($atendente->nome) ?>
                    &mdash; <?= (int)$atendente->conversas_ativas ?>/<?= (int)$atendente->max_conversas ?> conversas
                </span>
            <?php else: ?>
                <span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Voce nao esta vinculado como atendente</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra de acoes em massa -->
    <div class="bulk-bar" id="bulk-bar">
        <div>
            <label style="cursor:pointer; margin:0;">
                <input type="checkbox" id="select-all" class="fila-check" style="vertical-align:middle;">
                <strong>Selecionar todas</strong>
            </label>
            <span id="selected-count" class="ml-2 text-muted"></span>
        </div>
        <form id="bulk-form" method="post" action="<?= Url::to(['finalizar-massa']) ?>" style="display:inline;">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
            <button type="submit" class="btn btn-danger btn-sm" id="btn-finalizar-massa" disabled>
                <i class="fas fa-check-double"></i> Finalizar Selecionadas (<span id="selected-num">0</span>)
            </button>
        </form>
    </div>

    <!-- Container dos cards (atualizado via AJAX) -->
    <div id="fila-container"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var FILA_URL = <?= json_encode(Url::to(['fila-json'])) ?>;
    var PEGAR_URL = <?= json_encode(Url::to(['pegar'])) ?>;
    var CSRF_PARAM = <?= json_encode(Yii::$app->request->csrfParam) ?>;
    var CSRF_TOKEN = <?= json_encode(Yii::$app->request->csrfToken) ?>;
    var PODE_ATENDER = <?= json_encode($atendente && ($atendente->conversas_ativas < $atendente->max_conversas)) ?>;
    var container = document.getElementById('fila-container');
    var countBadge = document.getElementById('fila-count');
    var bulkBar = document.getElementById('bulk-bar');
    var selectAll = document.getElementById('select-all');
    var btnFinalizar = document.getElementById('btn-finalizar-massa');
    var selectedNum = document.getElementById('selected-num');
    var selectedCount = document.getElementById('selected-count');
    var bulkForm = document.getElementById('bulk-form');
    var selectedIds = {};  // IDs selecionados pelo usuario

    function escapeHtml(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function renderFila(conversas) {
        if (conversas.length === 0) {
            bulkBar.classList.remove('visible');
            container.innerHTML =
                '<div class="card"><div class="card-body fila-empty">' +
                '<i class="fas fa-check-circle"></i>' +
                '<p>Nenhuma conversa na fila de espera!</p>' +
                '<small>Quando um cliente enviar mensagem, a conversa aparecera aqui.</small>' +
                '</div></div>';
            return;
        }

        bulkBar.classList.add('visible');
        var html = '';
        for (var i = 0; i < conversas.length; i++) {
            var c = conversas[i];
            var isSelected = selectedIds[c.id] ? true : false;
            html += '<div class="card fila-card' + (isSelected ? ' selected' : '') + '" data-conv-id="' + c.id + '">';
            html += '<div class="card-body d-flex align-items-center justify-content-between">';
            html += '<input type="checkbox" class="fila-check conv-check" value="' + c.id + '"' + (isSelected ? ' checked' : '') + '>';
            if (c.profile_picture_url) {
                html += '<img src="' + escapeHtml(c.profile_picture_url) + '" class="fila-avatar" onerror="this.outerHTML=\'<div class=fila-avatar-placeholder><i class=fas\\ fa-user></i></div>\'">';
            } else {
                html += '<div class="fila-avatar-placeholder"><i class="fas fa-user"></i></div>';
            }
            html += '<div style="flex:1; min-width: 0;">';
            html += '<div class="fila-cliente">' + escapeHtml(c.cliente_nome);
            if (c.account_name) {
                html += ' <span class="badge badge-info" style="font-size:0.65rem;"><i class="fab fa-whatsapp"></i> ' + escapeHtml(c.account_name) + '</span>';
            }
            html += '</div>';
            html += '<div class="fila-numero">' + escapeHtml(c.cliente_numero || '') + '</div>';
            if (c.preview) {
                html += '<div class="fila-preview"><i class="fas fa-comment-dots text-muted"></i> ' + escapeHtml(c.preview) + '</div>';
            }
            html += '</div>';
            html += '<div class="text-right ml-3" style="white-space: nowrap;">';
            html += '<div class="fila-tempo mb-2"><i class="fas fa-clock"></i> ' + escapeHtml(c.tempo_fila || '') + ' na fila</div>';
            if (PODE_ATENDER) {
                html += '<a href="' + PEGAR_URL + '?id=' + c.id + '" class="btn btn-success btn-sm btn-pegar" data-id="' + c.id + '" data-nome="' + escapeHtml(c.cliente_nome) + '">';
                html += '<i class="fas fa-hand-pointer"></i> Pegar</a>';
            } else {
                html += '<button class="btn btn-secondary btn-sm" disabled title="Limite de conversas atingido"><i class="fas fa-ban"></i> Limite</button>';
            }
            html += '</div></div></div>';
        }
        container.innerHTML = html;

        // Bind checkboxes
        container.querySelectorAll('.conv-check').forEach(function(cb) {
            cb.addEventListener('change', function() {
                if (this.checked) selectedIds[this.value] = true;
                else delete selectedIds[this.value];
                updateBulkCount();
            });
        });

        // Bind botoes Pegar
        container.querySelectorAll('.btn-pegar').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var nome = this.getAttribute('data-nome');
                if (!confirm('Pegar conversa com ' + nome + '?')) return;
                // Submit via form POST
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = this.href;
                var csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = CSRF_PARAM;
                csrfInput.value = CSRF_TOKEN;
                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            });
        });

        updateBulkCount();
    }

    function updateBulkCount() {
        var checks = container.querySelectorAll('.conv-check');
        var checked = container.querySelectorAll('.conv-check:checked');
        var n = checked.length;
        selectedNum.textContent = n;
        selectedCount.textContent = n > 0 ? n + ' selecionada(s)' : '';
        btnFinalizar.disabled = n === 0;

        checks.forEach(function(cb) {
            var card = cb.closest('.fila-card');
            if (cb.checked) card.classList.add('selected');
            else card.classList.remove('selected');
        });

        selectAll.checked = n === checks.length && n > 0;
        selectAll.indeterminate = n > 0 && n < checks.length;
    }

    selectAll.addEventListener('change', function() {
        container.querySelectorAll('.conv-check').forEach(function(cb) {
            cb.checked = selectAll.checked;
            if (selectAll.checked) selectedIds[cb.value] = true;
            else delete selectedIds[cb.value];
        });
        updateBulkCount();
    });

    bulkForm.addEventListener('submit', function(e) {
        var checked = container.querySelectorAll('.conv-check:checked');
        if (checked.length === 0) { e.preventDefault(); return; }
        if (!confirm('Finalizar ' + checked.length + ' conversa(s)?')) { e.preventDefault(); return; }
        // Remover inputs antigos
        bulkForm.querySelectorAll('input[name="ids[]"]').forEach(function(el) { el.remove(); });
        checked.forEach(function(cb) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            bulkForm.appendChild(input);
        });
    });

    // Polling: buscar fila a cada 5 segundos
    function fetchFila() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', FILA_URL, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status === 200) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.success) {
                        countBadge.textContent = resp.count;
                        // Limpar IDs selecionados que nao existem mais
                        var currentIds = {};
                        for (var i = 0; i < resp.conversas.length; i++) {
                            currentIds[resp.conversas[i].id] = true;
                        }
                        for (var id in selectedIds) {
                            if (!currentIds[id]) delete selectedIds[id];
                        }
                        renderFila(resp.conversas);
                    }
                } catch(e) {}
            }
        };
        xhr.send();
    }

    // Primeira carga imediata + polling
    fetchFila();
    setInterval(fetchFila, 5000);
});
</script>
