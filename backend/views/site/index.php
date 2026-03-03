<?php

/** @var yii\web\View $this */
/** @var int $totalChats */
/** @var int $mensagensHoje */
/** @var int $instanciasOnline */
/** @var int $conversasAtivas */
/** @var common\models\Message[] $ultimasMensagens */
/** @var common\models\WhatsappAccount[] $instancias */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Dashboard';
?>
<div class="site-index">

    <!-- Info Boxes -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-comments"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total de Chats</span>
                    <span class="info-box-number"><?= number_format($totalChats) ?></span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-envelope"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Mensagens Hoje</span>
                    <span class="info-box-number"><?= number_format($mensagensHoje) ?></span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-mobile-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Instancias Online</span>
                    <span class="info-box-number"><?= number_format($instanciasOnline) ?></span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-headset"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Conversas Ativas</span>
                    <span class="info-box-number"><?= number_format($conversasAtivas) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Ultimas Mensagens -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title"><i class="fas fa-envelope mr-1"></i> Ultimas Mensagens</h3>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-valign-middle">
                        <thead>
                            <tr>
                                <th>Chat</th>
                                <th>Mensagem</th>
                                <th>Tipo</th>
                                <th>Direcao</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ultimasMensagens)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Nenhuma mensagem encontrada.</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($ultimasMensagens as $msg): ?>
                            <tr>
                                <td>
                                    <?= Html::encode($msg->chat ? $msg->chat->chat_name : 'N/A') ?>
                                </td>
                                <td>
                                    <?= Html::encode(mb_substr($msg->message_text ?: '[midia]', 0, 50)) ?>
                                    <?= mb_strlen($msg->message_text ?? '') > 50 ? '...' : '' ?>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?= Html::encode($msg->message_type) ?></span>
                                </td>
                                <td>
                                    <?php if ($msg->is_from_me): ?>
                                        <span class="badge badge-success"><i class="fas fa-arrow-up"></i> Enviada</span>
                                    <?php else: ?>
                                        <span class="badge badge-info"><i class="fas fa-arrow-down"></i> Recebida</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= $msg->created_at ? Yii::$app->formatter->asDatetime($msg->created_at, 'short') : ($msg->timestamp ? date('d/m/Y H:i', $msg->timestamp) : '-') ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Instancias WhatsApp -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title"><i class="fab fa-whatsapp mr-1"></i> Instancias WhatsApp</h3>
                    <div class="card-tools">
                        <a href="<?= Url::to(['/whatsapp-account/index']) ?>" class="btn btn-tool btn-sm">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (empty($instancias)): ?>
                        <li class="list-group-item text-center text-muted">
                            Nenhuma instancia cadastrada.
                        </li>
                        <?php else: ?>
                        <?php foreach ($instancias as $inst): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= Html::encode($inst->session_name ?: $inst->phone_number) ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?= Html::encode($inst->phone_number) ?>
                                    <?php if ($inst->empresa): ?>
                                        - <?= Html::encode($inst->empresa->nome) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <div>
                                <?php if ($inst->is_connected): ?>
                                    <span class="badge badge-success badge-pill"><i class="fas fa-circle"></i> Online</span>
                                <?php else: ?>
                                    <span class="badge badge-danger badge-pill"><i class="fas fa-circle"></i> Offline</span>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
