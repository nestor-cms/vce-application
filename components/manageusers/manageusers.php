<?php

class ManageUsers extends Component {

    /**
     * basic info about the component
     */
    public function component_info() {
        return array(
            'name' => 'Manage Users',
            'description' => 'Add, edit and delete site users. You can also masquerade as them using this component.',
            'category' => 'admin',
            'permissions' => array(
                array(
                    'name' => 'create_users',
                    'description' => 'Role can create new users',
                ),
                array(
                    'name' => 'edit_users',
                    'description' => 'Role can delete users',
                ),
                array(
                    'name' => 'delete_users',
                    'description' => 'Role can delete users',
                ),
                array(
                    'name' => 'masquerade_users',
                    'description' => 'Role can masquerade as users',
                ),
            ),
        );
    }

    /**
     *
     */
    public function as_content($each_component, $vce) {

        // add javascript to page
        $vce->site->add_script(dirname(__FILE__) . '/js/script.js');

        $vce->site->add_style(dirname(__FILE__) . '/css/style.css');

        // minimal user attributers
        $default_attributes = array(
            'user_id' => array(
                'title' => 'User Id',
                'sortable' => 1,
            ),
            'role_id' => array(
                'title' => 'Role Id',
                'sortable' => 1,
            ),
            'email' => array(
                'title' => 'Email',
                'required' => 1,
                'type' => 'text',
                'sortable' => 1,
            ),
        );

        $user_attributes = json_decode($vce->site->user_attributes, true);

        $attributes = array_merge($default_attributes, $user_attributes);

        $filter_by = array();

        foreach ($vce as $key => $value) {
            if (strpos($key, 'filter_by_') !== FALSE) {
                $filter_by[str_replace('filter_by_', '', $key)] = $value;
            }
        }

        // manage_users_attributes_filter_by
        if (isset($vce->site->hooks['manage_users_attributes_filter_by'])) {
            foreach ($vce->site->hooks['manage_users_attributes_filter_by'] as $hook) {
                $filter_by = call_user_func($hook, $filter_by, $vce);
            }
        }

        // check if edit_user is within the page object, which means we want to edit this user
        $edit_user = isset($vce->edit_user) ? $vce->edit_user : null;

        // get roles
        $roles = json_decode($vce->site->roles, true);

        // get roles in hierarchical order
        $roles_hierarchical = json_decode($vce->site->site_roles, true);

        // create var for content
        $content = null;

        // variables
        $sort_by = isset($vce->sort_by) ? $vce->sort_by : 'email';
        $sort_direction = isset($vce->sort_direction) ? $vce->sort_direction : 'ASC';
        $display_users = true;
        $pagination = true;
        $pagination_current = isset($vce->pagination_current) ? $vce->pagination_current : 1;
        $pagination_length = isset($vce->pagination_length) ? $vce->pagination_length : 100;

        // create search in values
        $role_id_in = array();
        foreach ($roles_hierarchical as $roles_each) {
            foreach ($roles_each as $key => $value) {
                if ($value['role_hierarchy'] >= $roles[$vce->user->role_id]['role_hierarchy']) {
                    // add to role array
                    $role_id_in[] = $key;
                }
            }
        }

        // get total count of users
        $query = "SELECT count(*) as count FROM " . TABLE_PREFIX . "users WHERE role_id IN (" . implode(',', $role_id_in) . ")";

        $count_data = $vce->db->get_data_object($query);
        // set variable
        $pagination_count = $count_data[0]->count;

        $number_of_pages = ceil($pagination_count / $pagination_length);

        // prevent errors if input number is bad
        if ($pagination_current > $number_of_pages) {
            $pagination_current = $number_of_pages;
        } else if ($pagination_current < 1) {
            $pagination_current = 1;
        }

        $pagination_offset = ($pagination_current != 1) ? ($pagination_length * ($pagination_current - 1)) : 0;

        // First we query the user table to get user_id and vector

        // search results
        if (isset($vce->user_search_results) && !empty($vce->user_search_results)) {

            $pagination = false;
            $sort_by = null;

            $query = "SELECT * FROM " . TABLE_PREFIX . "users WHERE user_id IN (" . implode(json_decode($vce->user_search_results, true), ',') . ")";

        } else if (isset($edit_user)) {
            // edit user
            $display_users = false;
            $sort_by = null;

            $query = "SELECT * FROM " . TABLE_PREFIX . "users WHERE user_id = '" . $edit_user . "'";

        } else {
            // towards the standard way
            // with role_id filter
            if (!empty($filter_by)) {
                $query = "SELECT * FROM " . TABLE_PREFIX . "users";
                $pagination = false;
                $sort_by = null;
            } else if ($sort_by == 'user_id' || $sort_by == 'role_id') {
                // if user_id or role_id is the sort
                $query = "SELECT * FROM " . TABLE_PREFIX . "users WHERE role_id IN (" . implode(',', $role_id_in) . ") ORDER BY $sort_by " . $sort_direction . " LIMIT " . $pagination_length . " OFFSET " . $pagination_offset;
            } else {
                // the standard way
                $query = "SELECT " . TABLE_PREFIX . "users.* FROM " . TABLE_PREFIX . "users_meta INNER JOIN " . TABLE_PREFIX . "users ON " . TABLE_PREFIX . "users_meta.user_id = " . TABLE_PREFIX . "users.user_id WHERE " . TABLE_PREFIX . "users.role_id IN (" . implode(',', $role_id_in) . ") AND " . TABLE_PREFIX . "users_meta.meta_key='" . $sort_by . "' GROUP BY " . TABLE_PREFIX . "users_meta.user_id ORDER BY " . TABLE_PREFIX . "users_meta.minutia " . $sort_direction . " LIMIT " . $pagination_length . " OFFSET " . $pagination_offset;
            }

        }

        $current_list = $vce->db->get_data_object($query);

        // rekey data into array for user_id and vectors
        foreach ($current_list as $each_list) {
            $users_list[] = $each_list->user_id;
            $users[$each_list->user_id]['user_id'] = $each_list->user_id;
            $users[$each_list->user_id]['role_id'] = $each_list->role_id;
            $users[$each_list->user_id]['role_name'] = $roles[$each_list->role_id]['role_name'];
            $vectors[$each_list->user_id] = $each_list->vector;
        }

        // Second we query the user_meta table for user_ids

        if (isset($users_list)) {

            // get meta data for the list of user_ids
            $query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE user_id IN (" . implode($users_list, ',') . ")";

        } else {

            // get all meta data for all users because of filtering
            $query = "SELECT * FROM " . TABLE_PREFIX . "users_meta";

        }

        $meta_data = $vce->db->get_data_object($query);

        // rekey data
        foreach ($meta_data as $each_meta_data) {

            // skip lookup
            if ($each_meta_data->meta_key == 'lookup') {
                continue;
            }

            // add
            $users[$each_meta_data->user_id][$each_meta_data->meta_key] = User::decryption($each_meta_data->meta_value, $vectors[$each_meta_data->user_id]);
        }

        /* start user edit */

        // we want to edit this user
        // check permissions for edit users
        if (isset($edit_user) && $vce->check_permissions('edit_users')) {

            // get user info and cast as an object
            $user = (object) $users[$edit_user];

            // create the dossier
            $dossier_for_update = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'update', 'user_id' => $edit_user));

            $content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content clickbar-open">
<form id="form" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
<label>
<div class="input-padding">
$user->email
</div>
<div class="label-text">
<div class="label-message">Email</div>
<div class="label-error">Enter your Email</div>
</div>
</label>
EOF;

            foreach ($user_attributes as $user_attributes_key => $user_attributes_value) {

                // nice title for this user attribute
                $title = isset($user_attributes_value['title']) ? $user_attributes_value['title'] : ucwords(str_replace('_', ' ', $user_attributes_key));

                // check if required
                $tag = (isset($user_attributes_value['required']) && $user_attributes_value['required'] == '1') ? 'required' : null;

                // attribute value
                $attribute_value = isset($user->$user_attributes_key) ? $user->$user_attributes_key : null;

                // if a datalist has been assigned
                if (isset($user_attributes_value['datalist'])) {

                    if (!is_array($user_attributes_value['datalist'])) {
                        $datalist_field = 'datalist';
                        $datalist_value = $user_attributes_value['datalist'];
                    } else {
                        $datalist_field = array_keys($user_attributes_value['datalist'])[0];
                        $datalist_value = $user_attributes_value['datalist'][$datalist_field];
                    }

                    $options_data = $vce->get_datalist_items(array($datalist_field => $datalist_value));

                    $options = array();

                    if (!empty($options_data)) {

                        foreach ($options_data['items'] as $option_key => $option_value) {

                            $options[$option_key] = $option_value['name'];

                        }

                    }

                }

                // if options is set
                if (isset($user_attributes_value['options'])) {
                    $options = $user_attributes_value['options'];
                }

                if (isset($user_attributes_value['type'])) {

                    // skip if conceal
                    if ($user_attributes_value['type'] == 'conceal') {
                        continue;
                    }

                    $content .= <<<EOF
<label>
EOF;

                    // if this is text
                    if ($user_attributes_value['type'] == 'text') {

                        $content .= <<<EOF
<input type="text" name="$user_attributes_key" value="$attribute_value" tag="$tag" autocomplete="off">
EOF;

                    }

                    // if this is a radio button
                    if ($user_attributes_value['type'] == 'radio' || $user_attributes_value['type'] == 'checkbox') {

                        $content .= <<<EOF
<div class="input-padding">
EOF;

                        $type = $user_attributes_value['type'];

                        $option_counter = 0;

                        foreach ($options as $option_key => $option_value) {

                            $option_counter++;
                            $input_name = $user_attributes_key;

                            // if this is a checkbox, then append with _1, _2
                            if ($user_attributes_value['type'] == 'checkbox') {
                                $input_name .= '_' . $option_counter;

                                // check if checkbox selected
                                if (in_array($option_key, json_decode($attribute_value))) {
                                    $checked = 'checked';
                                } else {
                                    $checked = '';
                                }

                            } else {

                                // check if radio selected
                                if ($option_key == $attribute_value) {
                                    $checked = 'checked';
                                } else {
                                    $checked = '';
                                }

                            }

                            $content .= <<<EOF
<label class="ignore"><input type="$type" name="$input_name" value="$option_key" tag="$tag" $checked> $option_value </label>
EOF;

                        }

                        $content .= <<<EOF
</div>
EOF;

                    }

                    // if this is text
                    if ($user_attributes_value['type'] == 'select') {

                        $content .= <<<EOF
<select name="$user_attributes_key" tag="$tag" autocomplete="off">
<option value=""></option>
EOF;

                        if (isset($options)) {
                            foreach ($options as $option_key => $option_value) {
                                $content .= '<option value="' . $option_key . '"';
                                if ($option_key == $attribute_value) {
                                    $content .= ' selected';
                                }
                                $content .= '>' . $option_value . '</option>';
                            }
                        }

                        $content .= <<<EOF
</select>
EOF;

                    }

                    $content .= <<<EOF
<div class="label-text">
<div class="label-message">$title</div>
<div class="label-error">Enter your $title</div>
</div>
</label>
EOF;

                }

            }

            // load hooks
            if (isset($vce->site->hooks['manage_users_attributes'])) {
                foreach ($vce->site->hooks['manage_users_attributes'] as $hook) {
                    $content .= call_user_func($hook, $user);
                }
            }

            $content .= <<<EOF
<label>
<select name="role_id" tag="required">
EOF;

            foreach ($roles_hierarchical as $roles_each) {
                foreach ($roles_each as $key => $value) {
                    if ($value['role_hierarchy'] >= $roles[$vce->user->role_id]['role_hierarchy']) {
                        $content .= '<option value="' . $key . '"';
                        if ($key == $user->role_id) {
                            $content .= ' selected';
                        }
                        $content .= '>' . $value['role_name'] . '</option>';
                    }
                }
            }

            $content .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Role</div>
<div class="label-error">Enter your Role</div>
</div>
</label>
<input type="submit" value="Update User">
<div class="link-button cancel-button">Cancel</div>
</form>
</div>
<div class="clickbar-title disabled"><span>Update An Existing User</span></div>
</div>
</p>
EOF;

            /* end user edit */
        } else {
            /* start of new user */

            // check permissions for create users
            if ($vce->check_permissions('create_users')) {

                // create the dossier
                $dossier_for_create = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'create'));

                $content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content">
<form id="form" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_create">

<label>
<input type="text" name="email" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Email</div>
<div class="label-error">Enter Email</div>
</div>
</label>
EOF;

                if (isset($user_attributes['password']['type']) && $user_attributes['password']['type'] == 'conceal') {

                    $password = user::generate_password();

                    $content .= <<<EOF
<input type="hidden" name="password" value="$password">
EOF;

                } else {

                    // the standard user create form with password input

                    $content .= <<<EOF
<label>
<input type="text" name="password" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Password</div>
<div class="label-error">Enter your Password</div>
</div>
</label>
EOF;

                }

                foreach ($user_attributes as $user_attributes_key => $user_attributes_value) {

                    // nice title for this user attribute
                    $title = isset($user_attributes_value['title']) ? $user_attributes_value['title'] : ucwords(str_replace('_', ' ', $user_attributes_key));

                    // check if required
                    $tag = (isset($user_attributes_value['required']) && $user_attributes_value['required'] == '1') ? 'required' : null;

                    // if a datalist has been assigned
                    if (isset($user_attributes_value['datalist'])) {

                        if (!is_array($user_attributes_value['datalist'])) {
                            $datalist_field = 'datalist';
                            $datalist_value = $user_attributes_value['datalist'];
                        } else {
                            $datalist_field = array_keys($user_attributes_value['datalist'])[0];
                            $datalist_value = $user_attributes_value['datalist'][$datalist_field];
                        }

                        $options_data = $vce->get_datalist_items(array($datalist_field => $datalist_value));

                        $options = array();

                        if (!empty($options_data)) {
                            foreach ($options_data['items'] as $option_key => $option_value) {
                                $options[$option_key] = $option_value['name'];
                            }
                        }
                    }

                    // if options is set
                    if (isset($user_attributes_value['options'])) {
                        $options = $user_attributes_value['options'];
                    }

                    if (isset($user_attributes_value['type'])) {

                        // skip if conceal
                        if ($user_attributes_value['type'] == 'conceal') {
                            continue;
                        }

                        $content .= <<<EOF
<label>
EOF;

                        // if this is text
                        if ($user_attributes_value['type'] == 'text') {

                            $content .= <<<EOF
<input type="text" name="$user_attributes_key" tag="$tag" autocomplete="off">
EOF;

                        }

                        // if this is a radio button
                        if ($user_attributes_value['type'] == 'radio' || $user_attributes_value['type'] == 'checkbox') {

                            $content .= <<<EOF
<div class="input-padding">/
EOF;

                            $type = $user_attributes_value['type'];

                            // for checkbox
                            $option_counter = 0;

                            foreach ($options as $option_key => $option_value) {

                                $option_counter++;
                                $input_name = $user_attributes_key;

                                // if this is a checkbox, then append with _1, _2
                                if ($user_attributes_value['type'] == 'checkbox') {
                                    $input_name .= '_' . $option_counter;
                                }

                                $content .= <<<EOF
<label class="ignore"><input type="$type" name="$input_name" value="$option_key"> $option_value </label> /
EOF;

                            }

                            $content .= <<<EOF
</div>
EOF;

                        }

                        // if this is text
                        if ($user_attributes_value['type'] == 'select') {

                            $content .= <<<EOF
<select name="$user_attributes_key" tag="$tag" autocomplete="off">
<option value=""></option>
EOF;

                            if (isset($user_attributes_value['datalist'])) {

                                if (isset($options)) {
                                    foreach ($options as $option_key => $option_value) {
                                        $content .= '<option value="' . $option_key . '">' . $option_value . '</option>';
                                    }
                                }

                            }

                            $content .= <<<EOF
</select>
EOF;

                        }

                        $content .= <<<EOF
<div class="label-text">
<div class="label-message">$title</div>
<div class="label-error">Enter your $title</div>
</div>
</label>
EOF;

                    }

                }

                // load hooks
                if (isset($vce->site->hooks['manage_users_attributes'])) {
                    foreach ($vce->site->hooks['manage_users_attributes'] as $hook) {
                        $content .= call_user_func($hook, $content);
                    }
                }

                $content .= <<<EOF
<label>
<select name="role_id" tag="required">
EOF;

                foreach ($roles_hierarchical as $roles_each) {
                    foreach ($roles_each as $key => $value) {
                        if ($value['role_hierarchy'] >= $roles[$vce->user->role_id]['role_hierarchy']) {
                            $content .= '<option value="' . $key . '">' . $value['role_name'] . '</option>';
                        }
                    }
                }

                $content .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Role</div>
<div class="label-error">Enter your Role</div>
</div>
</label>
<input type="submit" value="Create User">
EOF;

                if (!isset($user_attributes['password']) || !isset($user_attributes['password']['type']) || $user_attributes['password']['type'] != 'conceal') {

                    $content .= <<<EOF
<div id="generate-password" class="link-button">Generate Password</div>
EOF;

                }

                $content .= <<<EOF
<div class="link-button cancel-button">Cancel</div>
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>Create A New User</span></div>
</div>
</p>
EOF;

            }

            /* end of new user */

            /* start search */

            // dossier for search
            $dossier = array(
                'type' => 'ManageUsers',
                'procedure' => 'search',
            );

            // generate dossier
            $dossier_for_search = $vce->generate_dossier($dossier);

            $clickbar_content = isset($vce->search_value) ? 'clickbar-content clickbar-open' : 'clickbar-content';
            $clickbar_title = isset($vce->search_value) ? 'clickbar-title' : 'clickbar-title clickbar-closed';
            $input_value = isset($vce->search_value) ? $vce->search_value : null;

            $content .= <<<EOF
<div class="clickbar-container">
<div class="$clickbar_content">
EOF;

            if (isset($vce->user_search_results) && empty($vce->user_search_results)) {

                $content .= <<<EOF
<div class="form-message form-error">No Matches Found</div>
EOF;

            }

            $content .= <<<EOF
<form id="search-users" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_search">

<label>
<input type="text" name="search" value="$input_value" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Search For Users (3 Character Minimum)</div>
<div class="label-error">Searching For Someone?</div>
</div>
</label>

<input type="submit" value="Search">
<div class="link-button cancel-button">Cancel</div>
</form>

</div>
<div class="$clickbar_title"><span>Search For Users</span></div>
</div>
EOF;

            /* end search */

            /* start filtering */

            // the instructions to pass through the form
            $dossier = array(
                'type' => 'ManageUsers',
                'procedure' => 'filter',
            );

            // add dossier, which is an encrypted json object of details uses in the form
            $dossier_for_filter = $vce->generate_dossier($dossier);

            $clickbar_content = !empty($filter_by) ? 'clickbar-content clickbar-open' : 'clickbar-content';
            $clickbar_title = !empty($filter_by) ? 'clickbar-title' : 'clickbar-title clickbar-closed';

            $content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="$clickbar_content">
<label>
<select class="filter-form" name="role_id">
<option></option>
EOF;

            foreach ($roles_hierarchical as $roles_each) {
                foreach ($roles_each as $key => $value) {
                    if ($value['role_hierarchy'] >= $roles[$vce->user->role_id]['role_hierarchy']) {

                        $content .= '<option value="' . $key . '"';
                        if (isset($vce->filter_by_role_id) && $key == $vce->filter_by_role_id) {
                            $content .= ' selected';
                        }
                        $content .= '>' . $value['role_name'] . '</option>';
                    }
                }
            }

            $content .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Filter By Site Roles</div>
</div>
</label>
EOF;

            // load hooks
            if (isset($vce->site->hooks['manage_users_attributes_filter'])) {
                foreach ($vce->site->hooks['manage_users_attributes_filter'] as $hook) {
                    $content .= call_user_func($hook, $filter_by, $content, $vce);
                }
            }

            $content .= <<<EOF
<div class="filter-form-submit link-button" dossier="$dossier_for_filter" action="$vce->input_path" pagination="1">Filter</div>
<div class="link-button cancel-button">Cancel</div>
</div>
<div class="$clickbar_title"><span>Filter</span></div>
</div>
EOF;

            /* end filtering */

        }

        // check if display_users is true
        if ($display_users) {

            // the instructions to pass through the form
            $dossier = array(
                'type' => 'ManageUsers',
                'procedure' => 'pagination',
            );

            // add dossier, which is an encrypted json object of details uses in the form
            $dossier_for_pagination = $vce->generate_dossier($dossier);

            $pagination_previous = ($pagination_current > 1) ? $pagination_current - 1 : 1;
            $pagination_next = ($pagination_current < $number_of_pages) ? $pagination_current + 1 : $number_of_pages;

            $content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content no-padding clickbar-open">
EOF;

            if ($pagination) {

                $content .= <<<EOF
<div class="pagination">
<div class="pagination-controls">
<div class="pagination-button link-button" pagination="1" sort="$sort_by" direction="$sort_direction" dossier="$dossier_for_pagination" action="$vce->input_path">&#124;&#65124;</div>
<div class="pagination-button link-button" pagination="$pagination_previous" sort="$sort_by" direction="$sort_direction" dossier="$dossier_for_pagination" action="$vce->input_path">&#65124;</div>
<div class="pagination-tracker">
Page <input class="pagination-input no-label" type="text" name="pagination" value="$pagination_current" sort="$sort_by" direction="$sort_direction" dossier="$dossier_for_pagination" action="$vce->input_path"> of $number_of_pages
</div>
<div class="pagination-button link-button" pagination="$pagination_next" sort="$sort_by" direction="$sort_direction" dossier="$dossier_for_pagination" action="$vce->input_path">&#65125;</div>
<div class="pagination-button link-button" pagination="$number_of_pages" sort="$sort_by" direction="$sort_direction" dossier="$dossier_for_pagination" action="$vce->input_path">&#65125;&#124;</div>
</div>
</div>
EOF;

            }

            $content .= <<<EOF
<table class="table-style">
<thead>
<tr>
<th></th>
EOF;

            // load hooks
            if (isset($vce->site->hooks['manage_users_attributes_list'])) {
                $user_attributes_list = array();
                foreach ($vce->site->hooks['manage_users_attributes_list'] as $hook) {
                    $user_attributes_list = call_user_func($hook, $user_attributes_list);
                }
                foreach ($user_attributes_list as $each_attribute_key => $each_attribute_value) {
                    if (!is_array($each_attribute_value)) {
                        $attributes[$each_attribute_value] = array(
                            'title' => $each_attribute_value,
                            'sortable' => 1,
                        );
                    } else {
                        $attributes[$each_attribute_key] = $each_attribute_value;
                    }
                }
            }

            foreach ($attributes as $each_attribute_key => $each_attribute_value) {

                // if conceal is set, as in the case of password, skip to next
                if (isset($each_attribute_value['type']) && $each_attribute_value['type'] == 'conceal') {
                    continue;
                }

                $nice_attribute_title = ucwords(str_replace('_', ' ', $each_attribute_key));

                if ($each_attribute_key == $sort_by) {
                    if ($sort_direction == 'ASC') {
                        $sort_class = 'sort-icon sort-active sort-asc';
                        $direction = 'DESC';
                    } else {
                        $sort_class = 'sort-icon sort-active sort-desc';
                        $direction = 'ASC';
                    }
                    $th_class = 'current-sort';
                } else {
                    $sort_class = 'sort-icon sort-inactive';
                    $direction = 'ASC';
                    $th_class = '';
                }

                // dossier for search
                $dossier = array(
                    'type' => 'ManageUsers',
                    'procedure' => 'pagination',
                );

                // generate dossier
                $dossier_for_sort = $vce->generate_dossier($dossier);

                $content .= <<<EOF
<th class="$th_class">
$nice_attribute_title
EOF;

                // check if this is a sortable attribute
                if (isset($each_attribute_value['sortable']) && $each_attribute_value['sortable']) {

                    $content .= <<<EOF
<div class="$sort_class" dossier="$dossier_for_sort" sort="$each_attribute_key" direction="$direction" action="$vce->input_path" title="Sort By $nice_attribute_title"></div>
EOF;

                } else {

                    $content .= <<<EOF
<div class="sort-icon"></div>
EOF;

                }

                $content .= <<<EOF
</th>
EOF;

            }

            $content .= <<<EOF
</tr>
</thead>
<tbody>
EOF;

            // check permissions and assign values
            $edit_users = $vce->check_permissions('edit_users') ? true : false;
            $masquerade_users = $vce->check_permissions('masquerade_users') ? true : false;
            $delete_users = $vce->check_permissions('delete_users') ? true : false;

            // prepare for filtering of roles limited by hierarchy
            if (!empty($filter_by)) {
                $role_hierarchy = array();
                // create a lookup array from role_name to role_hierarchy
                foreach ($roles as $roles_key => $roles_value) {
                    $role_hierarchy[$roles_key] = $roles_value['role_hierarchy'];
                }
            }

            // loop through users
            foreach ($users_list as $each_user) {

                // check if filtering is happening
                if (!empty($filter_by)) {
                    // loop through filters and check if any user fields are a match
                    foreach ($filter_by as $filter_key => $filter_value) {
                        // prevent roles hierarchy above this from displaying
                        if ($role_hierarchy[$users[$each_user]['role_id']] < $role_hierarchy[$vce->user->role_id]) {
                            continue 2;
                        }

                        if ($filter_key == "role_id") {
                            // make title of role
                            //    $filter_value = $roles[$filter_value]['role_name'];
                            if ($users[$each_user]['role_id'] != $filter_value) {
                                continue 2;
                            }

                            continue;
                        }
                        // check if $filter_value is an array
                        if (is_array($filter_value)) {
                            // check that meta_key exists for this user
                            if (!isset($users[$each_user][$filter_key])) {
                                continue 2;
                            }
                            // check if not in the array
                            if (!in_array($users[$each_user][$filter_key], $filter_value)) {
                                // continue foreach before this foreach
                                continue 2;
                            }
                        } else {
                            // doesn't match so continue
                            if (isset($users[$each_user][$filter_key])) {
                                if ($users[$each_user][$filter_key] != $filter_value) {
                                    // continue foreach before this foreach
                                    continue 2;
                                }
                            } else {
                                continue 2;
                            }
                        }
                    }
                }

                $content .= '<tr>';

                //$dossier_for_edit = $vce->user->encryption(json_encode(array('type' => 'ManageUsers','procedure' => 'edit','user_id' => $each_user)),$vce->user->session_vector);
                $dossier_for_edit = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'edit', 'user_id' => $each_user));

                //$dossier_for_masquerade = $vce->user->encryption(json_encode(array('type' => 'ManageUsers','procedure' => 'masquerade','user_id' => $each_user)),$vce->user->session_vector);
                $dossier_for_masquerade = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'masquerade', 'user_id' => $each_user));

                //$dossier_for_delete = $vce->user->encryption(json_encode(array('type' => 'ManageUsers','procedure' => 'delete','user_id' => $each_user)),$vce->user->session_vector);
                $dossier_for_delete = $vce->generate_dossier(array('type' => 'ManageUsers', 'procedure' => 'delete', 'user_id' => $each_user));

                $content .= <<<EOF
<td class="align-center">
EOF;

                if ($edit_users) {

                    $content .= <<<EOF
<form class="inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_edit">
<input type="hidden" name="sort_by" value="$sort_by">
<input type="hidden" name="sort_direction" value="$sort_direction">
<input type="hidden" name="pagination_current" value="$pagination_current">
<input type="submit" value="Edit">
</form>
EOF;

                }

                if ($masquerade_users) {

                    $content .= <<<EOF
<form class="inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_masquerade">
<input type="submit" value="Masquerade">
</form>
EOF;

                }

                if ($delete_users) {

                    $content .= <<<EOF
<form class="delete-form inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
EOF;

                }

                $content .= <<<EOF
</td>
EOF;

                foreach ($attributes as $each_attribute_key => $each_attribute_value) {

                    // exception for role_id, change to role_name
                    if ($each_attribute_key == 'role_id') {
                        $each_attribute_key = 'role_name';
                    }

                    // if conceal is set, as in the case of password, skip to next
                    if (isset($each_attribute_value['type']) && $each_attribute_value['type'] == 'conceal') {
                        continue;
                    }

                    // prevent error if not set
                    $attribute_value = isset($users[$each_user][$each_attribute_key]) ? $users[$each_user][$each_attribute_key] : null;

                    if (isset($each_attribute_value['datalist'])) {

                        if (isset($datalist_cache[$attribute_value])) {

                            // user saved value
                            $attribute_name = $datalist_cache[$attribute_value];

                        } else {

                            $datalist = $vce->get_datalist_items(array('item_id' => $attribute_value));

                            $attribute_name = isset($datalist['items'][$attribute_value]['name']) ? $datalist['items'][$attribute_value]['name'] : null;

                            // save it so we dont need to look up again
                            $datalist_cache[$attribute_value] = $attribute_name;

                        }

                        $attribute_value = $attribute_name;

                    }

                    $content .= '<td>' . $attribute_value . '</td>';

                }

                $content .= '</tr>';

            }

            $content .= <<<EOF
</tbody>
</table>
<br>
</div>
<div class="clickbar-title disabled"><span>Users</span></div>
</div>
</p>
EOF;

        }

        $vce->content->add('main', $content);

    }

    /**
     * Create a new user
     */
    protected function create($input) {

        // loop through to look for checkbox type input
        foreach ($input as $input_key => $input_value) {
            // for checkbox inputs
            if (preg_match('/_\d+$/', $input_key, $matches)) {
                // strip _1 off to find input value for checkbox
                $new_input = str_replace($matches[0], '', $input_key);
                // decode previous json object value for input variable
                $new_value = isset($input[$new_input]) ? json_decode($input[$new_input], true) : array();
                // add new value to array
                $new_value[] = $input_value;
                // remove the _1
                unset($input[$input_key]);
                // reset the input with json object
                $input[$new_input] = json_encode($new_value);
            }
        }

        // remove type so that it's not created for new user

        $email = filter_var(strtolower($input['email']), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(array('response' => 'error', 'message' => 'Email is not a valid email address', 'form' => 'create', 'action' => ''));
            return;
        }

        if (user::user_exists($email)) {
            echo json_encode(array('response' => 'error', 'message' => 'Email is already in use', 'form' => 'create', 'action' => ''));
            return;
        }

		$password = $input['password'];
		$role_id = $input['role_id'];

        unset($input['type']);
		unset($input['procedure']);
        unset($input['password']);
		unset($input['role_id']);
		
        user::create_user($email, $password, $role_id, $input);

        echo json_encode(array('response' => 'success', 'message' => 'User has been created', 'form' => 'create', 'action' => ''));
        return;
    }

    /**
     * edit user
     */
    protected function edit($input) {

        // add attributes to page object for next page load using session
        global $site;

        $site->add_attributes('edit_user', $input['user_id']);

        $pagination_current = filter_var($input['pagination_current'], FILTER_SANITIZE_NUMBER_INT);

        if ($pagination_current < 1) {
            $pagination_current = 1;
        }

        $site->add_attributes('sort_by', $input['sort_by']);
        $site->add_attributes('sort_direction', $input['sort_direction']);
        $site->add_attributes('pagination_current', $pagination_current);

        echo json_encode(array('response' => 'success', 'message' => 'session data saved', 'form' => 'edit'));
        return;

    }

    /**
     * update user
     */
    protected function update($input) {

        global $db;
        global $site;

        // loop through to look for checkbox type input
        foreach ($input as $input_key => $input_value) {
            // for checkbox inputs
            if (preg_match('/_\d+$/', $input_key, $matches)) {
                // strip _1 off to find input value for checkbox
                $new_input = str_replace($matches[0], '', $input_key);
                // decode previous json object value for input variable
                $new_value = isset($input[$new_input]) ? json_decode($input[$new_input], true) : array();
                // add new value to array
                $new_value[] = $input_value;
                // remove the _1
                unset($input[$input_key]);
                // reset the input with json object
                $input[$new_input] = json_encode($new_value);
            }
        }

        // get user attributes
        $user_attributes = json_decode($site->user_attributes, true);

        // start with default
        $attributes = array('email' => 'text');

        // assign values into attributes for order preserving hash in minutia column
        if (isset($user_attributes)) {
            foreach ($user_attributes as $user_attributes_key => $user_attributes_value) {
                if (isset($user_attributes_value['sortable']) && $user_attributes_value['sortable']) {
                    $value = isset($user_attributes_value['type']) ? $user_attributes_value['type'] : null;
                    $attributes[$user_attributes_key] = $value;
                }
            }
        }

        $user_id = $input['user_id'];

        $query = "SELECT role_id, vector FROM " . TABLE_PREFIX . "users WHERE user_id='" . $user_id . "'";
        $user_info = $db->get_data_object($query);

        $role_id = $user_info[0]->role_id;
        $vector = $user_info[0]->vector;

        // has role_id been updated?
        if (isset($input['role_id']) && $input['role_id'] != $role_id) {

            $update = array('role_id' => $input['role_id']);
            $update_where = array('user_id' => $user_id);
            $db->update('users', $update, $update_where);

        }

        // clean up
        unset($input['type'], $input['procedure'], $input['role_id'], $input['user_id']);

        // delete old meta data
        foreach ($input as $key => $value) {

            // delete user meta from database
            $where = array('user_id' => $user_id, 'meta_key' => $key);
            $db->delete('users_meta', $where);

        }

        // now add meta data

        $records = array();

        foreach ($input as $key => $value) {

            // encode user data
            $encrypted = user::encryption($value, $vector);

            $minutia = null;

            // if this is a sortable text attribute
            if (isset($attributes[$key])) {
                // check if this is a text field
                if ($attributes[$key] == 'text') {
                    $minutia = user::order_preserving_hash($value);
                }
                // other option will go here
            }

            $records[] = array(
                'user_id' => $user_id,
                'meta_key' => $key,
                'meta_value' => $encrypted,
                'minutia' => $minutia,
            );

        }

        // check that $records is not empty
        if (!empty($records)) {
            $db->insert('users_meta', $records);
        }

        echo json_encode(array('response' => 'success', 'message' => 'User Updated', 'form' => 'create', 'action' => ''));
        return;

    }

    /**
     * Masquerade as user
     */
    protected function masquerade($input) {

        global $user;

        // pass user id to masquerade as
        $user->make_user_object($input['user_id']);

        global $site;

        echo json_encode(array('response' => 'success', 'message' => 'User masquerade', 'form' => 'masquerade', 'action' => $site->site_url));
        return;

    }

    /**
     * Delete a user
     */
    protected function delete($input) {

        global $db;

        // delete user from database
        $where = array('user_id' => $input['user_id']);
        $db->delete('users', $where);

        // delete user from database
        $where = array('user_id' => $input['user_id']);
        $db->delete('users_meta', $where);

        echo json_encode(array('response' => 'success', 'message' => 'User has been deleted', 'form' => 'delete', 'user_id' => $input['user_id'], 'action' => ''));
        return;

    }

    /**
     * Filter
     */
    protected function filter($input) {

        global $site;

        foreach ($input as $key => $value) {
            if (strpos($key, 'filter_by_') !== FALSE) {
                $site->add_attributes($key, $value);
            }
        }

        $site->add_attributes('pagination_current', $input['pagination_current']);

        echo json_encode(array('response' => 'success', 'message' => 'Filter'));
        return;

    }

    /**
     * pagination users
     */
    protected function pagination($input) {

        // add attributes to page object for next page load using session
        global $site;

        $pagination_current = filter_var($input['pagination_current'], FILTER_SANITIZE_NUMBER_INT);

        if ($pagination_current < 1) {
            $pagination_current = 1;
        }

        $site->add_attributes('sort_by', $input['sort_by']);
        $site->add_attributes('sort_direction', $input['sort_direction']);
        $site->add_attributes('pagination_current', $pagination_current);

        echo json_encode(array('response' => 'success', 'message' => 'pagination'));
        return;

    }

    /**
     * search for a user
     */
    public static function search($input) {

        global $db;
        global $site;
        global $user;

        if (!isset($input['search']) || strlen($input['search']) < 3) {
            // return a response, but without any results
            echo json_encode(array('response' => 'success', 'results' => null));
            return;
        }

        // break into array based on spaces
        $search_values = explode('|', preg_replace('/\s+/', '|', $input['search']));

        // get all users of specific roles as an array
        $query = "SELECT * FROM " . TABLE_PREFIX . "users";
        $find_users_by_role = $db->get_data_object($query, 0);

        // get roles
        $roles = json_decode($site->roles, true);

        $roles_list = array();
        foreach ($roles as $key => $value) {
            if ($value['role_hierarchy'] >= $roles[$user->role_id]['role_hierarchy']) {
                // add to role_id to array
                $roles_list[] = $key;
            }
        }

        // cycle through users
        foreach ($find_users_by_role as $key => $value) {
            // filter out higher role_id
            if (in_array($value['role_id'], $roles_list)) {
                // add user_id to array for the IN contained within database call
                $users_id_in[] = $value['user_id'];
                // and these other values
                $all_users[$value['user_id']]['user_id'] = $value['user_id'];
                $all_users[$value['user_id']]['role_id'] = $value['role_id'];
                $all_users[$value['user_id']]['vector'] = $value['vector'];
                // set for search
                $match[$value['user_id']] = 0;
            }
        }

        if (!isset($users_id_in)) {
            echo json_encode(array('response' => 'success', 'results' => null));
            return;
        }

        $query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE user_id IN (" . implode(",", $users_id_in) . ")";
        $users_meta_data = $db->get_data_object($query, 0);

        foreach ($users_meta_data as $key => $value) {

            // skip a few meta_key that we don't want to allow searching in
            if ($value['meta_key'] == 'lookup' || $value['meta_key'] == 'persistent_login') {
                continue;
            }

            // decrypt the values
            $all_users[$value['user_id']][$value['meta_key']] = user::decryption($value['meta_value'], $all_users[$value['user_id']]['vector']);

            // test multiples
            for ($i = 0; $i < count($search_values); $i++) {
                // create a search
                $search = '/^' . $search_values[$i] . '/i';
                if (preg_match($search, $all_users[$value['user_id']][$value['meta_key']]) && !isset($counter[$value['user_id']][$i])) {
                    // add to specific match
                    $match[$value['user_id']]++;
                    // set a counter to prevent repeats
                    $counter[$value['user_id']][$i] = true;
                    // break so it only counts once for this value
                    break;
                }
            }
        }

        // cycle through match to see if the number is equal to count
        foreach ($match as $match_user_id => $match_user_value) {
            // unset vector
            unset($all_users[$match_user_id]['vector']);
            // if there are fewer than count, then unset
            if ($match_user_value < count($search_values)) {
                // unset user info if the count is less than the total
                unset($all_users[$match_user_id]);
            }
        }

        // hook to work with search results
        if (isset($site->hooks['manage_users_attributes_search'])) {
            foreach ($site->hooks['manage_users_attributes_search'] as $hook) {
                $all_users = call_user_func($hook, $all_users);
            }
        }

        if (count($all_users)) {

            $user_keys = array_keys($all_users);

            $site->add_attributes('search_value', $input['search']);
            $site->add_attributes('user_search_results', json_encode($user_keys));

            echo json_encode(array('response' => 'success', 'form' => 'edit'));
            return;
        }

        $site->add_attributes('search_value', $input['search']);
        $site->add_attributes('user_search_results', null);

        echo json_encode(array('response' => 'success', 'form' => 'edit'));
        return;

    }

    /**
     * fileds to display when this is created
     */
    public function recipe_fields($recipe) {

        $title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];
        $url = isset($recipe['url']) ? $recipe['url'] : null;

        $elements = <<<EOF
<input type="hidden" name="auto_create" value="forward">
<label>
<input type="text" name="title" value="$title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
<label>
<input type="text" name="url" value="$url" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">URL</div>
<div class="label-error">Enter a URL</div>
</div>
</label>
EOF;

        return $elements;

    }

}