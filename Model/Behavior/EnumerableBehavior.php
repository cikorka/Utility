<?php
/**
 * EnumerableBehavior
 *
 * A CakePHP Behavior that emulates enumerable fields within the model. Each model that contains an enum field
 * (a field of multiple designated values), should define an $enum map and associated constants.
 *
 * After every query, any field within the $enum map will be replaced by the respective value (example: a status
 * of 0 will be replaced with PENDING). This allows for easy readability for clients and easy usability,
 * flexibility and portability for developers.
 *
 * {{{
 *		class User extends AppModel {
 * 			const PENDING = 0;
 * 			const ACTIVE = 1;
 * 			const INACTIVE = 2;
 *
 *			public $actsAs = array('Utility.Enumerable');
 *
 * 			public $enum = array(
 *				'status' => array(
 *					self::PENDING => 'PENDING',
 * 					self::ACTIVE => 'ACTIVE',
 * 					self::INACTIVE => 'INACTIVE
 *				)
 * 			);
 *		}
 *
 * 		// Return the enum for the status field
 * 		$user->enum('status');
 *
 * 		// Find all users by status
 * 		$user->findByStatus(User::PENDING);
 * }}}
 *
 * @author		Miles Johnson - http://milesj.me
 * @copyright	Copyright 2006+, Miles Johnson, Inc.
 * @license		http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link		http://milesj.me/code/cakephp/utility
 */

App::uses('ModelBehavior', 'Model');

class EnumerableBehavior extends ModelBehavior {

	/**
	 * Default settings.
	 *
	 * 	persistValue - Persist the raw value in the response by appending a new field named <field>_enum
	 * 	formatOnUpdate - Toggle the replacing of raw values with enum values when a record is being updated (checks Model::$id)
	 *
	 * @var array
	 */
	public $settings = array(
		'persistValue' => true,
		'formatOnUpdate' => false
	);

	/**
	 * The enum from the model.
	 *
	 * @var array
	 */
	public $enum = array();

	/**
	 * Store the settings and Model::$enum.
	 *
	 * @param Model $model
	 * @param array $settings
	 * @throws Exception
	 */
	public function setup(Model $model, $settings = array()) {
		if (!isset($model->enum)) {
			throw new Exception(sprintf('%s::$enum does not exist', $model->alias));
		}

		$enum = $model->enum;
		$parent = $model;

		// Grab the parent enum and merge
		while ($parent = get_parent_class($parent)) {
			$props = get_class_vars($parent);

			if (isset($props['enum'])) {
				$enum = $enum + $props['enum'];
			}
		}

		$this->enum = $enum;
		$this->settings = $settings + $this->settings;
	}

	/**
	 * Helper method for grabbing and filtering the enum from the model.
	 *
	 * @param Model $model
	 * @param string|null $key
	 * @param string|null $value
	 * @return array
	 * @throws Exception
	 */
	public function enum(Model $model, $key = null, $value = null) {
		$enum = $this->enum;

		if ($key) {
			if (!isset($enum[$key])) {
				throw new Exception(sprintf('Field %s does not exist within %s::$enum', $key, $model->alias));
			}

			if ($value) {
				return isset($enum[$key][$value]) ? $enum[$key][$value] : null;
			} else {
				return $enum[$key];
			}
		}

		return $enum;
	}

	/**
	 * Generate select options based on the enum fields which will be used for form input auto-magic.
	 * If a Controller is passed, it will auto-set the data to the views.
	 *
	 * @param Model $model
	 * @param Controller|null $controller
	 * @return array
	 */
	public function generateOptions(Model $model, Controller $controller = null) {
		$enum = array();

		foreach ($this->enum as $key => $values) {
			$var = Inflector::variable(Inflector::pluralize(preg_replace('/_id$/', '', $key)));

			if ($controller) {
				$controller->set($var, $values);
			}

			$enum[$var] = $values;
		}

		return $enum;
	}

	/**
	 * Format the results by replacing all enum fields with their respective value replacement.
	 *
	 * @param Model $model
	 * @param array $results
	 * @param boolean $primary
	 * @return mixed
	 */
	public function afterFind(Model $model, $results, $primary) {
		if (!empty($model->id) && !$this->settings['formatOnUpdate']) {
			return $results;
		}

		if (!empty($results)) {
			$enum = $this->enum;
			$alias = $model->alias;
			$settings = $this->settings;
			$isMulti = true;

			if (!isset($results[0])) {
				$results = array($results);
				$isMulti = false;
			}

			foreach ($results as &$result) {
				foreach ($enum as $key => $values) {
					if (isset($result[$alias][$key])) {
						$value = $result[$alias][$key];

						// Persist integer value
						if ($settings['persistValue']) {
							$result[$alias][$key . '_enum'] = $value;
						}

						$result[$alias][$key] = $this->enum($model, $key, $value);
					}
				}
			}

			if (!$isMulti) {
				$results = $results[0];
			}
		}

		return $results;
	}

}