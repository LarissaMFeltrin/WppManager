<?php

namespace backend\controllers;

use common\models\LoginForm;
use common\models\Chat;
use common\models\LogSistema;
use common\models\Message;
use common\models\WhatsappAccount;
use common\models\Conversa;
use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
        ];
    }

    /**
     * Displays homepage / Dashboard.
     *
     * @return string
     */
    public function actionIndex()
    {
        $user = Yii::$app->user->identity;
        $isAdmin = $user && $user->isAdmin();

        // Total de Chats
        $chatQuery = Chat::find();
        if (!$isAdmin) {
            $chatQuery->innerJoin('whatsapp_accounts wa', 'wa.id = chats.account_id')
                ->andWhere(['wa.empresa_id' => $user->empresa_id]);
        }
        $totalChats = $chatQuery->count();

        // Mensagens Hoje
        $hoje = date('Y-m-d');
        $msgQuery = Message::find()
            ->andWhere(['>=', 'messages.created_at', $hoje . ' 00:00:00'])
            ->andWhere(['<=', 'messages.created_at', $hoje . ' 23:59:59']);
        if (!$isAdmin) {
            $msgQuery->innerJoin('chats c', 'c.id = messages.chat_id')
                ->innerJoin('whatsapp_accounts wa', 'wa.id = c.account_id')
                ->andWhere(['wa.empresa_id' => $user->empresa_id]);
        }
        $mensagensHoje = $msgQuery->count();

        // Instancias Online
        $instQuery = WhatsappAccount::find()->andWhere(['is_connected' => 1]);
        if (!$isAdmin) {
            $instQuery->andWhere(['empresa_id' => $user->empresa_id]);
        }
        $instanciasOnline = $instQuery->count();

        // Conversas Ativas (status != finalizada)
        $convQuery = Conversa::find()
            ->andWhere(['!=', 'status', Conversa::STATUS_FINALIZADA]);
        $conversasAtivas = $convQuery->count();

        // Ultimas 10 mensagens
        $ultimasMsgsQuery = Message::find()
            ->with(['chat', 'chat.whatsappAccount'])
            ->orderBy(['messages.id' => SORT_DESC])
            ->limit(10);
        if (!$isAdmin) {
            $ultimasMsgsQuery->innerJoin('chats c2', 'c2.id = messages.chat_id')
                ->innerJoin('whatsapp_accounts wa2', 'wa2.id = c2.account_id')
                ->andWhere(['wa2.empresa_id' => $user->empresa_id]);
        }
        $ultimasMensagens = $ultimasMsgsQuery->all();

        // Instancias WhatsApp
        $accountsQuery = WhatsappAccount::find()->with('empresa');
        if (!$isAdmin) {
            $accountsQuery->andWhere(['empresa_id' => $user->empresa_id]);
        }
        $instancias = $accountsQuery->all();

        return $this->render('index', [
            'totalChats' => $totalChats,
            'mensagensHoje' => $mensagensHoje,
            'instanciasOnline' => $instanciasOnline,
            'conversasAtivas' => $conversasAtivas,
            'ultimasMensagens' => $ultimasMensagens,
            'instancias' => $instancias,
        ]);
    }

    /**
     * Login action.
     *
     * @return string|Response
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'main-login';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            LogSistema::gravar(LogSistema::TIPO_INFO, LogSistema::NIVEL_INFO,
                "Login realizado: " . Yii::$app->user->identity->username
            );
            return $this->goBack();
        }

        if (Yii::$app->request->isPost) {
            LogSistema::gravar(LogSistema::TIPO_ERRO, LogSistema::NIVEL_WARNING,
                "Tentativa de login falhou para: " . ($model->username ?? 'desconhecido')
            );
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }
}
