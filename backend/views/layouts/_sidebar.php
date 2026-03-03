<?php

/** @var string $assetDir */

use yii\helpers\Html;
use yii\helpers\Url;

$user = Yii::$app->user->identity;
$isAdmin = $user && $user->isAdmin();
$isSupervisor = $user && $user->isSupervisor();
$isAdminOrSupervisor = $isAdmin || $isSupervisor;

// Detectar controller/action atual para marcar menu ativo
$controllerId = Yii::$app->controller ? Yii::$app->controller->id : '';
$actionId = Yii::$app->controller ? Yii::$app->controller->action->id : '';
$route = $controllerId . '/' . $actionId;
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="<?= Url::home() ?>" class="brand-link">
        <span class="brand-image" style="margin-left: 12px; font-size: 1.5rem; color: #25d366;">
            <i class="fab fa-whatsapp"></i>
        </span>
        <span class="brand-text font-weight-light"><b>WPP</b> Manager</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel -->
        <?php if (!Yii::$app->user->isGuest): ?>
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <span class="img-circle elevation-2 d-inline-block text-center bg-info" style="width:34px;height:34px;line-height:34px;font-size:16px;color:#fff;">
                    <?= strtoupper(substr($user->username, 0, 1)) ?>
                </span>
            </div>
            <div class="info">
                <a href="#" class="d-block"><?= Html::encode($user->username) ?></a>
                <small class="text-muted">
                    <?php
                    $roleLabels = [
                        'admin' => 'Administrador',
                        'supervisor' => 'Supervisor',
                        'agent' => 'Atendente',
                    ];
                    echo $roleLabels[$user->role] ?? $user->role;
                    ?>
                </small>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">

                <!-- === MONITORAMENTO === -->
                <li class="nav-header">MONITORAMENTO</li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/site/index']) ?>" class="nav-link <?= $controllerId === 'site' && $actionId === 'index' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a href="<?= Url::to(['/empresa/index']) ?>" class="nav-link <?= $controllerId === 'empresa' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-building"></i>
                        <p>Empresas</p>
                    </a>
                </li>
                <?php endif; ?>

                <!-- === WHATSAPP === -->
                <li class="nav-header">WHATSAPP</li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/whatsapp-account/index']) ?>" class="nav-link <?= $controllerId === 'whatsapp-account' && $actionId === 'index' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-mobile-alt"></i>
                        <p>Instancias</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/whatsapp-account/create']) ?>" class="nav-link <?= $controllerId === 'whatsapp-account' && $actionId === 'create' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        <p>Nova Instancia</p>
                    </a>
                </li>

                <!-- === ATENDIMENTO === -->
                <li class="nav-header">ATENDIMENTO</li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/chat/painel']) ?>" class="nav-link <?= $controllerId === 'chat' && $actionId === 'painel' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-comments"></i>
                        <p>Painel de Conversas</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/conversa/fila']) ?>" class="nav-link <?= $controllerId === 'conversa' && $actionId === 'fila' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-inbox"></i>
                        <p>Fila de Espera</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/conversa/meu-console']) ?>" class="nav-link <?= $controllerId === 'conversa' && $actionId === 'meu-console' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-headset"></i>
                        <p>Meu Console</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/contact/index']) ?>" class="nav-link <?= $controllerId === 'contact' && $actionId === 'index' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-address-book"></i>
                        <p>Contatos</p>
                    </a>
                </li>

                <!-- === MONITORAMENTO === -->
                <li class="nav-header">MONITORAMENTO</li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/monitor/index']) ?>" class="nav-link <?= $controllerId === 'monitor' && $actionId === 'index' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>Monitor</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/monitor/conversas']) ?>" class="nav-link <?= $controllerId === 'monitor' && $actionId === 'conversas' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-history"></i>
                        <p>Historico Conversas</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/log-sistema/index']) ?>" class="nav-link <?= $controllerId === 'log-sistema' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>Logs de Webhook</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/contact/sync']) ?>" class="nav-link <?= $controllerId === 'contact' && $actionId === 'sync' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-sync"></i>
                        <p>Sincronizar Contatos</p>
                    </a>
                </li>

                <!-- === CONFIGURACOES === -->
                <?php if ($isAdminOrSupervisor): ?>
                <li class="nav-header">CONFIGURACOES</li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/user/index']) ?>" class="nav-link <?= $controllerId === 'user' && $actionId === 'index' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Usuarios</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="<?= Url::to(['/atendente/index']) ?>" class="nav-link <?= $controllerId === 'atendente' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user-tie"></i>
                        <p>Atendentes</p>
                    </a>
                </li>
                <?php endif; ?>

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
