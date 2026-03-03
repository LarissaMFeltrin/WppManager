<?php

namespace backend\controllers;

use common\models\Chat;
use common\models\Contact;
use common\models\Conversa;
use common\models\Atendente;
use common\models\LogSistema;
use common\models\Message;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * ConversaController - Fila de espera e console do atendente
 */
class ConversaController extends BaseController
{
    /**
     * Busca o atendente vinculado ao usuario logado.
     */
    private function getAtendente()
    {
        $user = Yii::$app->user->identity;
        // Prioridade: user_id, fallback: email
        return Atendente::findOne(['user_id' => $user->id])
            ?: Atendente::findOne(['email' => $user->email]);
    }

    /**
     * Fila de conversas aguardando atendimento.
     */
    public function actionFila()
    {
        $atendente = $this->getAtendente();

        // Buscar conversas aguardando com preview da ultima mensagem
        $query = Conversa::find()
            ->with(['atendente', 'chat', 'whatsappAccount'])
            ->andWhere(['conversas.status' => Conversa::STATUS_AGUARDANDO])
            ->orderBy(['conversas.iniciada_em' => SORT_ASC]);

        // Filtrar por instancias vinculadas ao atendente
        if ($atendente) {
            $accountIds = $atendente->getAccountIds();
            if (!empty($accountIds)) {
                $query->andWhere(['conversas.account_id' => $accountIds]);
            } else {
                $query->andWhere('1=0');
            }
        }

        $conversas = $query->all();

        // Buscar preview da ultima mensagem de cada conversa via chat_id
        $previews = [];
        foreach ($conversas as $conv) {
            if ($conv->chat_id) {
                $lastMsg = Message::find()
                    ->where(['chat_id' => $conv->chat_id])
                    ->orderBy(['timestamp' => SORT_DESC, 'id' => SORT_DESC])
                    ->one();
                if ($lastMsg) {
                    $previews[$conv->id] = $lastMsg;
                }
            }
        }

        $filaCount = count($conversas);

        return $this->render('fila', [
            'conversas' => $conversas,
            'previews' => $previews,
            'atendente' => $atendente,
            'filaCount' => $filaCount,
        ]);
    }

    /**
     * Console do atendente logado - lista suas conversas ativas.
     */
    public function actionMeuConsole()
    {
        $atendente = $this->getAtendente();

        $conversas = [];
        $previews = [];

        if ($atendente) {
            $conversas = Conversa::find()
                ->with(['atendente', 'chat'])
                ->andWhere(['atendente_id' => $atendente->id])
                ->andWhere(['!=', 'status', Conversa::STATUS_FINALIZADA])
                ->orderBy(['ultima_msg_em' => SORT_DESC])
                ->all();

            foreach ($conversas as $conv) {
                if ($conv->chat_id) {
                    $lastMsg = Message::find()
                        ->where(['chat_id' => $conv->chat_id])
                        ->orderBy(['id' => SORT_DESC])
                        ->one();
                    if ($lastMsg) {
                        $previews[$conv->id] = $lastMsg;
                    }
                }
            }
        }

        // Contar fila para badge (filtrado pelas instancias do atendente)
        $filaQuery = Conversa::find()
            ->andWhere(['status' => Conversa::STATUS_AGUARDANDO]);
        if ($atendente) {
            $accountIds = $atendente->getAccountIds();
            if (!empty($accountIds)) {
                $filaQuery->andWhere(['account_id' => $accountIds]);
            } else {
                $filaQuery->andWhere('1=0');
            }
        }
        $filaCount = $filaQuery->count();

        return $this->render('meu-console', [
            'conversas' => $conversas,
            'previews' => $previews,
            'atendente' => $atendente,
            'filaCount' => $filaCount,
        ]);
    }

    /**
     * Pegar conversa da fila - atribui ao atendente logado.
     * POST /conversa/pegar?id=X
     */
    public function actionPegar($id)
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Metodo nao permitido.');
        }

        $atendente = $this->getAtendente();
        if (!$atendente) {
            throw new ForbiddenHttpException('Voce nao esta vinculado como atendente.');
        }

        // Verificar capacidade
        if ($atendente->conversas_ativas >= $atendente->max_conversas) {
            Yii::$app->session->setFlash('error', 'Voce ja atingiu o limite de conversas simultaneas (' . $atendente->max_conversas . ').');
            return $this->redirect(['fila']);
        }

        $conversa = Conversa::findOne($id);
        if (!$conversa) {
            throw new NotFoundHttpException('Conversa nao encontrada.');
        }

        if ($conversa->status !== Conversa::STATUS_AGUARDANDO) {
            Yii::$app->session->setFlash('warning', 'Esta conversa ja foi pega por outro atendente.');
            return $this->redirect(['fila']);
        }

        // Atribuir conversa ao atendente
        $conversa->atendente_id = $atendente->id;
        $conversa->status = Conversa::STATUS_EM_ATENDIMENTO;
        $conversa->atendida_em = date('Y-m-d H:i:s');
        $conversa->save(false);

        // Incrementar contador
        $atendente->conversas_ativas = (int)$atendente->conversas_ativas + 1;
        $atendente->ultimo_acesso = date('Y-m-d H:i:s');
        $atendente->save(false);

        LogSistema::gravar(LogSistema::TIPO_ATENDIMENTO, LogSistema::NIVEL_INFO,
            "Atendente {$atendente->nome} pegou conversa com " . ($conversa->cliente_nome ?: $conversa->cliente_numero),
            ['conversa_id' => $conversa->id, 'atendente_id' => $atendente->id]
        );

        Yii::$app->session->setFlash('success', 'Conversa com ' . ($conversa->cliente_nome ?: $conversa->cliente_numero) . ' atribuida a voce.');

        // Redirecionar para o Painel de Conversas com o chat aberto
        if ($conversa->chat_id) {
            $chat = Chat::findOne($conversa->chat_id);
            if ($chat) {
                return $this->redirect(['/chat/painel', 'chat_id' => $chat->chat_id]);
            }
        }

        return $this->redirect(['meu-console']);
    }

    /**
     * Finalizar conversa - marca como encerrada.
     * POST /conversa/finalizar?id=X
     */
    public function actionFinalizar($id)
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Metodo nao permitido.');
        }

        $conversa = Conversa::findOne($id);
        if (!$conversa) {
            throw new NotFoundHttpException('Conversa nao encontrada.');
        }

        $atendente = $this->getAtendente();

        // Apenas o atendente atribuido ou admin pode finalizar
        if (!$this->isAdmin() && (!$atendente || $conversa->atendente_id !== $atendente->id)) {
            throw new ForbiddenHttpException('Voce nao pode finalizar esta conversa.');
        }

        // Decrementar contador do atendente que estava atendendo
        if ($conversa->atendente_id) {
            $atendenteConv = Atendente::findOne($conversa->atendente_id);
            if ($atendenteConv && $atendenteConv->conversas_ativas > 0) {
                $atendenteConv->conversas_ativas = (int)$atendenteConv->conversas_ativas - 1;
                $atendenteConv->save(false);
            }
        }

        $conversa->status = Conversa::STATUS_FINALIZADA;
        $conversa->finalizada_em = date('Y-m-d H:i:s');
        $conversa->save(false);

        $userName = $atendente ? $atendente->nome : (Yii::$app->user->identity->username ?? 'admin');
        LogSistema::gravar(LogSistema::TIPO_ATENDIMENTO, LogSistema::NIVEL_INFO,
            "{$userName} finalizou conversa com " . ($conversa->cliente_nome ?: $conversa->cliente_numero),
            ['conversa_id' => $conversa->id]
        );

        Yii::$app->session->setFlash('success', 'Conversa finalizada.');

        $redirect = Yii::$app->request->get('redirect', 'meu-console');
        return $this->redirect([$redirect]);
    }

    /**
     * Finalizar multiplas conversas de uma vez.
     * POST /conversa/finalizar-massa
     */
    public function actionFinalizarMassa()
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Metodo nao permitido.');
        }

        $ids = Yii::$app->request->post('ids', []);
        if (empty($ids)) {
            Yii::$app->session->setFlash('warning', 'Nenhuma conversa selecionada.');
            return $this->redirect(Yii::$app->request->referrer ?: ['fila']);
        }

        $atendente = $this->getAtendente();
        $count = 0;

        foreach ($ids as $id) {
            $conversa = Conversa::findOne($id);
            if (!$conversa || $conversa->status === Conversa::STATUS_FINALIZADA) {
                continue;
            }

            // Apenas o atendente atribuido ou admin pode finalizar
            if (!$this->isAdmin() && (!$atendente || ($conversa->atendente_id && $conversa->atendente_id !== $atendente->id))) {
                continue;
            }

            // Decrementar contador do atendente que estava atendendo
            if ($conversa->atendente_id) {
                $atendenteConv = Atendente::findOne($conversa->atendente_id);
                if ($atendenteConv && $atendenteConv->conversas_ativas > 0) {
                    $atendenteConv->conversas_ativas = (int)$atendenteConv->conversas_ativas - 1;
                    $atendenteConv->save(false);
                }
            }

            $conversa->status = Conversa::STATUS_FINALIZADA;
            $conversa->finalizada_em = date('Y-m-d H:i:s');
            $conversa->save(false);
            $count++;
        }

        if ($count > 0) {
            $userName = $atendente ? $atendente->nome : (Yii::$app->user->identity->username ?? 'admin');
            LogSistema::gravar(LogSistema::TIPO_ATENDIMENTO, LogSistema::NIVEL_INFO,
                "{$userName} finalizou {$count} conversa(s) em massa",
                ['ids' => $ids]
            );
        }

        Yii::$app->session->setFlash('success', $count . ' conversa(s) finalizada(s).');
        return $this->redirect(Yii::$app->request->referrer ?: ['fila']);
    }

    /**
     * Devolver conversa para a fila.
     * POST /conversa/devolver?id=X
     */
    public function actionDevolver($id)
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Metodo nao permitido.');
        }

        $conversa = Conversa::findOne($id);
        if (!$conversa) {
            throw new NotFoundHttpException('Conversa nao encontrada.');
        }

        $atendente = $this->getAtendente();

        // Apenas o atendente atribuido ou admin pode devolver
        if (!$this->isAdmin() && (!$atendente || $conversa->atendente_id !== $atendente->id)) {
            throw new ForbiddenHttpException('Voce nao pode devolver esta conversa.');
        }

        // Decrementar contador do atendente
        if ($conversa->atendente_id) {
            $atendenteConv = Atendente::findOne($conversa->atendente_id);
            if ($atendenteConv && $atendenteConv->conversas_ativas > 0) {
                $atendenteConv->conversas_ativas = (int)$atendenteConv->conversas_ativas - 1;
                $atendenteConv->save(false);
            }
        }

        $conversa->devolvida_por = $conversa->atendente_id;
        $conversa->atendente_id = null;
        $conversa->status = Conversa::STATUS_AGUARDANDO;
        $conversa->atendida_em = null;
        $conversa->save(false);

        Yii::$app->session->setFlash('info', 'Conversa devolvida para a fila.');
        return $this->redirect(['meu-console']);
    }

    /**
     * Visualizar detalhes de uma conversa.
     */
    public function actionView($id)
    {
        $model = Conversa::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException('Conversa nao encontrada.');
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    // ==================== AJAX ENDPOINTS ====================

    /**
     * Retorna conversas aguardando como JSON (para o drawer da fila).
     * GET /conversa/fila-json
     */
    public function actionFilaJson()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $atendente = $this->getAtendente();
        $query = Conversa::find()
            ->with(['chat', 'whatsappAccount'])
            ->andWhere(['conversas.status' => Conversa::STATUS_AGUARDANDO])
            ->orderBy(['conversas.iniciada_em' => SORT_ASC]);

        // Filtrar por instancias vinculadas ao atendente
        if ($atendente) {
            $accountIds = $atendente->getAccountIds();
            if (!empty($accountIds)) {
                $query->andWhere(['conversas.account_id' => $accountIds]);
            } else {
                $query->andWhere('1=0');
            }
        }

        $conversas = $query->all();

        $data = [];
        foreach ($conversas as $conv) {
            $preview = '';
            if ($conv->chat_id) {
                $lastMsg = Message::find()
                    ->where(['chat_id' => $conv->chat_id])
                    ->orderBy(['timestamp' => SORT_DESC, 'id' => SORT_DESC])
                    ->one();
                if ($lastMsg) {
                    $preview = $lastMsg->message_text ?: ('[' . $lastMsg->message_type . ']');
                    if (mb_strlen($preview) > 60) {
                        $preview = mb_substr($preview, 0, 60) . '...';
                    }
                }
            }

            // Tempo na fila - usar ultima_msg_em (quando o cliente interagiu) ou iniciada_em
            $tempoFila = '';
            $tempoRef = $conv->ultima_msg_em ?: $conv->iniciada_em;
            if ($tempoRef) {
                $diff = time() - strtotime($tempoRef);
                if ($diff < 60) $tempoFila = 'Agora';
                elseif ($diff < 3600) $tempoFila = floor($diff / 60) . ' min';
                else $tempoFila = floor($diff / 3600) . 'h ' . floor(($diff % 3600) / 60) . 'min';
            }

            $data[] = [
                'id' => $conv->id,
                'cliente_nome' => $conv->cliente_nome ?: 'Cliente',
                'cliente_numero' => $conv->cliente_numero,
                'chat_id' => $conv->chat_id,
                'chat_jid' => $conv->chat ? $conv->chat->chat_id : null,
                'preview' => $preview,
                'tempo_fila' => $tempoFila,
                'account_name' => $conv->whatsappAccount ? $conv->whatsappAccount->session_name : null,
            ];
        }

        return ['success' => true, 'count' => count($data), 'conversas' => $data];
    }

    /**
     * Aceitar conversa via AJAX — retorna JSON com dados do chat.
     * POST /conversa/pegar-ajax?id=X
     */
    public function actionPegarAjax($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Metodo nao permitido.'];
        }

        $atendente = $this->getAtendente();
        if (!$atendente) {
            return ['success' => false, 'error' => 'Voce nao esta vinculado como atendente.'];
        }

        if ($atendente->conversas_ativas >= $atendente->max_conversas) {
            return ['success' => false, 'error' => 'Limite de conversas atingido (' . $atendente->max_conversas . ').'];
        }

        $conversa = Conversa::findOne($id);
        if (!$conversa) {
            return ['success' => false, 'error' => 'Conversa nao encontrada.'];
        }

        if ($conversa->status !== Conversa::STATUS_AGUARDANDO) {
            return ['success' => false, 'error' => 'Conversa ja foi pega por outro atendente.'];
        }

        // Atribuir
        $conversa->atendente_id = $atendente->id;
        $conversa->status = Conversa::STATUS_EM_ATENDIMENTO;
        $conversa->atendida_em = date('Y-m-d H:i:s');
        $conversa->save(false);

        $atendente->conversas_ativas = (int)$atendente->conversas_ativas + 1;
        $atendente->ultimo_acesso = date('Y-m-d H:i:s');
        $atendente->save(false);

        LogSistema::gravar(LogSistema::TIPO_ATENDIMENTO, LogSistema::NIVEL_INFO,
            "Atendente {$atendente->nome} pegou conversa com " . ($conversa->cliente_nome ?: $conversa->cliente_numero),
            ['conversa_id' => $conversa->id, 'atendente_id' => $atendente->id]
        );

        return [
            'success' => true,
            'conversa_id' => $conversa->id,
            'chat_id' => $conversa->chat_id,
            'chat_jid' => $conversa->chat ? $conversa->chat->chat_id : null,
            'cliente_nome' => $conversa->cliente_nome ?: 'Cliente',
            'cliente_numero' => $conversa->cliente_numero,
        ];
    }

    /**
     * Finalizar conversa via AJAX.
     * POST /conversa/finalizar-ajax?id=X
     */
    public function actionFinalizarAjax($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Metodo nao permitido.'];
        }

        $conversa = Conversa::findOne($id);
        if (!$conversa) {
            return ['success' => false, 'error' => 'Conversa nao encontrada.'];
        }

        $atendente = $this->getAtendente();
        if (!$this->isAdmin() && (!$atendente || $conversa->atendente_id !== $atendente->id)) {
            return ['success' => false, 'error' => 'Sem permissao.'];
        }

        if ($conversa->atendente_id) {
            $atendenteConv = Atendente::findOne($conversa->atendente_id);
            if ($atendenteConv && $atendenteConv->conversas_ativas > 0) {
                $atendenteConv->conversas_ativas = (int)$atendenteConv->conversas_ativas - 1;
                $atendenteConv->save(false);
            }
        }

        $conversa->status = Conversa::STATUS_FINALIZADA;
        $conversa->finalizada_em = date('Y-m-d H:i:s');
        $conversa->save(false);

        $userName = $atendente ? $atendente->nome : (Yii::$app->user->identity->username ?? 'admin');
        LogSistema::gravar(LogSistema::TIPO_ATENDIMENTO, LogSistema::NIVEL_INFO,
            "{$userName} finalizou conversa com " . ($conversa->cliente_nome ?: $conversa->cliente_numero),
            ['conversa_id' => $conversa->id]
        );

        return ['success' => true];
    }

    /**
     * Devolver conversa via AJAX.
     * POST /conversa/devolver-ajax?id=X
     */
    public function actionDevolverAjax($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return ['success' => false, 'error' => 'Metodo nao permitido.'];
        }

        $conversa = Conversa::findOne($id);
        if (!$conversa) {
            return ['success' => false, 'error' => 'Conversa nao encontrada.'];
        }

        $atendente = $this->getAtendente();
        if (!$this->isAdmin() && (!$atendente || $conversa->atendente_id !== $atendente->id)) {
            return ['success' => false, 'error' => 'Sem permissao.'];
        }

        if ($conversa->atendente_id) {
            $atendenteConv = Atendente::findOne($conversa->atendente_id);
            if ($atendenteConv && $atendenteConv->conversas_ativas > 0) {
                $atendenteConv->conversas_ativas = (int)$atendenteConv->conversas_ativas - 1;
                $atendenteConv->save(false);
            }
        }

        $conversa->devolvida_por = $conversa->atendente_id;
        $conversa->atendente_id = null;
        $conversa->status = Conversa::STATUS_AGUARDANDO;
        $conversa->atendida_em = null;
        $conversa->save(false);

        return ['success' => true];
    }

    /**
     * Retorna conversas ativas do atendente como JSON (para inicializar grid).
     * GET /conversa/minhas-conversas
     */
    public function actionMinhasConversas()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $atendente = $this->getAtendente();
        if (!$atendente) {
            return ['success' => true, 'conversas' => []];
        }

        $conversas = Conversa::find()
            ->with('chat')
            ->where(['atendente_id' => $atendente->id])
            ->andWhere(['!=', 'status', Conversa::STATUS_FINALIZADA])
            ->orderBy(['ultima_msg_em' => SORT_DESC])
            ->limit(8)
            ->all();

        $data = [];
        foreach ($conversas as $conv) {
            $data[] = [
                'conversa_id' => $conv->id,
                'chat_id' => $conv->chat_id,
                'chat_jid' => $conv->chat ? $conv->chat->chat_id : null,
                'cliente_nome' => $conv->cliente_nome ?: 'Cliente',
                'cliente_numero' => $conv->cliente_numero,
                'status' => $conv->status,
            ];
        }

        return [
            'success' => true,
            'conversas' => $data,
            'conversas_ativas' => (int)$atendente->conversas_ativas,
            'max_conversas' => (int)$atendente->max_conversas,
        ];
    }

    /**
     * Inicia ou reabre uma conversa com um contato.
     * Busca por contact_id ou jid. Redireciona ao painel do dashboard.
     */
    public function actionIniciarConversa()
    {
        $contactId = Yii::$app->request->get('contact_id');
        $jid = Yii::$app->request->get('jid');

        // Buscar contato
        $contact = null;
        if ($contactId) {
            $contact = Contact::findOne($contactId);
        } elseif ($jid) {
            $contact = Contact::find()->where(['jid' => $jid])->one();
        }

        if (!$contact) {
            Yii::$app->session->setFlash('error', 'Contato nao encontrado.');
            return $this->redirect(['/contact/index']);
        }

        $atendente = $this->getAtendente();
        if (!$atendente) {
            Yii::$app->session->setFlash('error', 'Voce nao esta vinculado como atendente.');
            return $this->redirect(['/contact/index']);
        }

        // Verificar se ja existe conversa ativa com esse contato
        $chat = Chat::find()->where(['chat_id' => $contact->jid])->one();

        if ($chat) {
            $conversaAtiva = Conversa::find()
                ->where(['chat_id' => $chat->id])
                ->andWhere(['!=', 'status', Conversa::STATUS_FINALIZADA])
                ->one();

            if ($conversaAtiva) {
                // Ja existe conversa ativa, redirecionar ao painel
                Yii::$app->session->setFlash('info', 'Conversa com ' . ($contact->name ?: $contact->phone_number) . ' ja esta ativa.');
                return $this->redirect(['/chat/painel']);
            }
        }

        // Verificar limite de conversas
        if ($atendente->conversas_ativas >= $atendente->max_conversas) {
            Yii::$app->session->setFlash('warning', 'Limite de conversas atingido (' . $atendente->max_conversas . '). Finalize alguma conversa antes.');
            return $this->redirect(['/contact/index']);
        }

        // Se nao existe chat, criar
        if (!$chat) {
            $chat = new Chat();
            $chat->account_id = $contact->account_id;
            $chat->chat_id = $contact->jid;
            $chat->chat_name = $contact->name ?: $contact->phone_number;
            $chat->chat_type = 'individual';
            $chat->save(false);
        }

        // Criar nova conversa
        $phoneNumber = $contact->phone_number ?: str_replace('@s.whatsapp.net', '', $contact->jid);
        $conversa = new Conversa();
        $conversa->cliente_numero = $phoneNumber;
        $conversa->cliente_nome = $contact->name ?: null;
        $conversa->chat_id = $chat->id;
        $conversa->account_id = $chat->account_id;
        $conversa->atendente_id = $atendente->id;
        $conversa->status = Conversa::STATUS_EM_ATENDIMENTO;
        $conversa->iniciada_em = date('Y-m-d H:i:s');
        $conversa->atendida_em = date('Y-m-d H:i:s');
        $conversa->save(false);

        // Incrementar conversas ativas
        $atendente->conversas_ativas = (int)$atendente->conversas_ativas + 1;
        $atendente->save(false);

        Yii::$app->session->setFlash('success', 'Conversa iniciada com ' . ($contact->name ?: $phoneNumber) . '.');
        return $this->redirect(['/chat/painel']);
    }
}
