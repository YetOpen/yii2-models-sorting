Active Record sorting for Yii2
===========================
Sorting active record models (grid, form, code) by field.
Automaticaly update sort index. Drag&Drop and etc. 

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist matthew-p/yii2-models-sorting "@dev"
```

or add

```
"matthew-p/yii2-models-sorting": "@dev"
```

to the require section of your `composer.json` file.

Usage
-----

Once the extension is installed, simply use it in your code by:

Add sort column to gridview, if needed (Drag&drop attached to first "td" in "tr"):
```php
GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel'  => $searchModel,
    'columns'      => [
        ['class' => 'yii\grid\SerialColumn'],

        'id',
        'title',
        // Sort column
        [
            'class'      => \MP\ARSorting\ARSortColumn::class,
            'attribute'  => 'sort',
            'hideColumn' => false,
        ],
        [
            'attribute' => 'created_at',
            'format'    => ['date', 'format' => 'php: d/m/Y H:i:s'],
        ],

        ['class' => 'yii\grid\ActionColumn'],
    ],
]);
```

Add action in controller (only for gridview sorting):
```php
class SampleController extends Controller
{
...
    public function actions(): array
    {
        return array_merge(parent::actions(), [
            'mp-ar-sort' => \MP\ARSorting\ARSortAction::class,
        ]);
    }
...
}
```

Add behavior to AR model:
```php
class SampleModel extends ActiveRecord
{
    ...
    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        return [
            [
                'class'          => \MP\ARSorting\ARSortBehavior::class,
                'attribute'      => 'sort',
                // Optional
                'queryCondition' => function ($query, self $model) {
                    $query->andWhere(['parent_id' => $model->parent_id]);
                },
            ],
        ];
    }
    ...
}
```

Define encryption key in params.php:
```
'MPARSort' => [
    'encryptionKey' => 'RandomKey',
],

or 

'MPComponents' => [
    'encryptionKey' => 'RandomKey',
],

```

That's all. Check it.