<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 07.05.18
 * Time: 20:07
 */

namespace MP\ARSorting;

use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class    ARSortAction
 * @package MP\ARSorting
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class ARSortAction extends Action
{
    /**
     * Sort models
     *
     * @return array
     */
    public function run()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $encryptionKey = NULL;
        $result        = false;
        $newPositions  = [];

        if (!empty(Yii::$app->params['MPComponents']['encryptionKey'])) {
            $encryptionKey = Yii::$app->params['MPComponents']['encryptionKey'];
        } else if (!empty(Yii::$app->params['MPARSort']['encryptionKey'])) {
            $encryptionKey = Yii::$app->params['MPARSort']['encryptionKey'];
        } else {
            throw new InvalidConfigException('Required `encryptionKey` param isn\'t set.');
        }

        $data = Yii::$app->getSecurity()->decryptByKey(\base64_decode(Yii::$app->request->post('mpDataARSort')), $encryptionKey);

        if (empty($data) || empty($data = json_decode($data, true))) {
            throw new NotFoundHttpException();
        }

        /** @var ActiveRecord $modelClassName */
        $modelClassName = $data['modelClass'];
        $primaryKey     = $data['primaryKey'];
        /** @var ARSortBehavior $model */
        $model = $modelClassName::find()
            ->where([$primaryKey => Yii::$app->request->post('currentID')])
            ->one();

        if ($model instanceof ActiveRecord) {
            $beforeModelID = Yii::$app->request->post('beforeID');
            $afterModelID  = Yii::$app->request->post('afterID');

            if ($beforeModelID || $afterModelID) {
                $targetModelQuery = $modelClassName::find()
                    ->andFilterWhere([
                        'OR',
                        [$primaryKey => $beforeModelID],
                        [$primaryKey => $afterModelID],
                    ])
                    ->orderBy([$data['attribute'] => \SORT_ASC]);

                if ($model->hasProperty('queryCondition') && $model->queryCondition instanceof \Closure) {
                    \call_user_func($model->queryCondition, $targetModelQuery, $model);
                }

                $targetModel = $targetModelQuery->one();

                if ($targetModel instanceof ActiveRecord) {
                    $newSortValue = $targetModel->getAttribute($data['attribute']);

                    // If the attribute has changed to the lower side
                    if ($beforeModelID == $targetModel->$primaryKey && $model->getAttribute($data['attribute']) > $newSortValue) {
                        $newSortValue += 1;
                    }

                    $model->setAttribute($data['attribute'], $newSortValue);
                    $result = $model->save();

                    if ($result) {
                        $newPositions = $modelClassName::find()
                            ->select([$primaryKey, $data['attribute']])
                            ->orFilterWhere([$primaryKey => $beforeModelID])
                            ->orFilterWhere([$primaryKey => $afterModelID])
                            ->orFilterWhere([$primaryKey => $model->$primaryKey])
                            ->createCommand()
                            ->queryAll(\PDO::FETCH_KEY_PAIR);
                    }
                }
            }
        }

        return [
            'resut'     => $result,
            'positions' => $newPositions,
        ];
    }
}
