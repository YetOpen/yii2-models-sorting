<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 07.05.18
 * Time: 20:05
 */

namespace MP\ARSorting;

use Yii;
use yii\base\InvalidConfigException;
use yii\grid\DataColumn;
use yii\helpers\Json;
use yii\web\View;

/**
 * Class    ARSortBehavior
 * @package MP\ARSorting
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class ARSortColumn extends DataColumn
{
    /**
     * Hide column
     *
     * @var bool
     */
    public $hideColumn = true;

    /**
     * Module options
     *
     * @var array
     */
    public $moduleOptions = [];

    /**
     * Sortable plugin options
     *
     * @var array
     */
    public $sortingOptions = [];

    /**
     * Model class name
     *
     * @var string|NULL
     */
    public $modelClass = NULL;

    /**
     * @var string
     */
    private $encryptionKey;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!empty(Yii::$app->params['MPARSort']['encryptionKey'])) {
            $this->encryptionKey = Yii::$app->params['MPARSort']['encryptionKey'];
        } else {
            throw new InvalidConfigException('Required `encryptionKey` param isn\'t set.');
        }

        if (empty($this->modelClass)) {
            $this->modelClass = \get_class($this->grid->filterModel);
        }

        if (empty($this->grid->options['id'])) {
            $this->grid->options['id'] = $this->getUniqueId();
        }

        if (empty($this->moduleOptions['actionUrl'])) {
            $this->moduleOptions['actionUrl'] = 'mp-ar-sort';
        }

        $this->sortingOptions['attribute'] = $this->attribute;

        if ($this->hideColumn) {
            $this->filterOptions['style'] = 'display: none;';
            $this->headerOptions['style'] = 'display: none;';
        }

        /** @var ActiveRecord $modelClass */
        $modelClass = $this->modelClass;
        $primaryKey = $modelClass::primaryKey()[0];

        $this->contentOptions = function ($model) use ($primaryKey) {
            return [
                'data-id'        => $model->$primaryKey,
                'data-attribute' => $this->attribute,
                'style'          => $this->hideColumn ? 'display: none;' : NULL,
            ];
        };

        $localModuleOptions = [
            'attribute'    => $this->attribute,
            'mpDataARSort' => \base64_encode(Yii::$app->getSecurity()->encryptByKey(json_encode([
                'modelClass' => $modelClass,
                'attribute'  => $this->attribute,
                'primaryKey' => $primaryKey,
            ]), $this->encryptionKey)),
        ];

        $this->registerAssets($localModuleOptions);
    }

    /**
     * Register assets
     *
     * @param array $localModuleOptions
     *
     * @return void
     */
    protected function registerAssets(array $localModuleOptions = [])
    {
        ARSortAsset::register($this->grid->view);

        $this->grid->view->registerCss('.ar-sort-placeholder{background: #fff9e2 !important;}');
        $this->grid->view->registerJs('ARSort.init(' . Json::encode($this->moduleOptions) . ');', View::POS_END, 'ARSortInit');
        $this->grid->view->registerJs("ARSort.attachGrid('#{$this->grid->options['id']}', " . Json::encode($localModuleOptions) . ", " . Json::encode($this->sortingOptions) . ");");
    }

    /**
     * Get grid unique class
     *
     * @return string
     */
    private function getUniqueId()
    {
        return 'mp-grid-' . \rand(10000, 99999);
    }
}
