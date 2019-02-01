<?php

class Datalist extends Component {

    /**
     * basic info about the component
     */
    public function component_info() {
        return array(
            'name' => 'Datalist',
            'description' => 'Add datalist functions to VCE',
            'category' => 'utilities',
        );
    }

    /**
     * things to do when this component is preloaded
     */
    public function preload_component() {

        $content_hook = array(
            'vce_call_add_functions' => 'Datalist::vce_call_add_functions',
        );

        return $content_hook;

    }

    /**
     * add cron task functions to VCE
     *
     * @param [VCE] $vce
     */
    public static function vce_call_add_functions($vce) {

        /**
         * Creates a datalist
         *
         * @internal $attributes = array (
         * 'parent_id' => '1' ,
         * 'item_id' => '1' ,
         * 'component_id' => '1' ,
         * 'user_id' => '1' ,
         * 'sequence' => '1',
         * 'datalist' => 'test_datalist',
         * 'aspects' = > array ('key' => 'value', 'key' => 'value'),
         * 'hierarchy' => array ('value', 'value'),
         * 'items' => array ('key' => 'value', 'key' => 'value')
         * );
         * $vce->create_datalist($attributes);
         * @param array $attributes
         * @global object $db
         * @return int $datalist_id
         */
        $vce->create_datalist = function ($attributes) use ($vce) {

            // todo: add a flag that would be checked to make sure we don't create a duplicate

            // create a record in datalist
            $parent_id = isset($attributes['parent_id']) ? $attributes['parent_id'] : null;
            $item_id = isset($attributes['item_id']) ? $attributes['item_id'] : null;
            $component_id = isset($attributes['component_id']) ? $attributes['component_id'] : null;
            $user_id = isset($attributes['user_id']) ? $attributes['user_id'] : null;
            $sequence = isset($attributes['sequence']) ? $attributes['sequence'] : 0;

            $records = array(
                'parent_id' => $parent_id,
                'item_id' => $item_id,
                'component_id' => $component_id,
                'user_id' => $user_id,
                'sequence' => 0,
            );

            $new_datalist_id = $vce->db->insert('datalists', $records);

            // create aspects array if it doen't already exist
            $aspects = isset($attributes['aspects']) ? $attributes['aspects'] : array();

            // hierarchy is set
            if (isset($attributes['hierarchy'])) {

                $hierarchy = $attributes['hierarchy'];

                $aspects['name'] = isset($hierarchy[0]) ? $hierarchy[0] : 'unknown';

                if (count($hierarchy) > 1) {
                    // remove this level
                    array_shift($hierarchy);
                    // set hierarchy to add to meta_data
                    $aspects['hierarchy'] = json_encode($hierarchy);
                }

            }

            // add datalist title to aspects which are saved in datalists_meta
            $aspects['datalist'] = isset($attributes['datalist']) ? $attributes['datalist'] : 'no_name';

            // cycle through array and add each to this
            foreach ($aspects as $aspect_key => $aspect_value) {
                $aspects_records[] = array(
                    'datalist_id' => $new_datalist_id,
                    'meta_key' => $aspect_key,
                    'meta_value' => $aspect_value,
                    'minutia' => null,
                );
            }

            $vce->db->insert('datalists_meta', $aspects_records);

            // if items need to be created
            if (isset($attributes['items'])) {
                // pass datalist_id that was created and items
                $vce->insert_datalist_items(array('datalist_id' => $new_datalist_id, 'items' => $attributes['items']));
            }

            // return the id for the datalist
            return $new_datalist_id;
        };

        /**
         * Cycles though items in attributes and call to add_datalist_item function to add each item.
         * @example
         * $attributes = array (
         * 'datalist_id' => '1',
         * 'items' => array ( array ('key' => 'value', 'key' => 'value' ) )
         * );
         *
         * $vce->insert_datalist_items($attributes);
         * @param array $attributes
         * @return inserts items into datalist
         */
        $vce->insert_datalist_items = function ($attributes) use ($vce) {
            
            foreach ($attributes['items'] as $sequence => $each_item) {

                $input = array();

                // datalist_id
                $input['datalist_id'] = $attributes['datalist_id'];

                // sequence
                $input['sequence'] = ($sequence + 1);

                // meta data at current level
                $this_item = $each_item;
                unset($this_item['items']);

                foreach ($this_item as $key => $value) {

                    $input[$key] = $value;

                }

                // call to function to add the datalist item
                $new_datalist_id = $vce->add_datalist_item($input);

                if (isset($each_item['items'])) {

                    // make a copy and then change datalsit_id and items
                    $this_attributes = $attributes;
                    $this_attributes['datalist_id'] = $new_datalist_id;
                    $this_attributes['items'] = $each_item['items'];

                    $vce->insert_datalist_items($this_attributes);

                }
            }
        };

        /**
         * Adds item to datalist
         * Called by insert_datalist_items()
         * $attributes = array (
         * 'datalist_id' => '1',
         * '*key*' => '*value*
         * );
         * @param $input
         * @global object $db
         * @return int $new_datalist_id
         */
        $vce->add_datalist_item = function ($input) use ($vce) {

            // get meta_data associated with datalist_id
            $query = "SELECT meta_key, meta_value FROM " . TABLE_PREFIX . "datalists_meta WHERE datalist_id='" . $input['datalist_id'] . "'";
            $meta_data = $vce->db->get_data_object($query);

            // rekey datalist meta_data into object
            $datalist = new StdClass();
            foreach ($meta_data as $each_meta_data) {
                $key = $each_meta_data->meta_key;
                $datalist->$key = $each_meta_data->meta_value;
            }

            // get datalist_id and then unset from $input
            $datalist_id = $input['datalist_id'];
            unset($input['datalist_id']);

            // get sequence if there is one, then unset
            $sequence = isset($input['sequence']) ? $input['sequence'] : '0';
            unset($input['sequence']);

            // columns in datalists_items, without item_id
            $records = array(
                'datalist_id' => $datalist_id,
                'sequence' => $sequence,
            );

            $item_id = $vce->db->insert('datalists_items', $records);

            // add key value pairs
            foreach ($input as $key => $value) {

                $add_items_meta[] = array(
                    'item_id' => $item_id,
                    'meta_key' => $key,
                    'meta_value' => $value,
                    'minutia' => null,
                );

            }

            $vce->db->insert('datalists_items_meta', $add_items_meta);

            // hierarchy is set, so there are children
            if (isset($datalist->hierarchy)) {

                // creating an array of children
                $hierarchy = json_decode($datalist->hierarchy, true);

                // name of datalist is the child name
                $datalist->name = $hierarchy[0];

                if (count($hierarchy) > 1) {
                    // remove this level
                    array_shift($hierarchy);
                } else {
                    $hierarchy = null;
                }

                $add_lists[] = array(
                    'parent_id' => $datalist_id,
                    'item_id' => $item_id,
                    'component_id' => null,
                    'user_id' => null,
                );

                // get the id of the insert
                $new_datalist_id = $vce->db->insert('datalists', $add_lists)[0];

                unset($add_meta, $datalist->datalist, $datalist->hierarchy);

                foreach ($datalist as $key => $value) {

                    $add_meta[] = array(
                        'datalist_id' => $new_datalist_id,
                        'meta_key' => $key,
                        'meta_value' => $value,
                        'minutia' => null,
                    );

                }

                if ($hierarchy) {
                    $add_meta[] = array(
                        'datalist_id' => $new_datalist_id,
                        'meta_key' => 'hierarchy',
                        'meta_value' => json_encode($hierarchy),
                        'minutia' => null,
                    );
                }

                $vce->db->insert('datalists_meta', $add_meta);

            }

            // return new datalist_id if it exists
            return isset($new_datalist_id) ? $new_datalist_id : $item_id;

        };

        /**
         * Updates datalist and associated meta_data
         * using datalist_id or item_id of datalist
         * additional meta_data can be updated using key=>value
         * @param array $attributes
         *
         * $attributes = array (
         * 'datalist_id' => '1',
         * 'item_id' => '1',
         * 'relational_data' => array('parent_id => '1', 'item_id' => '1', 'component_id' => '1', 'user_id' => '1','sequence' => '1'),
         * 'meta_data' => array ( 'key' => 'value','key' => 'value' )
         * );
         *
         * $vce->update_datalist($attributes);
         *
         * @global object $db
         * @return updates the datalist
         */
        $vce->update_datalist = function ($attributes) use ($vce) {

            // update meta_data for datalist
            if (isset($attributes['datalist_id'])) {
                $where_key = 'datalist_id';
                $where_value = $attributes['datalist_id'];
            } elseif (isset($attributes['item_id'])) {
                $where_key = 'item_id';
                $where_value = $attributes['item_id'];
            } else {
                // no identifier found
                return false;
            }

            foreach (array('parent_id', 'item_id', 'component_id', 'user_id', 'sequence') as $each_update) {
                if (isset($attributes['relational_data'][$each_update])) {
                    $update_associations[$each_update] = $attributes['relational_data'][$each_update];
                }
            }

            if (isset($update_associations)) {
                $update = $update_associations;
                $update_where = array($where_key => $where_value);
                $vce->db->update('datalists', $update, $update_where);
            }

            if (isset($attributes['meta_data'])) {
                foreach ($attributes['meta_data'] as $key => $value) {
                    $update = array('meta_value' => $value);
                    $update_where = array($where_key => $where_value, 'meta_key' => $key);
                    $vce->db->update('datalists_meta', $update, $update_where);
                }
            }

            return true;

        };

        /**
         * Updates datalist_item and associated meta_data
         * using item_id of datalist_item
         * additional meta_data can be updated using key=>value
         * @param array $attributes
         *
         * $attributes = array (
         * 'item_id' => '1',
         * 'relational_data' => array('datalist_id => '1','sequence' => '1',);
         * 'meta_data' => array ( 'key' => 'value','key' => 'value' )
         * );
         *
         * $vce->update_datalist_list($attributes);
         *
         * @global object $db
         * @return updates the datalist
         */
        $vce->update_datalist_item = function ($attributes) use ($vce) {

            if (!isset($attributes['item_id'])) {
                // no identifier found
                return false;
            }

            foreach (array('datalist_id', 'sequence') as $each_update) {
                if (isset($attributes['relational_data'][$each_update])) {
                    $update_associations[$each_update] = $attributes['relational_data'][$each_update];
                }
            }

            if (isset($update_associations)) {
                $update = $update_associations;
                $update_where = array('item_id' => $attributes['item_id']);
                $vce->db->update('datalists_items', $update, $update_where);
            }

            if (isset($attributes['meta_data'])) {
                foreach ($attributes['meta_data'] as $key => $value) {
                    $update = array('meta_value' => $value);
                    $update_where = array('item_id' => $attributes['item_id'], 'meta_key' => $key);
                    $vce->db->update('datalists_items_meta', $update, $update_where);
                }
            }

            return true;

        };

        /**
         * Removes datalist associated data
         * Removes data from datalist by: datalist, datalist_id, item_id
         * @param array $attributes
         * @global object $db
         * @return removes datalist
         */
        $vce->remove_datalist = function ($attributes) use ($vce) {

            // datalist is named, delete everything associated with that datalist including meta and items
            if (isset($attributes['datalist']) && !isset($attributes['datalist_id'])) {

                // get all datalist_id associated with the datalist
                $query = "SELECT datalist_id FROM " . TABLE_PREFIX . "datalists_meta WHERE meta_key='datalist' AND meta_value='" . $attributes['datalist'] . "'";
                $datalist_ids = $vce->db->get_data_object($query);

                // cycle through results
                foreach ($datalist_ids as $each_datalist_id) {
                    // send each datalist_id to search_and_destroy with 'all' items to be deleted
                    $vce->extirpate_datalist('all', $each_datalist_id->datalist_id);
                }

            }

            // datalist_id is given, and if no item_id is set, then delete all items assocaited with the datalist_id
            if (isset($attributes['datalist_id'])) {

                // if no item_id, then delete all items associated with this datalist_id
                $item_id = isset($attributes['item_id']) ? $attributes['item_id'] : 'all';

                $vce->extirpate_datalist($item_id, $attributes['datalist_id']);

            }

            // item_id is given, and if no datalist_id is set, then just set it to null
            if (isset($attributes['item_id'])) {

                $datalist_id = isset($attributes['datalist_id']) ? $attributes['datalist_id'] : null;

                $vce->extirpate_datalist($attributes['item_id'], $datalist_id);

            }

        };

        /**
         * Recursively deletes datalists
         * {@internal}$item_id = "all" to remove everything}
         * @param string $item_id
         * @param $datalist_id
         * @global object $db
         * @return removes datalists
         */
        $vce->extirpate_datalist = function ($item_id, $datalist_id) use ($vce) {

            // search for all item_id in datalist_items

            if ($item_id == "all") {

                // search for datalist associated with this item
                $query = "SELECT item_id FROM " . TABLE_PREFIX . "datalists_items WHERE datalist_id='" . $datalist_id . "'";
                $items = $vce->db->get_data_object($query);

                foreach ($items as $each_item) {
                    // recursive call for children
                    $vce->extirpate_datalist($each_item->item_id, $datalist_id);
                }

                // delete from datalists where datalist_id = $datalist_id
                $where = array('datalist_id' => $datalist_id);
                $vce->db->delete('datalists', $where);

                // delete rows from datalists_meta where datalist_id =  $datalist_id
                $where = array('datalist_id' => $datalist_id);
                $vce->db->delete('datalists_meta', $where);

            } else {

                // search for datalist associated with this item
                $query = "SELECT datalist_id FROM " . TABLE_PREFIX . "datalists WHERE item_id='" . $item_id . "'";
                $children = $vce->db->get_data_object($query);

                // if there is a datalist, then we have children
                if (isset($children[0]->datalist_id)) {

                    // search for datalist associated with this item
                    $query = "SELECT item_id FROM " . TABLE_PREFIX . "datalists_items WHERE datalist_id='" . $children[0]->datalist_id . "'";
                    $items = $vce->db->get_data_object($query);

                    foreach ($items as $each_item) {
                        // recursive call for children
                        $vce->extirpate_datalist($each_item->item_id, $item_id);
                    }

                    // delete from datalists where item_id = $item_id
                    $where = array('item_id' => $item_id);
                    $vce->db->delete('datalists', $where);

                    // delete rows from datalists where datalist_id = $children->datalist_id
                    $where = array('datalist_id' => $children[0]->datalist_id);
                    $vce->db->delete('datalists', $where);

                    // delete rows from datalists_meta where datalist_id = $children->datalist_id
                    $where = array('datalist_id' => $children[0]->datalist_id);
                    $vce->db->delete('datalists_meta', $where);

                }

                // delete from datalists_items where item_id = $item_id
                $where = array('item_id' => $item_id);
                $vce->db->delete('datalists_items', $where);

                // delete from datalists_items_meta where item_id = $item_id
                $where = array('item_id' => $item_id);
                $vce->db->delete('datalists_items_meta', $where);

            }

        };

        /**
         * Returns datalist meta_data from assocated components_id.
         * Can specify datalist to filter, but that is optional
         * @param array $attributes
         *
         * $attributes = array (
         * 'component_id' => *component_id*,
         * 'datalist_id' => *datalist_id*,
         * 'user_id' => *user_id*,
         * 'datalist' => '*name*',
         * 'item_id' => '*item_id*'
         * );
         *
         * @global object $db
         * @return array $our_datalists
         */
        $vce->get_datalist = function ($attributes) use ($vce) {

            global $vce;

            $component_id = isset($attributes['component_id']) ? $attributes['component_id'] : null;
            $user_id = isset($attributes['user_id']) ? $attributes['user_id'] : null;
            $datalist = isset($attributes['datalist']) ? $attributes['datalist'] : null;
            $datalist_id = isset($attributes['datalist_id']) ? $attributes['datalist_id'] : null;
            $item_id = isset($attributes['item_id']) ? $attributes['item_id'] : null;

            // the first part of the query remains the same
            $query = "SELECT " . TABLE_PREFIX . "datalists.*," . TABLE_PREFIX . "datalists_meta.* FROM " . TABLE_PREFIX . "datalists JOIN " . TABLE_PREFIX . "datalists_meta ON " . TABLE_PREFIX . "datalists_meta.datalist_id=" . TABLE_PREFIX . "datalists.datalist_id ";

            if (isset($component_id)) {
                $query .= "WHERE " . TABLE_PREFIX . "datalists.component_id='" . $component_id . "'";
            } elseif (isset($datalist_id)) {
                $query .= "WHERE " . TABLE_PREFIX . "datalists.datalist_id='" . $datalist_id . "'";
            } elseif (isset($user_id) && isset($datalist)) {
                $query .= "WHERE " . TABLE_PREFIX . "datalists.user_id='" . $user_id . "' AND " . TABLE_PREFIX . "datalists.datalist_id IN (SELECT datalist_id FROM " . TABLE_PREFIX . "datalists_meta WHERE " . TABLE_PREFIX . "datalists_meta.meta_key='datalist' AND " . TABLE_PREFIX . "datalists_meta.meta_value='" . $datalist . "')";
            } elseif (isset($datalist)) {
                $query .= "WHERE " . TABLE_PREFIX . "datalists.datalist_id IN (SELECT datalist_id FROM " . TABLE_PREFIX . "datalists_meta WHERE " . TABLE_PREFIX . "datalists_meta.meta_key='datalist' AND " . TABLE_PREFIX . "datalists_meta.meta_value='" . $datalist . "')";
            } elseif (isset($user_id)) {
                $query .= "WHERE " . TABLE_PREFIX . "datalists.user_id='" . $user_id . "'";
            } elseif (isset($item_id)) {
                // if we are looking for the datalist_id associated with a specific item_id contained within that datalist, we would use a sub query, but this is not what we are trying to do here
                // $query .= "WHERE " . TABLE_PREFIX . "datalists.datalist_id IN (SELECT datalist_id FROM " . TABLE_PREFIX . "datalists_items WHERE " . TABLE_PREFIX . "datalists_items.item_id='" . $item_id . "')";
                // note: with the get_datalist method, we are looking specifically for an item_id in the datalists table that is assocaited with an item_id
                $query .= "WHERE " . TABLE_PREFIX . "datalists.item_id='" . $item_id . "'";
            } else {
                // nothing matches so return false
                return false;
            }

            // and the last part of the query remains the same
            $query .= " ORDER BY " . TABLE_PREFIX . "datalists.sequence ASC";

            // call to database
            $all_datalists = $vce->db->get_data_object($query);

            $our_datalists = array();
            $not_requested = array();

            foreach ($all_datalists as $each_datalist) {

                // add these the first time only
                if (!isset($our_datalists[$each_datalist->datalist_id]['datalist_id'])) {
                    $our_datalists[$each_datalist->datalist_id]['datalist_id'] = $each_datalist->datalist_id;
                    $our_datalists[$each_datalist->datalist_id]['parent_id'] = $each_datalist->parent_id;
                    $our_datalists[$each_datalist->datalist_id]['item_id'] = $each_datalist->item_id;
                    $our_datalists[$each_datalist->datalist_id]['component_id'] = $each_datalist->component_id;
                    $our_datalists[$each_datalist->datalist_id]['user_id'] = $each_datalist->user_id;
                    $our_datalists[$each_datalist->datalist_id]['sequence'] = $each_datalist->sequence;
                }

                // add key and value for meta_data
                $our_datalists[$each_datalist->datalist_id][$each_datalist->meta_key] = $each_datalist->meta_value;

                // store datalist_id for non matches if filtering has been requested.
                if (isset($datalist) && isset($our_datalists[$each_datalist->datalist_id]['datalist']) && $our_datalists[$each_datalist->datalist_id]['datalist'] != $datalist) {
                    $not_requested[] = $each_datalist->datalist_id;
                }

            }

            // filter out any non-requesterd datalist
            if (isset($datalist)) {
                foreach ($not_requested as $each_not_requested) {
                    // remove item from array
                    unset($our_datalists[$each_not_requested]);
                }
            }

            // return datalists array
            return $our_datalists;

        };

        /**
         * Gets meta_data from datalist items_id.
         * @return array of meta_data associated with items_id of datalist
         *
         * $attributes = array (
         * 'user_id' => *user_id*,
         * 'datalist' => '*name*',
         * 'datalist_id' => *datalist_id*
         * );
         *
         * $vce->get_datalist_items($attributes)
         *
         * @param array $attributes
         * @global object $db
         * @return array $options
         */
        $vce->get_datalist_items = function ($attributes) use ($vce) {

            // options to search by
            if (isset($attributes['datalist_id'])) {
                $query = "SELECT * FROM " . TABLE_PREFIX . "datalists WHERE datalist_id='" . $attributes['datalist_id'] . "'";
            } elseif (isset($attributes['name'])) {
                $query = "SELECT * FROM " . TABLE_PREFIX . "datalists_meta WHERE meta_key='name' AND meta_value='" . $attributes['name'] . "'";
            } elseif (isset($attributes['parent_id']) && isset($attributes['item_id'])) {
                $query = "SELECT " . TABLE_PREFIX . "datalists.* FROM " . TABLE_PREFIX . "datalists WHERE parent_id='" . $attributes['parent_id'] . "' AND item_id='" . $attributes['item_id'] . "'";
            } elseif (isset($attributes['user_id']) && isset($attributes['datalist'])) {
                $query = "SELECT " . TABLE_PREFIX . "datalists.* FROM " . TABLE_PREFIX . "datalists JOIN  " . TABLE_PREFIX . "datalists_meta ON " . TABLE_PREFIX . "datalists_meta.datalist_id=" . TABLE_PREFIX . "datalists.datalist_id WHERE " . TABLE_PREFIX . "datalists.user_id='" . $attributes['user_id'] . "' AND " . TABLE_PREFIX . "datalists_meta.meta_key='datalist' AND " . TABLE_PREFIX . "datalists_meta.meta_value='" . $attributes['datalist'] . "'";
            } elseif (isset($attributes['datalist'])) {
                $query = "SELECT * FROM " . TABLE_PREFIX . "datalists WHERE datalist_id IN (SELECT datalist_id FROM " . TABLE_PREFIX . "datalists_meta WHERE meta_key='datalist' AND meta_value='" . $attributes['datalist'] . "')";
            } elseif (isset($attributes['user_id'])) {
                $query = "SELECT " . TABLE_PREFIX . "datalists.* FROM " . TABLE_PREFIX . "datalists WHERE " . TABLE_PREFIX . "datalists.user_id='" . $attributes['user_id'] . "'";
            } elseif (isset($attributes['component_id'])) {
                $query = "SELECT " . TABLE_PREFIX . "datalists.* FROM " . TABLE_PREFIX . "datalists WHERE " . TABLE_PREFIX . "datalists.component_id='" . $attributes['component_id'] . "'";
            }

            // a query has been set
            if (isset($query)) {
                // database call
                $datalist_results = $vce->db->get_data_object($query);

                if (!empty($datalist_results)) {
                    $datalist_info = $datalist_results[0];
                } else {
                    return false;
                }
            }

            if (isset($datalist_results)) {

                // create options meta_data array
                $options_list = array();

                foreach ($datalist_info as $datalist_info_key => $datalist_info_value) {
                    $options[$datalist_info_key] = $datalist_info_value;
                }

                // get meta_data associated with item
                $query = "SELECT * FROM " . TABLE_PREFIX . "datalists_items INNER JOIN " . TABLE_PREFIX . "datalists_items_meta ON  " . TABLE_PREFIX . "datalists_items.item_id = " . TABLE_PREFIX . "datalists_items_meta.item_id WHERE " . TABLE_PREFIX . "datalists_items.datalist_id='" . $datalist_info->datalist_id . "' ORDER BY " . TABLE_PREFIX . "datalists_items.sequence";

            } else {

                if (isset($attributes['item_id'])) {
                    $query = "SELECT * FROM " . TABLE_PREFIX . "datalists_items JOIN " . TABLE_PREFIX . "datalists_items_meta ON  " . TABLE_PREFIX . "datalists_items.item_id = " . TABLE_PREFIX . "datalists_items_meta.item_id WHERE " . TABLE_PREFIX . "datalists_items.item_id='" . $attributes['item_id'] . "' ORDER BY " . TABLE_PREFIX . "datalists_items.sequence";
                }

            }

            if (isset($query)) {
                //make database call
                $meta_data = $vce->db->get_data_object($query);

                if (!empty($meta_data)) {
                    // add each key => value pair
                    foreach ($meta_data as $each_meta_data) {
                        $options_list[$each_meta_data->item_id]['item_id'] = $each_meta_data->item_id;
                        $options_list[$each_meta_data->item_id][$each_meta_data->meta_key] = $each_meta_data->meta_value;
                        $options_list[$each_meta_data->item_id]['sequence'] = $each_meta_data->sequence;
                    }

                    // add to options
                    $options['items'] = $options_list;

                    return $options;

                } else {

                    // add to options items as an empty array
                    $options['items'] = array();

                    return $options;

                }
            }

            return false;

        };

    }

    /**
     * hide this component from being added to a recipe
     */
    public function recipe_fields($recipe) {
        return false;
    }

}