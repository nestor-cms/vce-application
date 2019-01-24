<?php

/**
 * DataSet
 *
 * @category   Util
 * @package    AWS
 * @author     Andy Sodt <asodt@uw.edu>
 * @copyright  2018 University of Washington
 * @license    https://opensource.org/licenses/MIT  MIT License
 * @version    git: $Id$
 */

class DataSet {

    use NestedSet;

    public static $table = TABLE_PREFIX . 'datasets';
    public $id = 0;

    public static function load_all($name, $vce) {
		return self::make_tree(self::full_tree(self::$table, ['id', 'name', 'lft', 'rgt'], 'name', '"' . $name . '"', $vce));
	}

	public static function load_leaves($vce) {
		return self::make_tree(self::leaves(self::$table, ['id', 'name', 'lft', 'rgt'], 'name', '"' . $name . '"', $vce));
	}

	private static function make_tree($results) {
		$datasets = [];
        while ($row = $results->fetch_assoc()) {
			$type = $row['type'];
			$dataset = $type ? new $type() : new DataSet();
            foreach ($row as $col => $val) {
                $dataset->{$col} = $val;
            }
			$datasets[$dataset->id] = $dataset;
		}
		
		return $datasets;
	}

}