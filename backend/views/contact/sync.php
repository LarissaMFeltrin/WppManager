<?php

/** @var yii\web\View $this */
/** @var array $stats */
/** @var common\models\WhatsappAccount[] $accounts */
/** @var array|null $result */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Sincronizar Contatos';
$this->params['breadcrumbs'][] = ['label' => 'Contatos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="contact-sync">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sync"></i> <?= Html::encode($this->title) ?></h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        A sincronizacao verifica os chats existentes e cria contatos que estejam faltando.
                        Tambem atualiza nomes de contatos que estejam em branco usando o nome do chat.
                    </p>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="info-box bg-info">
                                <span class="info-box-icon"><i class="fas fa-address-book"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total de Contatos</span>
                                    <span class="info-box-number"><?= (int)$stats['total'] ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-user-slash"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Sem Nome</span>
                                    <span class="info-box-number"><?= (int)$stats['semNome'] ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Chats sem Contato</span>
                                    <span class="info-box-number"><?= (int)$stats['chatsSemContato'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="post" action="<?= Url::to(['sync']) ?>">
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sync"></i> Executar Sincronizacao
                        </button>
                        <a href="<?= Url::to(['index']) ?>" class="btn btn-default btn-lg ml-2">
                            <i class="fas fa-list"></i> Ver Contatos
                        </a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fab fa-whatsapp"></i> Instancias</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Numero</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $acc): ?>
                            <tr>
                                <td><?= Html::encode($acc->session_name ?: '-') ?></td>
                                <td><?= Html::encode($acc->phone_number ?: '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($accounts)): ?>
                            <tr><td colspan="2" class="text-center text-muted">Nenhuma instancia</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> O que a sincronizacao faz</h3>
                </div>
                <div class="card-body">
                    <ul class="mb-0" style="padding-left: 18px;">
                        <li>Cria contatos para chats individuais que nao possuem um registro de contato</li>
                        <li>Atualiza o nome de contatos que estao em branco usando o nome salvo no chat</li>
                        <li>Sincroniza nomes de grupos WhatsApp via servico Node.js</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
