<?php

/** @var string $assetDir */

use yii\helpers\Html;
use yii\helpers\Url;

?>
<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?= Url::home() ?>" class="nav-link">
                <i class="fas fa-home"></i> Inicio
            </a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <?php if (!Yii::$app->user->isGuest): ?>
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                    <span class="badge badge-warning navbar-badge" id="notification-count">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-header">Notificacoes</span>
                    <div class="dropdown-divider"></div>
                    <a href="<?= Url::to(['/conversa/fila']) ?>" class="dropdown-item">
                        <i class="fas fa-inbox mr-2"></i> Conversas na fila
                        <span class="float-right text-muted text-sm"></span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item dropdown-footer">Ver Todas as Notificacoes</a>
                </div>
            </li>

            <!-- Fullscreen Button -->
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>

            <!-- User Info and Logout -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-user-circle mr-1"></i>
                    <span class="d-none d-md-inline">
                        <?= Html::encode(Yii::$app->user->identity->username) ?>
                        <?php if (Yii::$app->user->identity->empresa): ?>
                            <small class="text-muted">(<?= Html::encode(Yii::$app->user->identity->empresa->nome ?? '') ?>)</small>
                        <?php endif; ?>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <span class="dropdown-header">
                        <strong><?= Html::encode(Yii::$app->user->identity->username) ?></strong>
                        <br>
                        <small class="text-muted">
                            <?php
                            $roleLabels = [
                                'admin' => 'Administrador',
                                'supervisor' => 'Supervisor',
                                'agent' => 'Atendente',
                            ];
                            echo $roleLabels[Yii::$app->user->identity->role] ?? Yii::$app->user->identity->role;
                            ?>
                        </small>
                    </span>
                    <div class="dropdown-divider"></div>
                    <a href="<?= Url::to(['/user/profile']) ?>" class="dropdown-item">
                        <i class="fas fa-user mr-2"></i> Meu Perfil
                    </a>
                    <div class="dropdown-divider"></div>
                    <?= Html::a(
                        '<i class="fas fa-sign-out-alt mr-2"></i> Sair',
                        ['/site/logout'],
                        ['data-method' => 'post', 'class' => 'dropdown-item']
                    ) ?>
                </div>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<!-- /.navbar -->
