<?php

/* NOT CURRENTLY USED */

/**
 * ComponentTree trait
 *
 * @category   Util
 * @package    AWS
 * @author     Andy Sodt <asodt@uw.edu>
 * @copyright  2018 University of Washington
 * @license    https://opensource.org/licenses/MIT  MIT License
 * @version    git: $Id$
 */

/**
 * ComponentTree trait.
 */
trait ComponentTree {

	/**
	 * Build a component tree with a single line going back to the parents of the component and
	 * a full tree of children
	 *
	 * @return the filled out component
	 */
    private function build_tree($component_id, $vce) {

        // Get list of all component ids in the tree.  This creates a straign line of parents to the root and the full tree
        // below the node identified by $component_id
        $result = $vce->db->get_data_object("SELECT lineage FROM " . TABLE_PREFIX . "components c WHERE c.component_id = " . $component_id);
        $ids = explode('|', ltrim($result[0]->lineage, '|'));
        $id_query = "SELECT DISTINCT component_id FROM " . TABLE_PREFIX . "components WHERE lineage like '" . $result[0]->lineage . "|%'";
        $id_result = $vce->db->get_data_object($id_query);
        foreach ($id_result as $id) {
            array_push($ids, $id->component_id);
        }

        $components = $this->load_component_tree($component_id, $ids, $vce);

        return $components;
    }

	/**
	 * Load a component tree including meta data.  Set parents and children
	 *
	 * @param [type] $component_id
	 * @return component map
	 */
    private function load_component_tree($requested_id, $component_ids, $vce) {

        // First get all the object types and store in a map with their component_ids
        $id_string = implode(',', $component_ids);
        $type_query = "SELECT DISTINCT m.component_id, m.meta_value AS 'type' FROM " . TABLE_PREFIX . "components_meta m WHERE m.meta_key='Type' and m.component_id in (" . $id_string . ')';
        $types = $vce->db->get_data_object($type_query);
        $type_map = [];
        foreach ($types as $type) {
            $type_map[$type->component_id] = $type->type;
        }

        // Now get all the components based on their ids.
        $component_query = "SELECT c.* FROM " . TABLE_PREFIX . "components c WHERE c.component_id in (" . $id_string . ")";
        $component_result = $vce->db->query($component_query);

        // Get metadata and put into a map of arrays based on component_ids
        $meta_query = "SELECT m.* FROM " . TABLE_PREFIX . "components_meta m WHERE m.component_id in (" . $id_string . ")";
        $meta_result = $vce->db->get_data_object($meta_query);
        $metas = [];
        foreach ($meta_result as $array_key => $each_metadata) {
            if (!$metas[$each_metadata->component_id]) {
                $metas[$each_metadata->component_id] = [];
            }
            array_push($metas[$each_metadata->component_id], $each_metadata);
        }

        // Create each component using the matching object type and load with matching meta data
        $components = [];
        while ($row = $component_result->fetch_assoc()) {

            // Instantiate component based on object type
            $type = $type_map[$row['component_id']];
            $component = new $type();
            foreach ($row as $col => $val) {
                $component->{$col} = $val;
            }
            $component->type = $type;

            // Load up metadata based on component_id
            foreach ($metas[$component->component_id] as $each_metadata) {

                // add title from requested id to page object base
                if ($component->component_id == $requested_id && $each_metadata->meta_key == "title") {
                    $this->title = $each_metadata->meta_value;
                }

                // if a template has been assigned to this component, add it to object
                if (!isset($this->template) && $each_metadata->meta_key == "template") {
                    // check that template file exists
                    if (is_file(BASEPATH . 'vce-content/themes/' . $vce->site->site_theme . '/' . $each_metadata->meta_value)) {
                        $this->template = $each_metadata->meta_value;
                    }
                }

                // get recipe and add to base of object
                if ($each_metadata->meta_key == "recipe") {

                    // decode json object of recipe
                    $recipe = json_decode($each_metadata->meta_value, true)['recipe'];

                    // load hooks
                    if (isset($vce->site->hooks['page_add_recipe'])) {
                        foreach ($vce->site->hooks['page_add_recipe'] as $hook) {
                            $recipe = call_user_func($hook, $this->recipe, $recipe);
                        }
                    }

                    // set recipe property of page object
                    $this->recipe = $recipe;
                    continue;
                }

                $component->{$each_metadata->meta_key} = $each_metadata->meta_value;

                // create minutia array element - this is not being used much
                if (!empty($each_metadata->minutia)) {
                    $component->{$each_metadata->meta_key . "_minutia"} = $each_metadata->minutia;
                }
            }

            // Add component to component map
            $components[$component->component_id] = $component;

            // clean up component url
            if (empty($component->url)) {
                unset($component->url);
            }

            // TODO: put hooks in correct place.
            // load hooks
            if (isset($vce->site->hooks['page_requested_components'])) {
                foreach ($vce->site->hooks['page_requested_components'] as $hook) {
                    $component = call_user_func($hook, $component, func_get_args());
                }
            }

            // load hooks
            if (isset($vce->site->hooks['page_get_components'])) {
                foreach ($vce->site->hooks['page_get_components'] as $hook) {
                    call_user_func($hook, $component, $components, $vce);
                }
            }

        }

        // set up children.  Children of a component are stored in 'components' array
        $add_children = false;
        foreach ($components as $component) {

            if ($add_children) {
                if (!isset($components[$component->parent_id]->components)) {
                    $components[$component->parent_id]->components = [];
                }
                array_push($components[$component->parent_id]->components, $component);
            }

            // we only add children to requested component and below
            if ($requested_id == $component->component_id) {
                $add_children = true; // start adding children
            }

        }

        // Remove component sub tree starting with $node
        $remove_sub_tree = function ($node, $components) use (&$remove_sub_tree) {
            if (isset($node->components)) {
                foreach ($node->components as $child) {
                    $components = $remove_sub_tree($child, $components);
                }
            }
            unset($components[$node->component_id]);
            return $components;
        };

        // Prune tree of non-displayed components
        $prune_tree = function ($node, $components) use (&$prune_tree, &$remove_sub_tree, &$vce) {
            if (isset($node->components)) {
                if ($node->find_sub_components($node, $vce, $components, $node->components)) {
                    foreach ($node->components as $child) {
                        $components = $prune_tree($child, $components);
                    }
                } else {
                    foreach ($node->components as $child) {
                        $components = $remove_sub_tree($child, $components);
                    }
                }

            }
            return $components;
        };

        $components = $prune_tree($components[$requested_id], $components);

        return $components;
    }
}
