<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;

/**
 * BaseController - Controller base com filtro por empresa
 *
 * Controllers que precisam de isolamento por empresa devem estender esta classe.
 */
class BaseController extends Controller
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
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Retorna o empresa_id do usuario logado.
     * @return int|null
     */
    protected function getEmpresaId()
    {
        $user = Yii::$app->user->identity;
        return $user ? $user->empresa_id : null;
    }

    /**
     * Aplica filtro por empresa_id na query, exceto para admin.
     * @param \yii\db\ActiveQuery $query
     * @return \yii\db\ActiveQuery
     */
    protected function applyEmpresaFilter($query)
    {
        if (!$this->isAdmin()) {
            $query->andWhere(['empresa_id' => $this->getEmpresaId()]);
        }
        return $query;
    }

    /**
     * Verifica se o usuario logado e admin.
     * @return bool
     */
    protected function isAdmin()
    {
        $user = Yii::$app->user->identity;
        return $user && $user->isAdmin();
    }

    /**
     * Verifica se o usuario logado e supervisor ou admin.
     * @return bool
     */
    protected function isAdminOrSupervisor()
    {
        $user = Yii::$app->user->identity;
        return $user && ($user->isAdmin() || $user->isSupervisor());
    }

    /**
     * Helper: Lanca excecao se nao for admin.
     * @throws ForbiddenHttpException
     */
    protected function requireAdmin()
    {
        if (!$this->isAdmin()) {
            throw new ForbiddenHttpException('Acesso permitido apenas para administradores.');
        }
    }

    /**
     * Helper: Lanca excecao se nao for admin ou supervisor.
     * @throws ForbiddenHttpException
     */
    protected function requireAdminOrSupervisor()
    {
        if (!$this->isAdminOrSupervisor()) {
            throw new ForbiddenHttpException('Acesso permitido apenas para administradores e supervisores.');
        }
    }
}
