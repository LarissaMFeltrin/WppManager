<?php

namespace backend\controllers;

use common\models\Contact;
use common\models\LogSistema;
use common\models\WhatsappAccount;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

/**
 * ContactController - Listagem de contatos WhatsApp
 */
class ContactController extends BaseController
{
    /**
     * Lista contatos com busca e filtro por account_id.
     * @return string
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $search = $request->get('search', '');
        $accountId = $request->get('account_id', '');

        $query = Contact::find()
            ->alias('ct')
            ->innerJoin('whatsapp_accounts wa', 'wa.id = ct.account_id')
            ->with('whatsappAccount')
            ->orderBy(['ct.name' => SORT_ASC]);

        // Filtro por empresa
        if (!$this->isAdmin()) {
            $query->andWhere(['wa.empresa_id' => $this->getEmpresaId()]);
        }

        // Filtro por account_id
        if (!empty($accountId)) {
            $query->andWhere(['ct.account_id' => $accountId]);
        }

        // Busca por nome ou numero
        if (!empty($search)) {
            $query->andWhere(['or',
                ['like', 'ct.name', $search],
                ['like', 'ct.phone_number', $search],
                ['like', 'ct.jid', $search],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 30,
            ],
        ]);

        // Lista de accounts para filtro
        $accountsQuery = WhatsappAccount::find();
        if (!$this->isAdmin()) {
            $accountsQuery->andWhere(['empresa_id' => $this->getEmpresaId()]);
        }
        $accounts = ArrayHelper::map($accountsQuery->all(), 'id', function ($model) {
            return $model->session_name ?: $model->phone_number;
        });

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'search' => $search,
            'accountId' => $accountId,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Sincronizar contatos: cria contatos faltantes a partir dos chats
     * e atualiza nomes vazios usando chat_name.
     * @return string
     */
    public function actionSync()
    {
        $result = null;

        if (Yii::$app->request->isPost) {
            $db = Yii::$app->db;
            $empresaId = $this->getEmpresaId();
            $isAdmin = $this->isAdmin();

            // 1. Criar contatos faltantes (chats individuais sem contato)
            $sqlInsert = "
                INSERT IGNORE INTO contacts (account_id, jid, name, phone_number, created_at, updated_at)
                SELECT c.account_id, c.chat_id, c.chat_name,
                       REPLACE(SUBSTRING_INDEX(c.chat_id, '@', 1), '+', ''),
                       NOW(), NOW()
                FROM chats c
                INNER JOIN whatsapp_accounts wa ON wa.id = c.account_id
                LEFT JOIN contacts ct ON ct.account_id = c.account_id AND ct.jid = c.chat_id
                WHERE c.chat_type = 'individual'
                  AND ct.id IS NULL
            ";
            if (!$isAdmin) {
                $sqlInsert .= " AND wa.empresa_id = " . (int)$empresaId;
            }
            $created = $db->createCommand($sqlInsert)->execute();

            // 2. Atualizar contatos sem nome usando chat_name
            $sqlUpdate = "
                UPDATE contacts ct
                INNER JOIN chats c ON c.account_id = ct.account_id AND c.chat_id = ct.jid
                INNER JOIN whatsapp_accounts wa ON wa.id = ct.account_id
                SET ct.name = c.chat_name, ct.updated_at = NOW()
                WHERE (ct.name IS NULL OR ct.name = '')
                  AND c.chat_name IS NOT NULL AND c.chat_name != ''
            ";
            if (!$isAdmin) {
                $sqlUpdate .= " AND wa.empresa_id = " . (int)$empresaId;
            }
            $updated = $db->createCommand($sqlUpdate)->execute();

            // 3. Tentar sincronizar grupos via Node.js
            $groupsSynced = false;
            $accounts = WhatsappAccount::find();
            if (!$isAdmin) {
                $accounts->andWhere(['empresa_id' => $empresaId]);
            }
            foreach ($accounts->all() as $account) {
                $port = $account->service_port ?: 3000;
                $url = "http://localhost:{$port}/api/sync-groups";
                $ctx = stream_context_create(['http' => ['method' => 'POST', 'timeout' => 10, 'header' => 'Content-Type: application/json']]);
                $resp = @file_get_contents($url, false, $ctx);
                if ($resp) $groupsSynced = true;
            }

            $result = [
                'created' => $created,
                'updated' => $updated,
                'groupsSynced' => $groupsSynced,
            ];

            LogSistema::gravar(LogSistema::TIPO_INFO, LogSistema::NIVEL_INFO,
                "Sincronizacao de contatos executada: {$created} criados, {$updated} atualizados",
                $result
            );

            Yii::$app->session->setFlash('success',
                "Sincronizacao concluida: {$created} contato(s) criado(s), {$updated} nome(s) atualizado(s)"
                . ($groupsSynced ? ', grupos sincronizados.' : '.')
            );
        }

        // Estatisticas
        $empresaId = $this->getEmpresaId();
        $isAdmin = $this->isAdmin();

        $contactQuery = Contact::find()
            ->innerJoin('whatsapp_accounts wa', 'wa.id = contacts.account_id');
        if (!$isAdmin) {
            $contactQuery->andWhere(['wa.empresa_id' => $empresaId]);
        }

        $stats = [
            'total' => (clone $contactQuery)->count(),
            'semNome' => (clone $contactQuery)->andWhere(['or', ['contacts.name' => null], ['contacts.name' => '']])->count(),
            'chatsSemContato' => Yii::$app->db->createCommand("
                SELECT COUNT(*)
                FROM chats c
                INNER JOIN whatsapp_accounts wa ON wa.id = c.account_id
                LEFT JOIN contacts ct ON ct.account_id = c.account_id AND ct.jid = c.chat_id
                WHERE c.chat_type = 'individual' AND ct.id IS NULL"
                . (!$isAdmin ? " AND wa.empresa_id = " . (int)$empresaId : "")
            )->queryScalar(),
        ];

        // Accounts para exibir
        $accountsQuery = WhatsappAccount::find();
        if (!$isAdmin) {
            $accountsQuery->andWhere(['empresa_id' => $empresaId]);
        }
        $accounts = $accountsQuery->all();

        return $this->render('sync', [
            'stats' => $stats,
            'accounts' => $accounts,
            'result' => $result,
        ]);
    }
}
