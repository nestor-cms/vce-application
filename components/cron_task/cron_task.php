<?php

class CronTask extends Component {

    /**
     * basic info about the component
     */
    public function component_info() {
        return array(
            'name' => 'CronTask',
            'description' => 'Add cron task functions to VCE',
            'category' => 'utilities',
        );
    }

    /**
     * things to do when this component is preloaded
     */
    public function preload_component() {

        $content_hook = array(
            'vce_call_add_functions' => 'VCEUtilities::vce_call_add_functions',
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
         * update a current cron_task
         * @param $id // site_meta table row id
         * @param $timestamp // new timestamp to set
         * @param $properties // key=>value array to update
         *
         * $attributes = json_encode(
         * array (
         * 'component' => *component_name*,
         * 'method' => *component_function_name*,
         * 'properties' => array ('key' => 'value', 'key' => 'value')
         * )
         * );
         */
        $vce->manage_cron_task = function ($attributes) {

            // check for a action
            if (isset($attributes['action'])) {
                // move a single attribute to the first array element
                $attributes = array('0' => $attributes);
            }

            // check that attributes is not empty
            if (!empty($attributes)) {

                global $vce;

                foreach ($attributes as $key => $each) {
                    // add a cron_task
                    if ($each['action'] == "add") {

                        $data = array(
                            'meta_key' => 'cron_task',
                            'meta_value' => $each['value'],
                            'minutia' => $each['timestamp'],
                        );

                        $cron_task_id = $vce->db->insert('site_meta', $data);

                        return $cron_task_id;

                    }
                    // update a cron_task
                    if ($each['action'] == "update") {

                        // update timestamp for cron_task
                        $update = array('minutia' => $each['timestamp']);

                        if (isset($each['value'])) {
                            $update['meta_value'] = $each['value'];
                        }

                        $update_where = array('id' => $each['id']);
                        $vce->db->update('site_meta', $update, $update_where);

                        return true;

                    }
                    // delete a cron_task
                    if ($each['action'] == "delete" && isset($each['id'])) {

                        // delete cron_task
                        $where = array('id' => $each['id']);
                        $vce->db->delete('site_meta', $where);

                        return true;

                    }

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