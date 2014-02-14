<?php
/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db;

use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\base\InvalidParamException;
use yii\base\ModelEvent;
use yii\base\NotSupportedException;
use yii\base\UnknownMethodException;
use yii\base\InvalidCallException;

/**
 * ActiveRecord is the base class for classes representing relational data in terms of objects.
 *
 * @include @yii/db/ActiveRecord.md
 *
 * @property array $dirtyAttributes The changed attribute values (name-value pairs). This property is
 * read-only.
 * @property boolean $isNewRecord Whether the record is new and should be inserted when calling [[save()]].
 * @property array $oldAttributes The old attribute values (name-value pairs).
 * @property mixed $oldPrimaryKey The old primary key value. An array (column name => column value) is
 * returned if the primary key is composite. A string is returned otherwise (null will be returned if the key
 * value is null). This property is read-only.
 * @property mixed $primaryKey The primary key value. An array (column name => column value) is returned if
 * the primary key is composite. A string is returned otherwise (null will be returned if the key value is null).
 * This property is read-only.
 * @property array $relatedRecords An array of related records indexed by relation names. This property is
 * read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
abstract class BaseActiveRecord extends Model implements ActiveRecordInterface
{
	/**
	 * @event Event an event that is triggered when the record is initialized via [[init()]].
	 */
	const EVENT_INIT = 'init';
	/**
	 * @event Event an event that is triggered after the record is created and populated with query result.
	 */
	const EVENT_AFTER_FIND = 'afterFind';
	/**
	 * @event ModelEvent an event that is triggered before inserting a record.
	 * You may set [[ModelEvent::isValid]] to be false to stop the insertion.
	 */
	const EVENT_BEFORE_INSERT = 'beforeInsert';
	/**
	 * @event Event an event that is triggered after a record is inserted.
	 */
	const EVENT_AFTER_INSERT = 'afterInsert';
	/**
	 * @event ModelEvent an event that is triggered before updating a record.
	 * You may set [[ModelEvent::isValid]] to be false to stop the update.
	 */
	const EVENT_BEFORE_UPDATE = 'beforeUpdate';
	/**
	 * @event Event an event that is triggered after a record is updated.
	 */
	const EVENT_AFTER_UPDATE = 'afterUpdate';
	/**
	 * @event ModelEvent an event that is triggered before deleting a record.
	 * You may set [[ModelEvent::isValid]] to be false to stop the deletion.
	 */
	const EVENT_BEFORE_DELETE = 'beforeDelete';
	/**
	 * @event Event an event that is triggered after a record is deleted.
	 */
	const EVENT_AFTER_DELETE = 'afterDelete';

	/**
	 * @var array attribute values indexed by attribute names
	 */
	private $_attributes = [];
	/**
	 * @var array old attribute values indexed by attribute names.
	 */
	private $_oldAttributes;
	/**
	 * @var array related models indexed by the relation names
	 */
	private $_related = [];


	/**
	 * Creates an [[ActiveQuery]] instance for query purpose.
	 *
	 * @include @yii/db/ActiveRecord-find.md
	 *
	 * @param mixed $q the query parameter. This can be one of the followings:
	 *
	 *  - a scalar value (integer or string): query by a single primary key value and return the
	 *    corresponding record.
	 *  - an array of name-value pairs: query by a set of column values and return a single record matching all of them.
	 *  - null: return a new [[ActiveQuery]] object for further query purpose.
	 *
	 * @return ActiveQuery|static|null When `$q` is null, a new [[ActiveQuery]] instance
	 * is returned; when `$q` is a scalar or an array, an ActiveRecord object matching it will be
	 * returned (null will be returned if there is no matching).
	 * @throws InvalidConfigException if the AR class does not have a primary key
	 * @see createQuery()
	 */
	public static function find($q = null)
	{
		$query = static::createQuery();
		if (is_array($q)) {
			return $query->andWhere($q)->one();
		} elseif ($q !== null) {
			// query by primary key
			$primaryKey = static::primaryKey();
			if (isset($primaryKey[0])) {
				return $query->andWhere([$primaryKey[0] => $q])->one();
			} else {
				throw new InvalidConfigException(get_called_class() . ' must have a primary key.');
			}
		}
		return $query;
	}

	/**
	 * Updates the whole table using the provided attribute values and conditions.
	 * For example, to change the status to be 1 for all customers whose status is 2:
	 *
	 * ~~~
	 * Customer::updateAll(['status' => 1], 'status = 2');
	 * ~~~
	 *
	 * @param array $attributes attribute values (name-value pairs) to be saved into the table
	 * @param string|array $condition the conditions that will be put in the WHERE part of the UPDATE SQL.
	 * Please refer to [[Query::where()]] on how to specify this parameter.
	 * @return integer the number of rows updated
	 */
	public static function updateAll($attributes, $condition = '')
	{
		throw new NotSupportedException(__METHOD__ . ' is not supported.');
	}

	/**
	 * Updates the whole table using the provided counter changes and conditions.
	 * For example, to increment all customers' age by 1,
	 *
	 * ~~~
	 * Customer::updateAllCounters(['age' => 1]);
	 * ~~~
	 *
	 * @param array $counters the counters to be updated (attribute name => increment value).
	 * Use negative values if you want to decrement the counters.
	 * @param string|array $condition the conditions that will be put in the WHERE part of the UPDATE SQL.
	 * Please refer to [[Query::where()]] on how to specify this parameter.
	 * @return integer the number of rows updated
	 */
	public static function updateAllCounters($counters, $condition = '')
	{
		throw new NotSupportedException(__METHOD__ . ' is not supported.');
	}

	/**
	 * Deletes rows in the table using the provided conditions.
	 * WARNING: If you do not specify any condition, this method will delete ALL rows in the table.
	 *
	 * For example, to delete all customers whose status is 3:
	 *
	 * ~~~
	 * Customer::deleteAll('status = 3');
	 * ~~~
	 *
	 * @param string|array $condition the conditions that will be put in the WHERE part of the DELETE SQL.
	 * Please refer to [[Query::where()]] on how to specify this parameter.
	 * @param array $params the parameters (name => value) to be bound to the query.
	 * @return integer the number of rows deleted
	 */
	public static function deleteAll($condition = '', $params = [])
	{
		throw new NotSupportedException(__METHOD__ . ' is not supported.');
	}

	/**
	 * Returns the name of the column that stores the lock version for implementing optimistic locking.
	 *
	 * Optimistic locking allows multiple users to access the same record for edits and avoids
	 * potential conflicts. In case when a user attempts to save the record upon some staled data
	 * (because another user has modified the data), a [[StaleObjectException]] exception will be thrown,
	 * and the update or deletion is skipped.
	 *
	 * Optimistic locking is only supported by [[update()]] and [[delete()]].
	 *
	 * To use Optimistic locking:
	 *
	 * 1. Create a column to store the version number of each row. The column type should be `BIGINT DEFAULT 0`.
	 *    Override this method to return the name of this column.
	 * 2. In the Web form that collects the user input, add a hidden field that stores
	 *    the lock version of the recording being updated.
	 * 3. In the controller action that does the data updating, try to catch the [[StaleObjectException]]
	 *    and implement necessary business logic (e.g. merging the changes, prompting stated data)
	 *    to resolve the conflict.
	 *
	 * @return string the column name that stores the lock version of a table row.
	 * If null is returned (default implemented), optimistic locking will not be supported.
	 */
	public function optimisticLock()
	{
		return null;
	}

	/**
	 * PHP getter magic method.
	 * This method is overridden so that attributes and related objects can be accessed like properties.
	 *
	 * @param string $name property name
	 * @throws \yii\base\InvalidParamException if relation name is wrong
	 * @return mixed property value
	 * @see getAttribute()
	 */
	public function __get($name)
	{
		if (isset($this->_attributes[$name]) || array_key_exists($name, $this->_attributes)) {
			return $this->_attributes[$name];
		} elseif ($this->hasAttribute($name)) {
			return null;
		} else {
			if (isset($this->_related[$name]) || array_key_exists($name, $this->_related)) {
				return $this->_related[$name];
			}
			$value = parent::__get($name);
			if ($value instanceof ActiveRelationInterface) {
				if (method_exists($this, 'get' . $name)) {
					$method = new \ReflectionMethod($this, 'get' . $name);
					$realName = lcfirst(substr($method->getName(), 3));
					if ($realName !== $name) {
						throw new InvalidParamException('Relation names are case sensitive. ' . get_class($this) . " has a relation named \"$realName\" instead of \"$name\".");
					}
				}
				$this->populateRelation($name, $value->multiple ? $value->all() : $value->one());
				return $this->_related[$name];
			}
			return $value;
		}
	}

	/**
	 * PHP setter magic method.
	 * This method is overridden so that AR attributes can be accessed like properties.
	 * @param string $name property name
	 * @param mixed $value property value
	 */
	public function __set($name, $value)
	{
		if ($this->hasAttribute($name)) {
			$this->_attributes[$name] = $value;
		} else {
			parent::__set($name, $value);
		}
	}

	/**
	 * Checks if a property value is null.
	 * This method overrides the parent implementation by checking if the named attribute is null or not.
	 * @param string $name the property name or the event name
	 * @return boolean whether the property value is null
	 */
	public function __isset($name)
	{
		try {
			return $this->__get($name) !== null;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Sets a component property to be null.
	 * This method overrides the parent implementation by clearing
	 * the specified attribute value.
	 * @param string $name the property name or the event name
	 */
	public function __unset($name)
	{
		if ($this->hasAttribute($name)) {
			unset($this->_attributes[$name]);
		} else {
			if (isset($this->_related[$name])) {
				unset($this->_related[$name]);
			} else {
				parent::__unset($name);
			}
		}
	}

	/**
	 * Declares a `has-one` relation.
	 * The declaration is returned in terms of an [[ActiveRelation]] instance
	 * through which the related record can be queried and retrieved back.
	 *
	 * A `has-one` relation means that there is at most one related record matching
	 * the criteria set by this relation, e.g., a customer has one country.
	 *
	 * For example, to declare the `country` relation for `Customer` class, we can write
	 * the following code in the `Customer` class:
	 *
	 * ~~~
	 * public function getCountry()
	 * {
	 *     return $this->hasOne(Country::className(), ['id' => 'country_id']);
	 * }
	 * ~~~
	 *
	 * Note that in the above, the 'id' key in the `$link` parameter refers to an attribute name
	 * in the related class `Country`, while the 'country_id' value refers to an attribute name
	 * in the current AR class.
	 *
	 * Call methods declared in [[ActiveRelation]] to further customize the relation.
	 *
	 * @param string $class the class name of the related record
	 * @param array $link the primary-foreign key constraint. The keys of the array refer to
	 * the attributes of the record associated with the `$class` model, while the values of the
	 * array refer to the corresponding attributes in **this** AR class.
	 * @return ActiveRelationInterface the relation object.
	 */
	public function hasOne($class, $link)
	{
		/** @var ActiveRecord $class */
		return $class::createRelation([
			'modelClass' => $class,
			'primaryModel' => $this,
			'link' => $link,
			'multiple' => false,
		]);
	}

	/**
	 * Declares a `has-many` relation.
	 * The declaration is returned in terms of an [[ActiveRelation]] instance
	 * through which the related record can be queried and retrieved back.
	 *
	 * A `has-many` relation means that there are multiple related records matching
	 * the criteria set by this relation, e.g., a customer has many orders.
	 *
	 * For example, to declare the `orders` relation for `Customer` class, we can write
	 * the following code in the `Customer` class:
	 *
	 * ~~~
	 * public function getOrders()
	 * {
	 *     return $this->hasMany(Order::className(), ['customer_id' => 'id']);
	 * }
	 * ~~~
	 *
	 * Note that in the above, the 'customer_id' key in the `$link` parameter refers to
	 * an attribute name in the related class `Order`, while the 'id' value refers to
	 * an attribute name in the current AR class.
	 *
	 * @param string $class the class name of the related record
	 * @param array $link the primary-foreign key constraint. The keys of the array refer to
	 * the attributes of the record associated with the `$class` model, while the values of the
	 * array refer to the corresponding attributes in **this** AR class.
	 * @return ActiveRelationInterface the relation object.
	 */
	public function hasMany($class, $link)
	{
		/** @var ActiveRecord $class */
		return $class::createRelation([
			'modelClass' => $class,
			'primaryModel' => $this,
			'link' => $link,
			'multiple' => true,
		]);
	}

	/**
	 * Populates the named relation with the related records.
	 * Note that this method does not check if the relation exists or not.
	 * @param string $name the relation name (case-sensitive)
	 * @param ActiveRecord|array|null $records the related records to be populated into the relation.
	 */
	public function populateRelation($name, $records)
	{
		$this->_related[$name] = $records;
	}

	/**
	 * Check whether the named relation has been populated with records.
	 * @param string $name the relation name (case-sensitive)
	 * @return bool whether relation has been populated with records.
	 */
	public function isRelationPopulated($name)
	{
		return array_key_exists($name, $this->_related);
	}

	/**
	 * Returns all populated related records.
	 * @return array an array of related records indexed by relation names.
	 */
	public function getRelatedRecords()
	{
		return $this->_related;
	}

	/**
	 * Returns a value indicating whether the model has an attribute with the specified name.
	 * @param string $name the name of the attribute
	 * @return boolean whether the model has an attribute with the specified name.
	 */
	public function hasAttribute($name)
	{
		return isset($this->_attributes[$name]) || in_array($name, $this->attributes());
	}

	/**
	 * Returns the named attribute value.
	 * If this record is the result of a query and the attribute is not loaded,
	 * null will be returned.
	 * @param string $name the attribute name
	 * @return mixed the attribute value. Null if the attribute is not set or does not exist.
	 * @see hasAttribute()
	 */
	public function getAttribute($name)
	{
		return isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
	}

	/**
	 * Sets the named attribute value.
	 * @param string $name the attribute name
	 * @param mixed $value the attribute value.
	 * @throws InvalidParamException if the named attribute does not exist.
	 * @see hasAttribute()
	 */
	public function setAttribute($name, $value)
	{
		if ($this->hasAttribute($name)) {
			$this->_attributes[$name] = $value;
		} else {
			throw new InvalidParamException(get_class($this) . ' has no attribute named "' . $name . '".');
		}
	}

	/**
	 * Returns the old attribute values.
	 * @return array the old attribute values (name-value pairs)
	 */
	public function getOldAttributes()
	{
		return $this->_oldAttributes === null ? [] : $this->_oldAttributes;
	}

	/**
	 * Sets the old attribute values.
	 * All existing old attribute values will be discarded.
	 * @param array $values old attribute values to be set.
	 */
	public function setOldAttributes($values)
	{
		$this->_oldAttributes = $values;
	}

	/**
	 * Returns the old value of the named attribute.
	 * If this record is the result of a query and the attribute is not loaded,
	 * null will be returned.
	 * @param string $name the attribute name
	 * @return mixed the old attribute value. Null if the attribute is not loaded before
	 * or does not exist.
	 * @see hasAttribute()
	 */
	public function getOldAttribute($name)
	{
		return isset($this->_oldAttributes[$name]) ? $this->_oldAttributes[$name] : null;
	}

	/**
	 * Sets the old value of the named attribute.
	 * @param string $name the attribute name
	 * @param mixed $value the old attribute value.
	 * @throws InvalidParamException if the named attribute does not exist.
	 * @see hasAttribute()
	 */
	public function setOldAttribute($name, $value)
	{
		if (isset($this->_oldAttributes[$name]) || $this->hasAttribute($name)) {
			$this->_oldAttributes[$name] = $value;
		} else {
			throw new InvalidParamException(get_class($this) . ' has no attribute named "' . $name . '".');
		}
	}

	/**
	 * Marks an attribute dirty.
	 * This method may be called to force updating a record when calling [[update()]],
	 * even if there is no change being made to the record.
	 * @param string $name the attribute name
	 */
	public function markAttributeDirty($name)
	{
		unset($this->_oldAttributes[$name]);
	}

	/**
	 * Returns a value indicating whether the named attribute has been changed.
	 * @param string $name the name of the attribute
	 * @return boolean whether the attribute has been changed
	 */
	public function isAttributeChanged($name)
	{
		if (isset($this->_attributes[$name], $this->_oldAttributes[$name])) {
			return $this->_attributes[$name] !== $this->_oldAttributes[$name];
		} else {
			return isset($this->_attributes[$name]) || isset($this->_oldAttributes[$name]);
		}
	}

	/**
	 * Returns the attribute values that have been modified since they are loaded or saved most recently.
	 * @param string[]|null $names the names of the attributes whose values may be returned if they are
	 * changed recently. If null, [[attributes()]] will be used.
	 * @return array the changed attribute values (name-value pairs)
	 */
	public function getDirtyAttributes($names = null)
	{
		if ($names === null) {
			$names = $this->attributes();
		}
		$names = array_flip($names);
		$attributes = [];
		if ($this->_oldAttributes === null) {
			foreach ($this->_attributes as $name => $value) {
				if (isset($names[$name])) {
					$attributes[$name] = $value;
				}
			}
		} else {
			foreach ($this->_attributes as $name => $value) {
				if (isset($names[$name]) && (!array_key_exists($name, $this->_oldAttributes) || $value !== $this->_oldAttributes[$name])) {
					$attributes[$name] = $value;
				}
			}
		}
		return $attributes;
	}

	/**
	 * Saves the current record.
	 *
	 * This method will call [[insert()]] when [[isNewRecord]] is true, or [[update()]]
	 * when [[isNewRecord]] is false.
	 *
	 * For example, to save a customer record:
	 *
	 * ~~~
	 * $customer = new Customer;  // or $customer = Customer::find($id);
	 * $customer->name = $name;
	 * $customer->email = $email;
	 * $customer->save();
	 * ~~~
	 *
	 *
	 * @param boolean $runValidation whether to perform validation before saving the record.
	 * If the validation fails, the record will not be saved to database.
	 * @param array $attributes list of attributes that need to be saved. Defaults to null,
	 * meaning all attributes that are loaded from DB will be saved.
	 * @return boolean whether the saving succeeds
	 */
	public function save($runValidation = true, $attributes = null)
	{
		if ($this->getIsNewRecord()) {
			return $this->insert($runValidation, $attributes);
		} else {
			return $this->update($runValidation, $attributes) !== false;
		}
	}

	/**
	 * Saves the changes to this active record into the associated database table.
	 *
	 * This method performs the following steps in order:
	 *
	 * 1. call [[beforeValidate()]] when `$runValidation` is true. If validation
	 *    fails, it will skip the rest of the steps;
	 * 2. call [[afterValidate()]] when `$runValidation` is true.
	 * 3. call [[beforeSave()]]. If the method returns false, it will skip the
	 *    rest of the steps;
	 * 4. save the record into database. If this fails, it will skip the rest of the steps;
	 * 5. call [[afterSave()]];
	 *
	 * In the above step 1, 2, 3 and 5, events [[EVENT_BEFORE_VALIDATE]],
	 * [[EVENT_BEFORE_UPDATE]], [[EVENT_AFTER_UPDATE]] and [[EVENT_AFTER_VALIDATE]]
	 * will be raised by the corresponding methods.
	 *
	 * Only the [[dirtyAttributes|changed attribute values]] will be saved into database.
	 *
	 * For example, to update a customer record:
	 *
	 * ~~~
	 * $customer = Customer::find($id);
	 * $customer->name = $name;
	 * $customer->email = $email;
	 * $customer->update();
	 * ~~~
	 *
	 * Note that it is possible the update does not affect any row in the table.
	 * In this case, this method will return 0. For this reason, you should use the following
	 * code to check if update() is successful or not:
	 *
	 * ~~~
	 * if ($this->update() !== false) {
	 *     // update successful
	 * } else {
	 *     // update failed
	 * }
	 * ~~~
	 *
	 * @param boolean $runValidation whether to perform validation before saving the record.
	 * If the validation fails, the record will not be inserted into the database.
	 * @param array $attributes list of attributes that need to be saved. Defaults to null,
	 * meaning all attributes that are loaded from DB will be saved.
	 * @return integer|boolean the number of rows affected, or false if validation fails
	 * or [[beforeSave()]] stops the updating process.
	 * @throws StaleObjectException if [[optimisticLock|optimistic locking]] is enabled and the data
	 * being updated is outdated.
	 * @throws \Exception in case update failed.
	 */
	public function update($runValidation = true, $attributes = null)
	{
		if ($runValidation && !$this->validate($attributes)) {
			return false;
		}
		return $this->updateInternal($attributes);
	}

	/**
	 * Updates the specified attributes.
	 *
	 * This method is a shortcut to [[update()]] when data validation is not needed
	 * and only a list of attributes need to be updated.
	 *
	 * You may specify the attributes to be updated as name list or name-value pairs.
	 * If the latter, the corresponding attribute values will be modified accordingly.
	 * The method will then save the specified attributes into database.
	 *
	 * Note that this method will NOT perform data validation.
	 *
	 * @param array $attributes the attributes (names or name-value pairs) to be updated
	 * @return integer|boolean the number of rows affected, or false if [[beforeSave()]] stops the updating process.
	 */
	public function updateAttributes($attributes)
	{
		$attrs = [];
		foreach ($attributes as $name => $value) {
			if (is_integer($name)) {
				$attrs[] = $value;
			} else {
				$this->$name = $value;
				$attrs[] = $name;
			}
		}
		return $this->update(false, $attrs);
	}

	/**
	 * @see update()
	 * @throws StaleObjectException
	 */
	protected function updateInternal($attributes = null)
	{
		if (!$this->beforeSave(false)) {
			return false;
		}
		$values = $this->getDirtyAttributes($attributes);
		if (empty($values)) {
			$this->afterSave(false);
			return 0;
		}
		$condition = $this->getOldPrimaryKey(true);
		$lock = $this->optimisticLock();
		if ($lock !== null) {
			if (!isset($values[$lock])) {
				$values[$lock] = $this->$lock + 1;
			}
			$condition[$lock] = $this->$lock;
		}
		// We do not check the return value of updateAll() because it's possible
		// that the UPDATE statement doesn't change anything and thus returns 0.
		$rows = $this->updateAll($values, $condition);

		if ($lock !== null && !$rows) {
			throw new StaleObjectException('The object being updated is outdated.');
		}

		foreach ($values as $name => $value) {
			$this->_oldAttributes[$name] = $this->_attributes[$name];
		}
		$this->afterSave(false);
		return $rows;
	}

	/**
	 * Updates one or several counter columns for the current AR object.
	 * Note that this method differs from [[updateAllCounters()]] in that it only
	 * saves counters for the current AR object.
	 *
	 * An example usage is as follows:
	 *
	 * ~~~
	 * $post = Post::find($id);
	 * $post->updateCounters(['view_count' => 1]);
	 * ~~~
	 *
	 * @param array $counters the counters to be updated (attribute name => increment value)
	 * Use negative values if you want to decrement the counters.
	 * @return boolean whether the saving is successful
	 * @see updateAllCounters()
	 */
	public function updateCounters($counters)
	{
		if ($this->updateAllCounters($counters, $this->getOldPrimaryKey(true)) > 0) {
			foreach ($counters as $name => $value) {
				$this->_attributes[$name] += $value;
				$this->_oldAttributes[$name] = $this->_attributes[$name];
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Deletes the table row corresponding to this active record.
	 *
	 * This method performs the following steps in order:
	 *
	 * 1. call [[beforeDelete()]]. If the method returns false, it will skip the
	 *    rest of the steps;
	 * 2. delete the record from the database;
	 * 3. call [[afterDelete()]].
	 *
	 * In the above step 1 and 3, events named [[EVENT_BEFORE_DELETE]] and [[EVENT_AFTER_DELETE]]
	 * will be raised by the corresponding methods.
	 *
	 * @return integer|boolean the number of rows deleted, or false if the deletion is unsuccessful for some reason.
	 * Note that it is possible the number of rows deleted is 0, even though the deletion execution is successful.
	 * @throws StaleObjectException if [[optimisticLock|optimistic locking]] is enabled and the data
	 * being deleted is outdated.
	 * @throws \Exception in case delete failed.
	 */
	public function delete()
	{
		$result = false;
		if ($this->beforeDelete()) {
			// we do not check the return value of deleteAll() because it's possible
			// the record is already deleted in the database and thus the method will return 0
			$condition = $this->getOldPrimaryKey(true);
			$lock = $this->optimisticLock();
			if ($lock !== null) {
				$condition[$lock] = $this->$lock;
			}
			$result = $this->deleteAll($condition);
			if ($lock !== null && !$result) {
				throw new StaleObjectException('The object being deleted is outdated.');
			}
			$this->_oldAttributes = null;
			$this->afterDelete();
		}
		return $result;
	}

	/**
	 * Returns a value indicating whether the current record is new.
	 * @return boolean whether the record is new and should be inserted when calling [[save()]].
	 */
	public function getIsNewRecord()
	{
		return $this->_oldAttributes === null;
	}

	/**
	 * Sets the value indicating whether the record is new.
	 * @param boolean $value whether the record is new and should be inserted when calling [[save()]].
	 * @see getIsNewRecord()
	 */
	public function setIsNewRecord($value)
	{
		$this->_oldAttributes = $value ? null : $this->_attributes;
	}

	/**
	 * Initializes the object.
	 * This method is called at the end of the constructor.
	 * The default implementation will trigger an [[EVENT_INIT]] event.
	 * If you override this method, make sure you call the parent implementation at the end
	 * to ensure triggering of the event.
	 */
	public function init()
	{
		parent::init();
		$this->trigger(self::EVENT_INIT);
	}

	/**
	 * This method is called when the AR object is created and populated with the query result.
	 * The default implementation will trigger an [[EVENT_AFTER_FIND]] event.
	 * When overriding this method, make sure you call the parent implementation to ensure the
	 * event is triggered.
	 */
	public function afterFind()
	{
		$this->trigger(self::EVENT_AFTER_FIND);
	}

	/**
	 * This method is called at the beginning of inserting or updating a record.
	 * The default implementation will trigger an [[EVENT_BEFORE_INSERT]] event when `$insert` is true,
	 * or an [[EVENT_BEFORE_UPDATE]] event if `$insert` is false.
	 * When overriding this method, make sure you call the parent implementation like the following:
	 *
	 * ~~~
	 * public function beforeSave($insert)
	 * {
	 *     if (parent::beforeSave($insert)) {
	 *         // ...custom code here...
	 *         return true;
	 *     } else {
	 *         return false;
	 *     }
	 * }
	 * ~~~
	 *
	 * @param boolean $insert whether this method called while inserting a record.
	 * If false, it means the method is called while updating a record.
	 * @return boolean whether the insertion or updating should continue.
	 * If false, the insertion or updating will be cancelled.
	 */
	public function beforeSave($insert)
	{
		$event = new ModelEvent;
		$this->trigger($insert ? self::EVENT_BEFORE_INSERT : self::EVENT_BEFORE_UPDATE, $event);
		return $event->isValid;
	}

	/**
	 * This method is called at the end of inserting or updating a record.
	 * The default implementation will trigger an [[EVENT_AFTER_INSERT]] event when `$insert` is true,
	 * or an [[EVENT_AFTER_UPDATE]] event if `$insert` is false.
	 * When overriding this method, make sure you call the parent implementation so that
	 * the event is triggered.
	 * @param boolean $insert whether this method called while inserting a record.
	 * If false, it means the method is called while updating a record.
	 */
	public function afterSave($insert)
	{
		$this->trigger($insert ? self::EVENT_AFTER_INSERT : self::EVENT_AFTER_UPDATE);
	}

	/**
	 * This method is invoked before deleting a record.
	 * The default implementation raises the [[EVENT_BEFORE_DELETE]] event.
	 * When overriding this method, make sure you call the parent implementation like the following:
	 *
	 * ~~~
	 * public function beforeDelete()
	 * {
	 *     if (parent::beforeDelete()) {
	 *         // ...custom code here...
	 *         return true;
	 *     } else {
	 *         return false;
	 *     }
	 * }
	 * ~~~
	 *
	 * @return boolean whether the record should be deleted. Defaults to true.
	 */
	public function beforeDelete()
	{
		$event = new ModelEvent;
		$this->trigger(self::EVENT_BEFORE_DELETE, $event);
		return $event->isValid;
	}

	/**
	 * This method is invoked after deleting a record.
	 * The default implementation raises the [[EVENT_AFTER_DELETE]] event.
	 * You may override this method to do postprocessing after the record is deleted.
	 * Make sure you call the parent implementation so that the event is raised properly.
	 */
	public function afterDelete()
	{
		$this->trigger(self::EVENT_AFTER_DELETE);
	}

	/**
	 * Repopulates this active record with the latest data.
	 * @return boolean whether the row still exists in the database. If true, the latest data
	 * will be populated to this active record. Otherwise, this record will remain unchanged.
	 */
	public function refresh()
	{
		$record = $this->find($this->getPrimaryKey(true));
		if ($record === null) {
			return false;
		}
		foreach ($this->attributes() as $name) {
			$this->_attributes[$name] = isset($record->_attributes[$name]) ? $record->_attributes[$name] : null;
		}
		$this->_oldAttributes = $this->_attributes;
		$this->_related = [];
		return true;
	}

	/**
	 * Returns a value indicating whether the given active record is the same as the current one.
	 * The comparison is made by comparing the table names and the primary key values of the two active records.
	 * If one of the records [[isNewRecord|is new]] they are also considered not equal.
	 * @param ActiveRecord $record record to compare to
	 * @return boolean whether the two active records refer to the same row in the same database table.
	 */
	public function equals($record)
	{
		if ($this->getIsNewRecord() || $record->getIsNewRecord()) {
			return false;
		}
		return get_class($this) === get_class($record) && $this->getPrimaryKey() === $record->getPrimaryKey();
	}

	/**
	 * Returns the primary key value(s).
	 * @param boolean $asArray whether to return the primary key value as an array. If true,
	 * the return value will be an array with column names as keys and column values as values.
	 * Note that for composite primary keys, an array will always be returned regardless of this parameter value.
	 * @property mixed The primary key value. An array (column name => column value) is returned if
	 * the primary key is composite. A string is returned otherwise (null will be returned if
	 * the key value is null).
	 * @return mixed the primary key value. An array (column name => column value) is returned if the primary key
	 * is composite or `$asArray` is true. A string is returned otherwise (null will be returned if
	 * the key value is null).
	 */
	public function getPrimaryKey($asArray = false)
	{
		$keys = $this->primaryKey();
		if (count($keys) === 1 && !$asArray) {
			return isset($this->_attributes[$keys[0]]) ? $this->_attributes[$keys[0]] : null;
		} else {
			$values = [];
			foreach ($keys as $name) {
				$values[$name] = isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
			}
			return $values;
		}
	}

	/**
	 * Returns the old primary key value(s).
	 * This refers to the primary key value that is populated into the record
	 * after executing a find method (e.g. find(), findAll()).
	 * The value remains unchanged even if the primary key attribute is manually assigned with a different value.
	 * @param boolean $asArray whether to return the primary key value as an array. If true,
	 * the return value will be an array with column name as key and column value as value.
	 * If this is false (default), a scalar value will be returned for non-composite primary key.
	 * @property mixed The old primary key value. An array (column name => column value) is
	 * returned if the primary key is composite. A string is returned otherwise (null will be
	 * returned if the key value is null).
	 * @return mixed the old primary key value. An array (column name => column value) is returned if the primary key
	 * is composite or `$asArray` is true. A string is returned otherwise (null will be returned if
	 * the key value is null).
	 */
	public function getOldPrimaryKey($asArray = false)
	{
		$keys = $this->primaryKey();
		if (count($keys) === 1 && !$asArray) {
			return isset($this->_oldAttributes[$keys[0]]) ? $this->_oldAttributes[$keys[0]] : null;
		} else {
			$values = [];
			foreach ($keys as $name) {
				$values[$name] = isset($this->_oldAttributes[$name]) ? $this->_oldAttributes[$name] : null;
			}
			return $values;
		}
	}

	/**
	 * Populates an active record object using a row of data from the database/storage.
	 *
	 * This is an internal method meant to be called to create active record objects after
	 * fetching data from the database. It is mainly used by [[ActiveQuery]] to populate
	 * the query results into active records.
	 *
	 * When calling this method manually you should call [[afterFind()]] on the created
	 * record to trigger the [[EVENT_AFTER_FIND|afterFind Event]].
	 *
	 * @param BaseActiveRecord $record the record to be populated. In most cases this will be an instance
	 * created by [[instantiate()]] beforehand.
	 * @param array $row attribute values (name => value)
	 */
	public static function populateRecord($record, $row)
	{
		$columns = array_flip($record->attributes());
		foreach ($row as $name => $value) {
			if (isset($columns[$name])) {
				$record->_attributes[$name] = $value;
			} else {
				$record->$name = $value;
			}
		}
		$record->_oldAttributes = $record->_attributes;
	}

	/**
	 * Creates an active record instance.
	 *
	 * This method is called together with [[populateRecord()]] by [[ActiveQuery]].
	 * It is not meant to be used for creating new records directly.
	 *
	 * You may override this method if the instance being created
	 * depends on the row data to be populated into the record.
	 * For example, by creating a record based on the value of a column,
	 * you may implement the so-called single-table inheritance mapping.
	 * @param array $row row data to be populated into the record.
	 * @return static the newly created active record
	 */
	public static function instantiate($row)
	{
		return new static;
	}

	/**
	 * Returns whether there is an element at the specified offset.
	 * This method is required by the interface ArrayAccess.
	 * @param mixed $offset the offset to check on
	 * @return boolean whether there is an element at the specified offset.
	 */
	public function offsetExists($offset)
	{
		return $this->__isset($offset);
	}

	/**
	 * Returns the relation object with the specified name.
	 * A relation is defined by a getter method which returns an [[ActiveRelation]] object.
	 * It can be declared in either the Active Record class itself or one of its behaviors.
	 * @param string $name the relation name
	 * @return ActiveRelation the relation object
	 * @throws InvalidParamException if the named relation does not exist.
	 */
	public function getRelation($name)
	{
		$getter = 'get' . $name;
		try {
			// the relation could be defined in a behavior
			$relation = $this->$getter();
		} catch (UnknownMethodException $e) {
			throw new InvalidParamException(get_class($this) . ' has no relation named "' . $name . '".', 0, $e);
		}
		if (!$relation instanceof ActiveRelationInterface) {
			throw new InvalidParamException(get_class($this) . ' has no relation named "' . $name . '".');
		}

		if (method_exists($this, $getter)) {
			// relation name is case sensitive, trying to validate it when the relation is defined within this class
			$method = new \ReflectionMethod($this, $getter);
			$realName = lcfirst(substr($method->getName(), 3));
			if ($realName !== $name) {
				throw new InvalidParamException('Relation names are case sensitive. ' . get_class($this) . " has a relation named \"$realName\" instead of \"$name\".");
			}
		}

		return $relation;
	}

	/**
	 * Establishes the relationship between two models.
	 *
	 * The relationship is established by setting the foreign key value(s) in one model
	 * to be the corresponding primary key value(s) in the other model.
	 * The model with the foreign key will be saved into database without performing validation.
	 *
	 * If the relationship involves a pivot table, a new row will be inserted into the
	 * pivot table which contains the primary key values from both models.
	 *
	 * Note that this method requires that the primary key value is not null.
	 *
	 * @param string $name the case sensitive name of the relationship
	 * @param ActiveRecord $model the model to be linked with the current one.
	 * @param array $extraColumns additional column values to be saved into the pivot table.
	 * This parameter is only meaningful for a relationship involving a pivot table
	 * (i.e., a relation set with `[[ActiveRelation::via()]]` or `[[ActiveRelation::viaTable()]]`.)
	 * @throws InvalidCallException if the method is unable to link two models.
	 */
	public function link($name, $model, $extraColumns = [])
	{
		$relation = $this->getRelation($name);

		if ($relation->via !== null) {
			if ($this->getIsNewRecord() || $model->getIsNewRecord()) {
				throw new InvalidCallException('Unable to link models: both models must NOT be newly created.');
			}
			if (is_array($relation->via)) {
				/** @var ActiveRelation $viaRelation */
				list($viaName, $viaRelation) = $relation->via;
				$viaClass = $viaRelation->modelClass;
				// unset $viaName so that it can be reloaded to reflect the change
				unset($this->_related[$viaName]);
			} else {
				$viaRelation = $relation->via;
				$viaTable = reset($relation->via->from);
			}
			$columns = [];
			foreach ($viaRelation->link as $a => $b) {
				$columns[$a] = $this->$b;
			}
			foreach ($relation->link as $a => $b) {
				$columns[$b] = $model->$a;
			}
			foreach ($extraColumns as $k => $v) {
				$columns[$k] = $v;
			}
			if (is_array($relation->via)) {
				/** @var $viaClass ActiveRecord */
				/** @var $record ActiveRecord */
				$record = new $viaClass();
				foreach($columns as $column => $value) {
					$record->$column = $value;
				}
				$record->insert(false);
			} else {
				/** @var $viaTable string */
				static::getDb()->createCommand()
					->insert($viaTable, $columns)->execute();
			}
		} else {
			$p1 = $model->isPrimaryKey(array_keys($relation->link));
			$p2 = $this->isPrimaryKey(array_values($relation->link));
			if ($p1 && $p2) {
				if ($this->getIsNewRecord() && $model->getIsNewRecord()) {
					throw new InvalidCallException('Unable to link models: both models are newly created.');
				} elseif ($this->getIsNewRecord()) {
					$this->bindModels(array_flip($relation->link), $this, $model);
				} else {
					$this->bindModels($relation->link, $model, $this);
				}
			} elseif ($p1) {
				$this->bindModels(array_flip($relation->link), $this, $model);
			} elseif ($p2) {
				$this->bindModels($relation->link, $model, $this);
			} else {
				throw new InvalidCallException('Unable to link models: the link does not involve any primary key.');
			}
		}

		// update lazily loaded related objects
		if (!$relation->multiple) {
			$this->_related[$name] = $model;
		} elseif (isset($this->_related[$name])) {
			if ($relation->indexBy !== null) {
				$indexBy = $relation->indexBy;
				$this->_related[$name][$model->$indexBy] = $model;
			} else {
				$this->_related[$name][] = $model;
			}
		}
	}

	/**
	 * Destroys the relationship between two models.
	 *
	 * The model with the foreign key of the relationship will be deleted if `$delete` is true.
	 * Otherwise, the foreign key will be set null and the model will be saved without validation.
	 *
	 * @param string $name the case sensitive name of the relationship.
	 * @param ActiveRecord $model the model to be unlinked from the current one.
	 * @param boolean $delete whether to delete the model that contains the foreign key.
	 * If false, the model's foreign key will be set null and saved.
	 * If true, the model containing the foreign key will be deleted.
	 * @throws InvalidCallException if the models cannot be unlinked
	 */
	public function unlink($name, $model, $delete = false)
	{
		$relation = $this->getRelation($name);

		if ($relation->via !== null) {
			if (is_array($relation->via)) {
				/** @var ActiveRelation $viaRelation */
				list($viaName, $viaRelation) = $relation->via;
				$viaClass = $viaRelation->modelClass;
				unset($this->_related[$viaName]);
			} else {
				$viaRelation = $relation->via;
				$viaTable = reset($relation->via->from);
			}
			$columns = [];
			foreach ($viaRelation->link as $a => $b) {
				$columns[$a] = $this->$b;
			}
			foreach ($relation->link as $a => $b) {
				$columns[$b] = $model->$a;
			}
			if (is_array($relation->via)) {
				/** @var $viaClass ActiveRecord */
				if ($delete) {
					$viaClass::deleteAll($columns);
				} else {
					$nulls = [];
					foreach (array_keys($columns) as $a) {
						$nulls[$a] = null;
					}
					$viaClass::updateAll($nulls, $columns);
				}
			} else {
				/** @var $viaTable string */
				$command = static::getDb()->createCommand();
				if ($delete) {
					$command->delete($viaTable, $columns)->execute();
				} else {
					$nulls = [];
					foreach (array_keys($columns) as $a) {
						$nulls[$a] = null;
					}
					$command->update($viaTable, $nulls, $columns)->execute();
				}
			}
		} else {
			$p1 = $model->isPrimaryKey(array_keys($relation->link));
			$p2 = $this->isPrimaryKey(array_values($relation->link));
			if ($p1 && $p2 || $p2) {
				foreach ($relation->link as $a => $b) {
					$model->$a = null;
				}
				$delete ? $model->delete() : $model->save(false);
			} elseif ($p1) {
				foreach ($relation->link as $b) {
					$this->$b = null;
				}
				$delete ? $this->delete() : $this->save(false);
			} else {
				throw new InvalidCallException('Unable to unlink models: the link does not involve any primary key.');
			}
		}

		if (!$relation->multiple) {
			unset($this->_related[$name]);
		} elseif (isset($this->_related[$name])) {
			/** @var ActiveRecord $b */
			foreach ($this->_related[$name] as $a => $b) {
				if ($model->getPrimaryKey() == $b->getPrimaryKey()) {
					unset($this->_related[$name][$a]);
				}
			}
		}
	}

	/**
	 * @param array $link
	 * @param ActiveRecord $foreignModel
	 * @param ActiveRecord $primaryModel
	 * @throws InvalidCallException
	 */
	private function bindModels($link, $foreignModel, $primaryModel)
	{
		foreach ($link as $fk => $pk) {
			$value = $primaryModel->$pk;
			if ($value === null) {
				throw new InvalidCallException('Unable to link models: the primary key of ' . get_class($primaryModel) . ' is null.');
			}
			$foreignModel->$fk = $value;
		}
		$foreignModel->save(false);
	}

	/**
	 * Returns a value indicating whether the given set of attributes represents the primary key for this model
	 * @param array $keys the set of attributes to check
	 * @return boolean whether the given set of attributes represents the primary key for this model
	 */
	public static function isPrimaryKey($keys)
	{
		$pks = static::primaryKey();
		if (count($keys) === count($pks)) {
			return count(array_intersect($keys, $pks)) === count($pks);
		} else {
			return false;
		}
	}
}
