<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 08.05.18
 * Time: 20:16
 */

namespace MP\ARSorting;

use yii\bootstrap\BootstrapAsset;
use yii\jui\JuiAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

/**
 * Class    ARSortAsset
 * @package MP\ARSorting
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class ARSortAsset extends AssetBundle
{
    /**
     * @var string
     */
    public $sourcePath = __DIR__ . '/assets';

    /**
     * @var array
     */
    public $js = [
        'ar-sort.js',
    ];

    /**
     * @var array
     */
    public $depends = [
        YiiAsset::class,
        JuiAsset::class,
        BootstrapAsset::class,
    ];
}