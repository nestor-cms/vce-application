<?php

/**
 * NestedSet trait
 *
 * @category   Util
 * @package    AWS
 * @author     Andy Sodt <asodt@uw.edu>
 * @copyright  2018 University of Washington
 * @license    https://opensource.org/licenses/MIT  MIT License
 * @version    git: $Id$
 */

/**
 * NestedSet trait.
 */
trait NestedSet {

    /**
     * Return full sub tree from root
     *
     * example:
     *
     *     use NestedSet
     *
     *     $results = self::full_tree('components', ['component_id', 'url'], 'component_id', $requested_component->component_id, $vce);
     *  while ($row = $results->fetch_assoc()) {
     *         ...
     *     }
     *
     * @param [string] $table
     * @param [array of strings] $select_fields
     * @param [string] $root_field
     * @param [string] $root_value
     * @param [VCE] $vce
     * @return db results
     */
    public static function full_tree($table, $select_fields, $root_field, $root_value, $vce) {
        $select = self::select('node', $table, $select_fields);
        $query = $select . ' AS node, ' . $table . ' AS parent WHERE node.lft BETWEEN parent.lft AND parent.rgt AND parent.' . $root_field . ' = ' . $root_value . self::order();
        return $vce->db->query($query);
    }

    public static function leaves($table, $select_fields, $root_field, $root_value, $vce) {
        $select = self::select($table, $table, $select_fields);
        $query = $select . ' WHERE ' . $table . '.rgt = ' . $table . '.lft +1';
        return $vce->db->query($query);
    }

    private static function select($table_alias, $table, $select_fields) {
        $select = [];
        foreach ($select_fields as $field) {
            array_push($select, $table_alias . '.' . $field);
        }
        return 'SELECT ' . implode(',', $select) . ' FROM ' . $table . " ";
    }

    private static function order() {
        ' ORDER BY node.lft ';
    }
}