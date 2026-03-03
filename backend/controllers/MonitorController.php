<?php

namespace backend\controllers;

use common\models\Atendente;
use common\models\Chat;
use common\models\Conversa;
use common\models\Message;
use common\models\WhatsappAccount;
use Yii;

class MonitorController extends BaseController
{
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $isAdmin = $this->isAdmin();
        $empresaId = $this->getEmpresaId();

        // === Cards de metricas ===

        // Instancias online
        $instOnlineQuery = WhatsappAccount::find()->andWhere(['is_connected' => 1]);
        $instTotalQuery = WhatsappAccount::find();
        if (!$isAdmin) {
            $instOnlineQuery->andWhere(['empresa_id' => $empresaId]);
            $instTotalQuery->andWhere(['empresa_id' => $empresaId]);
        }
        $instanciasOnline = $instOnlineQuery->count();
        $instanciasTotal = $instTotalQuery->count();

        // Conversas na fila
        $filaCount = Conversa::find()
            ->andWhere(['status' => Conversa::STATUS_AGUARDANDO])
            ->count();

        // Em atendimento
        $emAtendimentoCount = Conversa::find()
            ->andWhere(['status' => Conversa::STATUS_EM_ATENDIMENTO])
            ->count();

        // Mensagens hoje
        $hoje = date('Y-m-d');
        $msgHojeQuery = Message::find()
            ->andWhere(['>=', 'messages.created_at', $hoje . ' 00:00:00']);
        if (!$isAdmin) {
            $msgHojeQuery->innerJoin('chats c', 'c.id = messages.chat_id')
                ->innerJoin('whatsapp_accounts wa', 'wa.id = c.account_id')
                ->andWhere(['wa.empresa_id' => $empresaId]);
        }
        $mensagensHoje = $msgHojeQuery->count();

        // === Tabelas ===

        // Instancias WhatsApp
        $instanciasQuery = WhatsappAccount::find()->with('empresa');
        if (!$isAdmin) {
            $instanciasQuery->andWhere(['empresa_id' => $empresaId]);
        }
        $instancias = $instanciasQuery->orderBy(['is_connected' => SORT_DESC, 'last_connection' => SORT_DESC])->all();

        // Atendentes
        $atendentesQuery = Atendente::find()->with('empresa');
        if (!$isAdmin) {
            $atendentesQuery->andWhere(['empresa_id' => $empresaId]);
        }
        $atendentes = $atendentesQuery->orderBy(['status' => SORT_ASC, 'nome' => SORT_ASC])->all();

        // Conversas ativas (nao finalizadas)
        $conversasQuery = Conversa::find()
            ->with('atendente')
            ->andWhere(['!=', 'status', Conversa::STATUS_FINALIZADA])
            ->orderBy(['ultima_msg_em' => SORT_DESC])
            ->limit(20);
        $conversas = $conversasQuery->all();

        // Ultimas 15 mensagens
        $ultimasMsgsQuery = Message::find()
            ->with(['chat', 'chat.whatsappAccount'])
            ->orderBy(['messages.id' => SORT_DESC])
            ->limit(15);
        if (!$isAdmin) {
            $ultimasMsgsQuery->innerJoin('chats c2', 'c2.id = messages.chat_id')
                ->innerJoin('whatsapp_accounts wa2', 'wa2.id = c2.account_id')
                ->andWhere(['wa2.empresa_id' => $empresaId]);
        }
        $ultimasMensagens = $ultimasMsgsQuery->all();

        return $this->render('index', [
            'instanciasOnline' => $instanciasOnline,
            'instanciasTotal' => $instanciasTotal,
            'filaCount' => $filaCount,
            'emAtendimentoCount' => $emAtendimentoCount,
            'mensagensHoje' => $mensagensHoje,
            'instancias' => $instancias,
            'atendentes' => $atendentes,
            'conversas' => $conversas,
            'ultimasMensagens' => $ultimasMensagens,
        ]);
    }

    /**
     * Historico de conversas por atendente.
     * GET /monitor/conversas?atendente_id=X&status=Y&periodo=Z
     */
    public function actionConversas($atendente_id = null, $periodo = 'tudo', $busca = null)
    {
        $db = Yii::$app->db;

        // === Cards de resumo geral ===
        $totalConversas = (int) Conversa::find()->count();
        $totalFinalizadas = (int) Conversa::find()->andWhere(['status' => Conversa::STATUS_FINALIZADA])->count();
        $totalEmAtendimento = (int) Conversa::find()->andWhere(['status' => Conversa::STATUS_EM_ATENDIMENTO])->count();
        $totalNaFila = (int) Conversa::find()->andWhere(['status' => Conversa::STATUS_AGUARDANDO])->count();

        // === Estatisticas por atendente ===
        $statsAtendentes = $db->createCommand("
            SELECT
                a.id,
                a.nome,
                a.status as atendente_status,
                COALESCE(SUM(c.status = 'em_atendimento'), 0) as em_atendimento,
                COALESCE(SUM(c.status = 'finalizada'), 0) as finalizadas,
                ROUND(AVG(
                    CASE WHEN c.status = 'finalizada' AND c.atendida_em IS NOT NULL AND c.finalizada_em IS NOT NULL
                    THEN TIMESTAMPDIFF(MINUTE, c.atendida_em, c.finalizada_em) END
                ), 0) as tempo_medio_min
            FROM atendentes a
            LEFT JOIN conversas c ON c.atendente_id = a.id
            GROUP BY a.id, a.nome, a.status
            ORDER BY a.nome
        ")->queryAll();

        // Devolvidas por atendente (campo devolvida_por)
        $devolvidasPorAtendente = $db->createCommand("
            SELECT devolvida_por, COUNT(*) as total
            FROM conversas
            WHERE devolvida_por IS NOT NULL
            GROUP BY devolvida_por
        ")->queryAll();
        $devolvidasMap = [];
        foreach ($devolvidasPorAtendente as $row) {
            $devolvidasMap[(int) $row['devolvida_por']] = (int) $row['total'];
        }

        // Adicionar devolvidas ao stats
        foreach ($statsAtendentes as &$stat) {
            $stat['devolvidas'] = $devolvidasMap[(int) $stat['id']] ?? 0;
        }
        unset($stat);

        // === Lista de atendentes para dropdown ===
        $atendentes = Atendente::find()->orderBy(['nome' => SORT_ASC])->all();

        // === Historico de conversas (filtrado) ===
        $conversasQuery = Conversa::find()
            ->with(['atendente', 'chat'])
            ->orderBy(['conversas.id' => SORT_DESC]);

        if ($atendente_id) {
            $conversasQuery->andWhere(['OR',
                ['conversas.atendente_id' => (int) $atendente_id],
                ['conversas.devolvida_por' => (int) $atendente_id],
            ]);
        }

        $status = Yii::$app->request->get('status', []);
        if (!is_array($status)) {
            $status = $status ? [$status] : [];
        }
        $status = array_filter($status);
        if (!empty($status)) {
            $conversasQuery->andWhere(['conversas.status' => $status]);
        }

        // Filtro de busca por nome/numero do cliente
        if ($busca) {
            $busca = trim($busca);
            $conversasQuery->andWhere(['OR',
                ['like', 'conversas.cliente_nome', $busca],
                ['like', 'conversas.cliente_numero', $busca],
            ]);
        }

        // Filtro de periodo
        if ($periodo === 'hoje') {
            $conversasQuery->andWhere(['>=', 'conversas.iniciada_em', date('Y-m-d') . ' 00:00:00']);
        } elseif ($periodo === '7dias') {
            $conversasQuery->andWhere(['>=', 'conversas.iniciada_em', date('Y-m-d', strtotime('-7 days')) . ' 00:00:00']);
        } elseif ($periodo === '30dias') {
            $conversasQuery->andWhere(['>=', 'conversas.iniciada_em', date('Y-m-d', strtotime('-30 days')) . ' 00:00:00']);
        }

        $conversas = $conversasQuery->limit(100)->all();

        return $this->render('conversas', [
            'totalConversas' => $totalConversas,
            'totalFinalizadas' => $totalFinalizadas,
            'totalEmAtendimento' => $totalEmAtendimento,
            'totalNaFila' => $totalNaFila,
            'statsAtendentes' => $statsAtendentes,
            'atendentes' => $atendentes,
            'conversas' => $conversas,
            'filtroAtendenteId' => $atendente_id,
            'filtroStatus' => $status ?: [],
            'filtroPeriodo' => $periodo,
            'filtroBusca' => $busca,
        ]);
    }
}
