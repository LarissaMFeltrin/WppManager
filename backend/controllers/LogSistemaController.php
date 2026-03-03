<?php

namespace backend\controllers;

use common\models\LogSistema;
use Yii;
use yii\data\ActiveDataProvider;

/**
 * LogSistemaController - Visualizacao de logs do sistema
 */
class LogSistemaController extends BaseController
{
    /**
     * Lista logs do sistema com filtros.
     * @return string
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $tipo = $request->get('tipo', '');
        $nivel = $request->get('nivel', '');

        $query = LogSistema::find()
            ->orderBy(['id' => SORT_DESC]);

        if (!empty($tipo)) {
            $query->andWhere(['tipo' => $tipo]);
        }

        if (!empty($nivel)) {
            $query->andWhere(['nivel' => $nivel]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'tipo' => $tipo,
            'nivel' => $nivel,
        ]);
    }

    /**
     * Visualiza detalhes de um log.
     * @param int $id
     * @return string
     */
    public function actionView($id)
    {
        $model = LogSistema::findOne($id);
        if (!$model) {
            throw new \yii\web\NotFoundHttpException('Log nao encontrado.');
        }

        return $this->render('view', [
            'model' => $model,
        ]);
    }
}
