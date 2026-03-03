<?php

namespace backend\controllers;

use common\models\Atendente;
use common\models\AtendenteAccount;
use common\models\Empresa;
use common\models\WhatsappAccount;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;

/**
 * AtendenteController - CRUD de atendentes
 */
class AtendenteController extends BaseController
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
     * {@inheritdoc}
     */
    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->requireAdminOrSupervisor();
        return true;
    }

    /**
     * Lista atendentes.
     * @return string
     */
    public function actionIndex()
    {
        $query = Atendente::find()->with('empresa');
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
     * Visualizar atendente.
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
     * Criar atendente.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Atendente();

        if (!$this->isAdmin()) {
            $model->empresa_id = $this->getEmpresaId();
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->saveAccountAssignments($model, Yii::$app->request->post('account_ids', []));
            Yii::$app->session->setFlash('success', 'Atendente criado com sucesso.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
            'empresas' => $this->getEmpresasList(),
            'accounts' => $this->getAccountsList(),
            'selectedAccounts' => [],
        ]);
    }

    /**
     * Atualizar atendente.
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->saveAccountAssignments($model, Yii::$app->request->post('account_ids', []));
            Yii::$app->session->setFlash('success', 'Atendente atualizado com sucesso.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'empresas' => $this->getEmpresasList(),
            'accounts' => $this->getAccountsList(),
            'selectedAccounts' => $model->getAccountIds(),
        ]);
    }

    /**
     * Excluir atendente.
     * @param int $id
     * @return \yii\web\Response
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();
        Yii::$app->session->setFlash('success', 'Atendente excluido com sucesso.');
        return $this->redirect(['index']);
    }

    /**
     * Encontra model Atendente pelo ID.
     * @param int $id
     * @return Atendente
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $query = Atendente::find()->where(['id' => $id]);
        $this->applyEmpresaFilter($query);

        if (($model = $query->one()) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Atendente nao encontrado.');
    }

    /**
     * Retorna lista de empresas para dropdown.
     * @return array
     */
    protected function getEmpresasList()
    {
        return ArrayHelper::map(Empresa::find()->where(['status' => Empresa::STATUS_ATIVO])->all(), 'id', 'nome');
    }

    /**
     * Retorna lista de instancias WhatsApp para checkboxes.
     * @return array
     */
    protected function getAccountsList()
    {
        $query = WhatsappAccount::find();
        if (!$this->isAdmin()) {
            $query->andWhere(['empresa_id' => $this->getEmpresaId()]);
        }
        return ArrayHelper::map($query->all(), 'id', 'session_name');
    }

    /**
     * Salva vinculo atendente <-> instancias.
     */
    protected function saveAccountAssignments(Atendente $model, array $accountIds)
    {
        AtendenteAccount::deleteAll(['atendente_id' => $model->id]);

        foreach ($accountIds as $accountId) {
            $aa = new AtendenteAccount();
            $aa->atendente_id = $model->id;
            $aa->account_id = (int)$accountId;
            $aa->save(false);
        }
    }
}
