<?php

namespace backend\controllers;

use common\models\User;
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
 * UserController - CRUD de usuarios (admin only)
 */
class UserController extends BaseController
{
    public $passwordInput;

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
     * Lista usuarios.
     * @return string
     */
    public function actionIndex()
    {
        $query = User::find()->with('empresa');

        if (!$this->isAdmin()) {
            $query->andWhere(['empresa_id' => $this->getEmpresaId()]);
        }

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
     * Visualizar usuario.
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
     * Criar usuario.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new User();

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $model->load($post);

            // Definir senha
            $password = $post['password_plain'] ?? '';
            if (!empty($password)) {
                $model->setPassword($password);
            } else {
                $model->addError('password_hash', 'A senha é obrigatória.');
            }

            $model->generateAuthKey();

            // Empresa: supervisor/agent herda a empresa do usuário logado
            if (!$this->isAdmin()) {
                $model->empresa_id = $this->getEmpresaId();
            }

            // Definir status padrão se não informado
            if ($model->status === null || $model->status === '') {
                $model->status = User::STATUS_ACTIVE;
            }

            // Definir role padrão se não informado
            if (empty($model->role)) {
                $model->role = User::ROLE_AGENT;
            }

            if (!$model->hasErrors() && $model->save()) {
                // Auto-criar perfil de atendente
                $this->saveAtendenteProfile($model, $post);
                Yii::$app->session->setFlash('success', 'Usuário criado com sucesso.');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                $errors = $model->getFirstErrors();
                $errorMsg = implode('<br>', $errors);
                Yii::$app->session->setFlash('error', 'Erro ao criar usuário:<br>' . $errorMsg);
            }
        }

        return $this->render('create', [
            'model' => $model,
            'empresas' => $this->getEmpresasList(),
            'accounts' => $this->getAccountsList(),
            'atendente' => new Atendente(['max_conversas' => 5]),
            'selectedAccounts' => [],
        ]);
    }

    /**
     * Atualizar usuario.
     * @param int $id
     * @return string|\yii\web\Response
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $atendente = $model->atendente;

        if (Yii::$app->request->isPost) {
            $post = Yii::$app->request->post();
            $model->load($post);

            // Atualizar senha se informada
            $password = $post['password_plain'] ?? '';
            if (!empty($password)) {
                $model->setPassword($password);
            }

            if ($model->save()) {
                // Auto-atualizar perfil de atendente
                $model->refresh();
                $this->saveAtendenteProfile($model, $post);
                Yii::$app->session->setFlash('success', 'Usuário atualizado com sucesso.');
                return $this->redirect(['view', 'id' => $model->id]);
            } else {
                $errors = $model->getFirstErrors();
                $errorMsg = implode('<br>', $errors);
                Yii::$app->session->setFlash('error', 'Erro ao atualizar usuário:<br>' . $errorMsg);
            }
            // Recarregar atendente após tentativa de salvar
            $atendente = $model->atendente;
        }

        return $this->render('update', [
            'model' => $model,
            'empresas' => $this->getEmpresasList(),
            'accounts' => $this->getAccountsList(),
            'atendente' => $atendente ?: new Atendente(['max_conversas' => 5]),
            'selectedAccounts' => $atendente ? $atendente->getAccountIds() : [],
        ]);
    }

    /**
     * Excluir usuario.
     * @param int $id
     * @return \yii\web\Response
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        // Nao permitir excluir a si mesmo
        if ($model->id == Yii::$app->user->id) {
            Yii::$app->session->setFlash('error', 'Voce nao pode excluir seu proprio usuario.');
            return $this->redirect(['index']);
        }

        $model->delete();
        Yii::$app->session->setFlash('success', 'Usuario excluido com sucesso.');
        return $this->redirect(['index']);
    }

    /**
     * Encontra model User pelo ID.
     * @param int $id
     * @return User
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $query = User::find()->where(['id' => $id]);
        if (!$this->isAdmin()) {
            $query->andWhere(['empresa_id' => $this->getEmpresaId()]);
        }

        if (($model = $query->one()) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Usuario nao encontrado.');
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
     * Cria ou atualiza o perfil Atendente vinculado ao usuario.
     */
    protected function saveAtendenteProfile(User $user, array $post)
    {
        $atendente = $user->atendente ?: new Atendente();

        $atendente->user_id = $user->id;
        $atendente->nome = $user->username;
        $atendente->email = $user->email;
        $atendente->empresa_id = $user->empresa_id;

        // Campos do formulario
        $atendenteData = $post['Atendente'] ?? [];
        if (isset($atendenteData['max_conversas'])) {
            $atendente->max_conversas = (int)$atendenteData['max_conversas'];
        }
        if (isset($atendenteData['status'])) {
            $atendente->status = $atendenteData['status'];
        }

        // Defaults para novo atendente
        if ($atendente->isNewRecord) {
            if (empty($atendente->max_conversas)) {
                $atendente->max_conversas = 5;
            }
            if (empty($atendente->status)) {
                $atendente->status = Atendente::STATUS_OFFLINE;
            }
        }

        // Senha do atendente = mesma senha do usuario (hash)
        if ($atendente->isNewRecord) {
            $atendente->senha = $user->password_hash;
        }
        $password = $post['password_plain'] ?? '';
        if (!empty($password)) {
            $atendente->senha = $user->password_hash;
        }

        $atendente->save(false);

        // Salvar vinculo com instancias
        $accountIds = $post['account_ids'] ?? [];
        AtendenteAccount::deleteAll(['atendente_id' => $atendente->id]);
        foreach ($accountIds as $accountId) {
            $aa = new AtendenteAccount();
            $aa->atendente_id = $atendente->id;
            $aa->account_id = (int)$accountId;
            $aa->save(false);
        }
    }
}
