<?php

/** @var yii\web\View $this */
/** @var common\models\Conversa[] $conversas */
/** @var common\models\Message[] $previews */
/** @var common\models\Atendente|null $atendente */
/** @var int $filaCount */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Meu Console';
$this->params['breadcrumbs'][] = $this->title;
?>

<style>
.console-card {
    border-left: 4px solid #28a745;
    transition: box-shadow 0.2s;
    margin-bottom: 12px;
}
.console-card:hover {
    box-shadow: 0 2px 12px rgba(0,0,0,0.12);
}
.console-card .card-body {
    padding: 12px 16px;
}
.console-cliente {
    font-weight: 600;
    font-size: 1rem;
    color: #333;
}
.console-numero {
    color: #888;
    font-size: 0.85rem;
}
.console-preview {
    color: #666;
    font-size: 0.85rem;
    margin-top: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 400px;
}
.console-tempo {
    color: #888;
    font-size: 0.82rem;
}
.console-empty {
    text-align: center;
    padding: 60px 20px;
    color: #aaa;
}
.console-empty i {
    font-size: 3rem;
    margin-bottom: 12px;
}
.console-card.selected {
    border-left-color: #dc3545;
    background: #fff8f8;
}
.console-actions .btn {
    margin-left: 4px;
}
.fila-alert {
    border-left: 4px solid #ffc107;
}
.console-check {
    width: 18px;
    height: 18px;
    cursor: pointer;
    margin-right: 12px;
    flex-shrink: 0;
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
</style>

<div class="conversa-meu-console">
    <?php if (!$atendente): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            Nenhum perfil de atendente vinculado ao seu usuario. Contacte o administrador.
        </div>
    <?php else: ?>
        <!-- Header com info do atendente -->
        <div class="row mb-3">
            <div class="col-md-6">
                <h4>
                    <i class="fas fa-headset text-primary"></i>
                    <?= Html::encode($atendente->nome) ?>
                    <?php
                    $statusBadges = [
                        'online' => '<span class="badge badge-success">Online</span>',
                        'offline' => '<span class="badge badge-secondary">Offline</span>',
                        'ocupado' => '<span class="badge badge-warning">Ocupado</span>',
                    ];
                    echo $statusBadges[$atendente->status] ?? '<span class="badge badge-light">' . Html::encode($atendente->status) . '</span>';
                    ?>
                </h4>
                <span class="text-muted">
                    Conversas: <strong><?= (int)$atendente->conversas_ativas ?></strong> / <?= (int)$atendente->max_conversas ?>
                </span>
            </div>
            <div class="col-md-6 text-right">
                <?= Html::a(
                    '<i class="fas fa-inbox"></i> Fila de Espera <span class="badge badge-warning">' . $filaCount . '</span>',
                    ['fila'],
                    ['class' => 'btn btn-outline-warning']
                ) ?>
            </div>
        </div>

        <?php if ($filaCount > 0): ?>
            <div class="alert alert-warning fila-alert">
                <i class="fas fa-bell"></i>
                <strong><?= $filaCount ?></strong> conversa(s) aguardando na fila.
                <?= Html::a('Ir para a Fila', ['fila'], ['class' => 'alert-link']) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($conversas) && $atendente): ?>
        <!-- Barra de acoes em massa -->
        <div class="bulk-bar" id="bulk-bar">
            <div>
                <label style="cursor:pointer; margin:0;">
                    <input type="checkbox" id="select-all" class="console-check" style="vertical-align:middle;">
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
    <?php endif; ?>

    <?php if (empty($conversas) && $atendente): ?>
        <div class="card">
            <div class="card-body console-empty">
                <i class="fas fa-inbox text-muted"></i>
                <p>Nenhuma conversa ativa no momento.</p>
                <small>Pegue conversas da <?= Html::a('Fila de Espera', ['fila']) ?> para comecar a atender.</small>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach ($conversas as $conv): ?>
        <?php
        $preview = $previews[$conv->id] ?? null;
        $previewText = '';
        if ($preview) {
            if ($preview->message_text) {
                $previewText = mb_strlen($preview->message_text) > 80
                    ? mb_substr($preview->message_text, 0, 80) . '...'
                    : $preview->message_text;
            } else {
                $typeLabels = ['image' => 'Imagem', 'video' => 'Video', 'audio' => 'Audio', 'document' => 'Documento', 'sticker' => 'Sticker'];
                $previewText = $typeLabels[$preview->message_type] ?? $preview->message_type;
            }
        }

        // Tempo desde inicio
        $tempoAtendimento = '';
        $refTime = $conv->atendida_em ?: $conv->iniciada_em;
        if ($refTime) {
            $diff = time() - strtotime($refTime);
            if ($diff < 60) {
                $tempoAtendimento = 'Agora';
            } elseif ($diff < 3600) {
                $tempoAtendimento = floor($diff / 60) . ' min';
            } else {
                $tempoAtendimento = floor($diff / 3600) . 'h ' . floor(($diff % 3600) / 60) . 'min';
            }
        }

        // Link para abrir o chat
        $chatLink = null;
        if ($conv->chat_id && $conv->chat) {
            $chatLink = Yii::$app->urlManager->createUrl(['/chat/painel', 'chat_id' => $conv->chat->chat_id]);
        }
        ?>
        <div class="card console-card" data-conv-id="<?= $conv->id ?>">
            <div class="card-body d-flex align-items-center justify-content-between">
                <input type="checkbox" class="console-check conv-check" value="<?= $conv->id ?>">
                <div style="flex:1; min-width: 0;">
                    <div class="console-cliente">
                        <i class="fas fa-user text-muted"></i>
                        <?= Html::encode($conv->cliente_nome ?: 'Cliente') ?>
                        <span class="badge badge-success" style="font-size:0.7rem;">Em atendimento</span>
                    </div>
                    <div class="console-numero"><?= Html::encode($conv->cliente_numero) ?></div>
                    <?php if ($previewText): ?>
                        <div class="console-preview">
                            <i class="fas fa-comment-dots text-muted"></i>
                            <?= Html::encode($previewText) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="text-right ml-3 console-actions" style="white-space: nowrap;">
                    <div class="console-tempo mb-2">
                        <i class="fas fa-clock"></i> <?= $tempoAtendimento ?>
                    </div>

                    <?php if ($chatLink): ?>
                        <?= Html::a(
                            '<i class="fas fa-comments"></i> Abrir Chat',
                            $chatLink,
                            ['class' => 'btn btn-primary btn-sm']
                        ) ?>
                    <?php endif; ?>

                    <?= Html::a(
                        '<i class="fas fa-undo"></i> Devolver',
                        ['devolver', 'id' => $conv->id],
                        [
                            'class' => 'btn btn-warning btn-sm',
                            'data-method' => 'post',
                            'data-confirm' => 'Devolver conversa para a fila?',
                        ]
                    ) ?>

                    <?= Html::a(
                        '<i class="fas fa-check"></i> Finalizar',
                        ['finalizar', 'id' => $conv->id],
                        [
                            'class' => 'btn btn-danger btn-sm',
                            'data-method' => 'post',
                            'data-confirm' => 'Finalizar esta conversa?',
                        ]
                    ) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (!empty($conversas) && $atendente): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var bulkBar = document.getElementById('bulk-bar');
    var selectAll = document.getElementById('select-all');
    var btnFinalizar = document.getElementById('btn-finalizar-massa');
    var selectedNum = document.getElementById('selected-num');
    var selectedCount = document.getElementById('selected-count');
    var bulkForm = document.getElementById('bulk-form');
    var checks = document.querySelectorAll('.conv-check');

    bulkBar.classList.add('visible');

    function updateCount() {
        var checked = document.querySelectorAll('.conv-check:checked');
        var n = checked.length;
        selectedNum.textContent = n;
        selectedCount.textContent = n > 0 ? n + ' selecionada(s)' : '';
        btnFinalizar.disabled = n === 0;

        checks.forEach(function(cb) {
            var card = cb.closest('.console-card');
            if (cb.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });

        selectAll.checked = n === checks.length && n > 0;
        selectAll.indeterminate = n > 0 && n < checks.length;
    }

    checks.forEach(function(cb) {
        cb.addEventListener('change', updateCount);
    });

    selectAll.addEventListener('change', function() {
        checks.forEach(function(cb) {
            cb.checked = selectAll.checked;
        });
        updateCount();
    });

    bulkForm.addEventListener('submit', function(e) {
        var checked = document.querySelectorAll('.conv-check:checked');
        if (checked.length === 0) {
            e.preventDefault();
            return;
        }

        if (!confirm('Finalizar ' + checked.length + ' conversa(s)?')) {
            e.preventDefault();
            return;
        }

        checked.forEach(function(cb) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            bulkForm.appendChild(input);
        });
    });
});
</script>
<?php endif; ?>
