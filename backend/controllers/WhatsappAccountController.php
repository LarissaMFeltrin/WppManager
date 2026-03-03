<?php

namespace backend\controllers;

use common\models\WhatsappAccount;
use common\models\Empresa;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

/**
 * WhatsappAccountController - CRUD de contas/instancias WhatsApp
 */
class WhatsappAccountController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ]);
    }

    /**
     * Lista as instancias WhatsApp.
     * @return string
     */
    public function actionIndex()
    {
        $query = WhatsappAccount::find()->with('empresa');
        $this->applyEmpresaFilter($query);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Exibe uma instancia.
     * @param int $id
     * @return string
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Cria uma nova instancia.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new WhatsappAccount();

        if ($model->load(Yii::$app->request->post())) {
            // Definir user_id do usuario logado
            $model->user_id = Yii::$app->user->id;

            // Se nao for admin, forcar empresa do usuario logado
            if (!$this->isAdmin()) {
                $model->empresa_id = $this->getEmpresaId();
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Instância criada com sucesso.');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                $errors = $model->getFirstErrors();
                $errorMsg = implode('<br>', $errors);
                Yii::$app->session->setFlash('error', 'Erro ao criar instância:<br>' . $errorMsg);
            }
        }

        // Se nao for admin, pre-definir empresa
        if (!$this->isAdmin()) {
            $model->empresa_id = $this->getEmpresaId();
        }

        return $this->render('create', [
            'model' => $model,
            'empresas' => $this->getEmpresasList(),
        ]);
    }

    /**
     * Atualiza uma instancia existente.
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Instância atualizada com sucesso.');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                $errors = $model->getFirstErrors();
                $errorMsg = implode('<br>', $errors);
                Yii::$app->session->setFlash('error', 'Erro ao atualizar instância:<br>' . $errorMsg);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'empresas' => $this->getEmpresasList(),
        ]);
    }

    /**
     * Exclui uma instancia.
     * @param int $id
     * @return \yii\web\Response
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Instancia excluida com sucesso.');
        return $this->redirect(['index']);
    }

    /**
     * Tela de conexao WhatsApp - mostra QR code e pairing code.
     * @param int $id
     * @return string
     */
    public function actionConnect($id)
    {
        $model = $this->findModel($id);

        return $this->render('connect', [
            'model' => $model,
        ]);
    }

    /**
     * Proxy para API do servico Node.js (evita CORS no browser).
     * @param int $id
     * @return \yii\web\Response
     */
    public function actionConnectionStatus($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $model = $this->findModel($id);
        $port = $model->service_port ?: 3000;

        try {
            $url = "http://127.0.0.1:{$port}/api/connection-status";
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'method' => 'GET',
                ],
            ]);
            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                return [
                    'success' => false,
                    'status' => 'service_offline',
                    'message' => 'Servico WhatsApp nao esta rodando. Inicie com: cd whatsapp-service && node src/server.js',
                ];
            }

            return json_decode($response, true);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Encontra o model WhatsappAccount pelo ID.
     * @param int $id
     * @return WhatsappAccount
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $query = WhatsappAccount::find()->where(['id' => $id]);
        $this->applyEmpresaFilter($query);

        if (($model = $query->one()) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Instancia WhatsApp nao encontrada.');
    }

    /**
     * Retorna lista de empresas para dropdown.
     * @return array
     */
    protected function getEmpresasList()
    {
        return ArrayHelper::map(Empresa::find()->where(['status' => Empresa::STATUS_ATIVO])->all(), 'id', 'nome');
    }
}
