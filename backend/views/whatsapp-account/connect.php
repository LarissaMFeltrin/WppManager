<?php

/** @var yii\web\View $this */
/** @var common\models\WhatsappAccount $model */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Conectar WhatsApp - ' . ($model->session_name ?: $model->phone_number);
$this->params['breadcrumbs'][] = ['label' => 'Instancias WhatsApp', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->session_name ?: $model->phone_number, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Conectar';

$statusUrl = Url::to(['connection-status', 'id' => $model->id]);
?>

<div class="whatsapp-connect">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            <!-- Card de Status -->
            <div class="card" id="card-status">
                <div class="card-header">
                    <h3 class="card-title"><i class="fab fa-whatsapp"></i> Conectar WhatsApp</h3>
                    <div class="card-tools">
                        <?= Html::a('<i class="fas fa-arrow-left"></i> Voltar', ['view', 'id' => $model->id], ['class' => 'btn btn-default btn-sm']) ?>
                    </div>
                </div>
                <div class="card-body text-center">

                    <!-- Info da Instancia -->
                    <div class="mb-3">
                        <strong><?= Html::encode($model->session_name) ?></strong>
                        <?php if ($model->phone_number): ?>
                            <br><small class="text-muted"><?= Html::encode($model->phone_number) ?></small>
                        <?php endif; ?>
                    </div>

                    <!-- Status Indicator -->
                    <div id="status-indicator" class="mb-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Carregando...</span>
                        </div>
                        <p class="mt-2 text-muted">Verificando status...</p>
                    </div>

                    <!-- QR Code Area -->
                    <div id="qr-area" style="display:none;">
                        <div class="alert alert-info">
                            <i class="fas fa-mobile-alt"></i>
                            <strong>Escaneie o QR Code</strong><br>
                            WhatsApp > Configuracoes > Aparelhos Conectados > Conectar
                        </div>
                        <div class="mb-3">
                            <img id="qr-image" src="" alt="QR Code" style="max-width:300px; border:2px solid #ddd; border-radius:8px; padding:8px;">
                        </div>
                    </div>

                    <!-- Pairing Code Area -->
                    <div id="pairing-area" style="display:none;">
                        <div class="alert alert-warning">
                            <i class="fas fa-key"></i>
                            <strong>Codigo de Pareamento</strong><br>
                            No celular: WhatsApp > Config > Aparelhos Conectados > Conectar com numero
                        </div>
                        <div class="mb-3">
                            <span id="pairing-code" style="font-size:2.5rem; font-weight:bold; letter-spacing:8px; font-family:monospace; color:#128C7E; background:#f0f0f0; padding:12px 24px; border-radius:8px; display:inline-block;"></span>
                        </div>
                    </div>

                    <!-- Connected Area -->
                    <div id="connected-area" style="display:none;">
                        <div class="text-success mb-3">
                            <i class="fas fa-check-circle" style="font-size:4rem;"></i>
                        </div>
                        <h4 class="text-success">WhatsApp Conectado!</h4>
                        <p class="text-muted" id="connected-jid"></p>
                        <div class="mt-3">
                            <?= Html::a('<i class="fas fa-comments"></i> Ir para Conversas', ['/chat/painel'], ['class' => 'btn btn-success btn-lg']) ?>
                        </div>
                    </div>

                    <!-- Service Offline Area -->
                    <div id="offline-area" style="display:none;">
                        <div class="text-danger mb-3">
                            <i class="fas fa-exclamation-triangle" style="font-size:3rem;"></i>
                        </div>
                        <h5 class="text-danger">Servico WhatsApp Offline</h5>
                        <p class="text-muted">O servico Node.js nao esta rodando.</p>
                        <div class="alert alert-secondary text-left">
                            <strong>Para iniciar, execute no terminal:</strong>
                            <pre class="mt-2 mb-0">cd /opt/htdoc_geral/projwhats/wpp-manager/whatsapp-service
node src/server.js</pre>
                        </div>
                    </div>

                    <!-- Connecting Area -->
                    <div id="connecting-area" style="display:none;">
                        <div class="spinner-border text-warning" role="status" style="width:3rem; height:3rem;">
                            <span class="sr-only">Conectando...</span>
                        </div>
                        <p class="mt-2 text-warning">Conectando ao WhatsApp...</p>
                    </div>

                </div>
                <div class="card-footer text-center text-muted">
                    <small><i class="fas fa-sync-alt fa-spin" id="polling-icon"></i> Atualizando automaticamente a cada 3 segundos</small>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
$js = <<<JS
var pollInterval = null;
var lastStatus = '';

function hideAllAreas() {
    $('#status-indicator, #qr-area, #pairing-area, #connected-area, #offline-area, #connecting-area').hide();
}

function pollStatus() {
    $.ajax({
        url: '{$statusUrl}',
        dataType: 'json',
        timeout: 8000,
        success: function(data) {
            hideAllAreas();

            if (!data.success && data.status === 'service_offline') {
                $('#offline-area').show();
                return;
            }

            var status = data.status || 'disconnected';

            if (status === 'connected') {
                $('#connected-area').show();
                $('#connected-jid').text('JID: ' + (data.jid || ''));
                // Parar polling quando conectado
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                    $('#polling-icon').removeClass('fa-spin');
                }
            } else if (status === 'qr_pending' && data.qrImage) {
                $('#qr-area').show();
                $('#qr-image').attr('src', data.qrImage);
                // Se tambem tem pairing code, mostrar
                if (data.pairingCode) {
                    $('#pairing-area').show();
                    $('#pairing-code').text(data.pairingCode);
                }
            } else if (status === 'pairing_code' && data.pairingCode) {
                $('#pairing-area').show();
                $('#pairing-code').text(data.pairingCode);
                // Se tambem tem QR, mostrar
                if (data.qrImage) {
                    $('#qr-area').show();
                    $('#qr-image').attr('src', data.qrImage);
                }
            } else if (status === 'connecting') {
                $('#connecting-area').show();
            } else {
                // disconnected
                $('#connecting-area').show();
            }

            lastStatus = status;
        },
        error: function() {
            hideAllAreas();
            $('#offline-area').show();
        }
    });
}

// Iniciar polling
pollStatus();
pollInterval = setInterval(pollStatus, 3000);
JS;
$this->registerJs($js);
?>
