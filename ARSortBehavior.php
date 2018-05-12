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
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);

        $owner->on(ActiveRecord::EVENT_BEFORE_UPDATE, [$this, 'changeSortAttribute']);
        $owner->on(ActiveRecord::EVENT_BEFORE_INSERT, [$this, 'changeSortAttribute']);

        $owner->on(ActiveRecord::EVENT_BEFORE_INSERT, [$this, 'attachRule']);

        $owner->on(ActiveRecord::EVENT_AFTER_INSERT, [$this, 'resortModels']);
        $owner->on(ActiveRecord::EVENT_AFTER_UPDATE, [$this, 'resortModels']);
        $owner->on(ActiveRecord::EVENT_AFTER_DELETE, [$this, 'resortModels']);
    }

    /**
     * Fix change sort attribute value
     *
     * @param ModelEvent $event
     *
     * @return void
     */
    public function changeSortAttribute($event): void
    {
        $owner = $this->owner;

        // If the attribute has changed to the higher side
        if (!$owner->isNewRecord && isset($owner->dirtyAttributes[$this->attribute])) {
            if ($owner->{$this->attribute} > $owner->getOldAttribute($this->attribute)) {
                $owner->setAttribute($this->attribute, $owner->{$this->attribute} + 1);
            }
        }
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
        $owner = $this->owner;

        if ($this->sortRule && empty($owner->{$this->attribute})) {
            $query = $owner::find();

            if ($this->queryCondition instanceof \Closure) {
                \call_user_func($this->queryCondition, $query, $owner);
            }

            $owner->{$this->attribute} = ($query->max($this->attribute) ? : 0) + 1;
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
        if (array_key_exists($this->attribute, $event->changedAttributes ?? []) || $owner->isNewRecord) {
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
