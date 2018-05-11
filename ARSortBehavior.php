<?php
/**
 * Created by PhpStorm.
 * User: Yarmaliuk Mikhail
 * Date: 08.05.18
 * Time: 20:38
 */

namespace MP\ARSorting;

use yii\base\Behavior;
use yii\base\ModelEvent;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\db\Expression;

/**
 * Class    ARSortBehavior
 * @package MP\ARSorting
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 *
 * @property ActiveRecord $owner
 */
class ARSortBehavior extends Behavior
{
    /**
     * @var string
     */
    public $attribute;

    /**
     * @var \Closure
     */
    public $queryCondition;

    /**
     * Enable auto increment sort attribute for new model
     *
     * @var bool
     */
    public $sortRule = true;

    /**
     * @return array
     */
    public function events(): array
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'attachRule',
            ActiveRecord::EVENT_AFTER_INSERT  => 'resortModels',
            ActiveRecord::EVENT_AFTER_UPDATE  => 'resortModels',
        ];
    }

    /**
     * Attach sort attribute rule
     *
     * @param ModelEvent $event
     *
     * @return void
     */
    public function attachRule($event): void
    {
        if ($this->sortRule) {
            /** @var ActiveRecord $model */
            $model = $event->sender;

            if (empty($model->{$this->attribute})) {
                $query = $model::find();

                if ($this->queryCondition instanceof \Closure) {
                    \call_user_func($this->queryCondition, $query, $model);
                }

                $model->{$this->attribute} = ($query->max($this->attribute) ? : 0) + 1;
            }
        }
    }

    /**
     * Event handler for after record saving
     *
     * @param AfterSaveEvent $event
     *
     * @return bool
     */
    public function resortModels($event): bool
    {
        $owner = $this->owner;

        // Detection change sorting
        if (isset($event->changedAttributes[$this->attribute]) || $owner->isNewRecord) {
            $tableName  = $owner::getTableSchema()->fullName;
            $primaryKey = $owner::primaryKey()[0];
            $queryModel = $owner::find();

            if ($this->queryCondition instanceof \Closure) {
                \call_user_func($this->queryCondition, $queryModel, $owner);
            }

            $query = $queryModel->createCommand()->update($tableName, [
                $tableName . '.' . $this->attribute => new Expression("(SELECT @arIcr := @arIcr + 1)"),
            ], $queryModel->where);

            $resultQuery = 'SELECT @arIcr := 0;'
                . $query->getRawSql()
                . " ORDER BY `$tableName`.`{$this->attribute}` ASC, IF(`$tableName`.`$primaryKey` = {$owner->$primaryKey}, 0, 1) ASC";

            return $query->setSql($resultQuery)->execute();
        }

        return false;
    }
}
