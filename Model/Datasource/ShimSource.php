<?php
/**
 * ShimSource
 *
 * A Model DataSource that does nothing and is used to trick the model layer for specific functionality.
 * Is used by the CacheableBehavior.
 *
 * @author		Miles Johnson - http://milesj.me
 * @copyright	Copyright 2012+, Miles Johnson, Inc.
 * @license		http://opensource.org/licenses/mit-license.php - Licensed under The MIT License
 * @link		http://milesj.me/code/cakephp/utility
 */

App::uses('DataSource', 'Model/Datasource');

class ShimSource extends DataSource {

	/**
	 * Return the Model schema.
	 *
	 * @access public
	 * @param Model $model
	 * @return array
	 */
	public function describe(Model $model) {
		return $model->schema();
	}

	/**
	 * Return $data else the query will fail.
	 *
	 * @access public
	 * @param mixed $data
	 * @return array|null
	 */
	public function listSources($data = null) {
		return $data;
	}

}