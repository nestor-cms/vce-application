<?php

class ManageRecipes extends Component {

    /**
     * basic info about the component
     */
    public function component_info() {
        return array(
            'name' => 'Manage Recipes',
            'description' => 'Create, edit, and delete recipes of different components (The power behind the throne).',
            'category' => 'admin',
        );
    }

    /**
     * display content specific to this component
     */
    public function as_content($each_component, $vce) {

        // check if value is in page object / check to see if we want to edit this recipe
        $component_id = isset($vce->component_id) ? $vce->component_id : null;

        $top_clickbar = isset($component_id) ? ' clickbar-open' : '';
        $bottom_clickbar = isset($component_id) ? '' : ' clickbar-closed';

        // nestable jquery plugin this is all based on
        // http://dbushell.github.io/Nestable/
        // https://github.com/dbushell/Nestable

        // add javascript to page
        $vce->site->add_script(dirname(__FILE__) . '/js/jquery-nestable.js', 'jquery tablesorter');

        // add javascript to page
        $vce->site->add_script(dirname(__FILE__) . '/js/script.js');

        // add javascript to page
        $vce->site->add_style(dirname(__FILE__) . '/css/jquery-nestable.css', 'jquery-nestable-style');

        // add javascript to page
        $vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'manage-users-style');

        $content = <<<EOF
<div class="clickbar-container">
<div class="clickbar-content$top_clickbar">
<div class="sort-block left-block">
<div class="sort-block-title">Components</div>

<div class="dd" id="nestable">
<ol class="dd-list">
EOF;

        $categories = array();

        // cycle through installed components
        foreach (json_decode($vce->site->activated_components, true) as $key => $value) {

            $component_path = BASEPATH . $value;

            if (file_exists($component_path)) {

                // load component class
                require_once $component_path;

                $access = new $key;

                // get info for component
                //$info = $access->component_info();
                $info = (object) $access->component_info();

                // set recipes fields
                $recipe_fields = $access->recipe_fields(array('type' => $key));

                // do not display if $recipe_fields returned false
                if ($recipe_fields) {

                    $categories[$info->category] = true;

                    $content .= <<<EOF
<li class="dd-item $info->category-component all-components" referrer="$key" data-type="$key" unique-id="$key">
<div class="dd-handle dd3-handle">&nbsp;</div>
<div class="dd-content"><div class="dd-title">$info->name</div>
<div class="dd-toggle"></div>
<div class="dd-content-extended $key-extended">$recipe_fields
<label><div class="input-padding">$info->description</div></label>
<button class="remove-button" data-action="remove" type="button">Remove</button>
</div></div></li>
EOF;

                }

            }

        }

        $content .= <<<EOF
</ol>
<br>
<div class="clickbar-container">
<div class="clickbar-content clickbar-open">
EOF;

        // alpha sort of categories
        ksort($categories);

        //
        foreach ($categories as $category_key => $category_value) {
            $content .= '<button class="category-display';
            if ($category_key == 'site') {
                $content .= ' highlight';
            }
            $content .= '" category="' . $category_key . '">' . $category_key . '</button>';
        }

        $content .= <<<EOF
</div>
<div class="clickbar-title"><span>Display By Category</span></div>
</div>
</div>
</div>
<div class="sort-block right-block">
<div class="sort-block-title">Recipe</div>
<div class="dd" id="nestable2">
EOF;

        if (isset($component_id)) {

            // start update

            $content .= '<ol class="dd-list">';

            // get recipe
            $query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $component_id . "' AND meta_key ='recipe'";
            $recipe_value = $vce->db->get_data_object($query);

            $recipe_object = json_decode($recipe_value[0]->meta_value, true);

            $recipe = $recipe_object['recipe'];
            $recipe_name = $recipe_object['recipe_name'];
            $full_object = isset($recipe_object['full_object']) ? $recipe_object['full_object'] : null;

            // adding this component_id to the recipe object
            $recipe[0]['component_id'] = $component_id;

            // call to recursive function
            $content .= self::cycle_though_recipe($recipe);

            // create dossier, which is an encrypted json object of details uses in the form
            $dossier = $vce->generate_dossier(array('type' => 'ManageRecipes', 'procedure' => 'update'));

            $content .= <<<EOF
</ol>
</div>
<form id="create_sets" class="recipe-form asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier">
<div class="recipe-info" style="clear:both">
<label>
<input type="text" name="recipe_name" value="$recipe_name" tag="required">
<div class="label-text">
<div class="label-message">Recipe Name</div>
<div class="label-error">Enter a Recipe Name</div>
</div>
</label>
<div class="clickbar-container">
<div class="clickbar-content">
EOF;
            // load hooks
            if (isset($site->hooks['recipe_attributes'])) {
                foreach ($site->hooks['recipe_attributes'] as $hook) {
                    $content .= call_user_func($hook, $vce->user);
                }
            }

            $content .= <<<EOF
<label>
<div class="input-padding">
<input type="checkbox" name="full_object" value="true"
EOF;

            if (isset($full_object)) {
                $content .= ' checked';
            }

            $content .= <<<EOF
> Generate Full Page Object
</div>
<div class="label-text">
<div class="label-message">Full Page Object</div>
<div class="label-error">Select</div>
</div>
</label>
</div>
<div class="clickbar-title clickbar-closed"><span>Advanced Options</span></div>
</div>
<br>

<input type="submit" value="Update This Recipe">
</div>
</form>
</div>
</div>
<div class="clickbar-title$bottom_clickbar"><span>Update This Recipe</span></div>
</div>

EOF;

            // end update
        } else {

            // create dossier, which is an encrypted json object of details uses in the form
            $dossier = $vce->generate_dossier(array('type' => 'ManageRecipes', 'procedure' => 'create'));

// start of create
            $content .= <<<EOF
<div class="dd-empty"></div>
</div>
<form id="create_sets" class="recipe-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier">
<div class="recipe-info" style="clear:both">
<label>
<input type="text" name="recipe_name" tag="required">
<div class="label-text">
<div class="label-message">Recipe Name</div>
<div class="label-error">Enter a Recipe Name</div>
</div>
</label>

<div class="clickbar-container">
<div class="clickbar-content">
EOF;

            // load hooks
            if (isset($site->hooks['recipe_attributes'])) {
                foreach ($site->hooks['recipe_attributes'] as $hook) {
                    $content->main .= call_user_func($hook, $vce->user);
                }
            }

            $content .= <<<EOF
<label>
<div class="input-padding">
<input type="checkbox" name="full_object" value="true"> Generate Full Page Object
</div>
<div class="label-text">
<div class="label-message">Full Page Object</div>
<div class="label-error">Select</div>
</div>
</label>
<label>
<div class="input-padding">
<textarea name="json_text" class="textarea-input"></textarea>
</div>
<div class="label-text">
<div class="label-message">Create from JSON</div>
<div class="label-error">Select</div>
</div>
</label>
</div>
<div class="clickbar-title clickbar-closed"><span>Advanced Options</span></div>
</div>
<br>
<input type="submit" value="Save This Recipe">
</div>

</form>
</div>
</div>
<div class="clickbar-title$bottom_clickbar"><span>Create A New Recipe</span></div>
</div>
EOF;

            // end of create
        }

        // fetch all recipes
        $query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE meta_key='recipe' ORDER BY meta_value ASC";
        $recipes = $vce->db->get_data_object($query);

        $content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content no-padding clickbar-open">
<table id="existing-recipes" class="tablesorter">
<thead>
<tr>
<th></th>
<th></th>
<th>Name</th>
<th>URL</th>
<th></th>
</tr>
</thead>
EOF;

        foreach ($recipes as $each_recipe) {

            $get_url = function ($parent_id) use (&$get_url) {

                global $db;

                // fetch all recipes
                $query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE parent_id='" . $parent_id . "'";
                $component = $db->get_data_object($query);

                if (!empty($component[0]->url)) {
                    return $component[0]->url;
                } else {

                    if (isset($component[0])) {
                        return $get_url($component[0]->component_id);
                    }
                }

            };

            // fetch all recipes
            $query = "SELECT * FROM " . TABLE_PREFIX . "components WHERE component_id='" . $each_recipe->component_id . "'";
            $component = $vce->db->get_data_object($query);

            $recipe_url = "";

            if (!empty($component[0]->url)) {

                $recipe_url = $component[0]->url;

            } else {

                $recipe_url = $get_url($component[0]->component_id);

            }

            // fetch all recipes
            $query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE meta_key='created_at' AND component_id='" . $each_recipe->component_id . "'";
            $meta_value = $vce->db->get_data_object($query);

            $recipe_object = json_decode($each_recipe->meta_value, true);

            $recipe_name = $recipe_object['recipe_name'];

            // create dossier, which is an encrypted json object of details uses in the form

            $dossier_edit = array(
                'type' => 'ManageRecipes',
                'procedure' => 'edit',
                'component_id' => $each_recipe->component_id,
                'created_at' => $meta_value[0]->meta_value,
            );

            $dossier_for_edit = $vce->generate_dossier($dossier_edit);

            $dossier_delete = array(
                'type' => 'ManageRecipes',
                'procedure' => 'delete',
                'component_id' => $each_recipe->component_id,
                'created_at' => $meta_value[0]->meta_value,
            );

            $dossier_for_delete = $vce->generate_dossier($dossier_delete);

            $content .= <<<EOF
<tr>
<td class="align-center">
<form id="edit-$each_recipe->component_id" class="inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_edit">
<input type="submit" value="Edit">
</form>
</td>
<td class="align-center">
<button class="view-recipe-object" component_id="$each_recipe->component_id">View Object</button>
</td>
<td>
$recipe_name
</td>
<td>
<a href="$vce->site_url/$recipe_url">$recipe_url</a>
</td>
<td class="align-center">
<form id="delete-$each_recipe->component_id" class="delete-form inline-form asynchronous-form" method="post" action="$vce->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
</td>
</tr>
<tr class="recipe-object recipe-object-$each_recipe->component_id">
<td colspan=5>
<textarea>$each_recipe->meta_value</textarea>
</td>
</tr>
EOF;

        }

        $content .= <<<EOF
</table>
</div>
<div class="clickbar-title"><span>Existing Recipes</span></div>
</div>
EOF;

        $vce->content->add('main', $content);

    }

    /**
     * recursive function to display current recipes
     */
    private function cycle_though_recipe($current_recipe, $component_id = null, $previous_exist = true, $level = 0) {

        global $vce;

        // get global site object and grab components list
        $activated_components = json_decode($vce->site->activated_components, true);

        $content = "";

        // get all components at this level that have parent
        if (isset($component_id)) {

            $query = "SELECT * FROM " . TABLE_PREFIX . "components LEFT JOIN " . TABLE_PREFIX . "components_meta ON " . TABLE_PREFIX . "components.component_id = " . TABLE_PREFIX . "components_meta.component_id WHERE " . TABLE_PREFIX . "components.parent_id='" . $component_id . "' AND " . TABLE_PREFIX . "components_meta.meta_key='type' ORDER BY " . TABLE_PREFIX . "components.sequence ASC";
            $current_level = $vce->db->get_data_object($query);

        }

        foreach ($current_recipe as $counter => $each_item) {

            // set to true when dd-nodrag should be added to component that exists and should not be deleted
            $check = null;

            $this_recipe = $each_item;

            unset($this_recipe['components']);

            // check if component has been activated
            if (isset($activated_components[$each_item['type']])) {

                // load component class
                require_once BASEPATH . '/' . $activated_components[$each_item['type']];

                $access = new $each_item['type'];

                // get info for component
                $info = $access->component_info();

            } else {

                $access = new Component();

                // get info for component
                $info = $access->component_info();

                // write over $info['name'] with $each_item['type']
                $info['name'] = $each_item['type'] . ' (Component Disabled)';

            }

            $content .= '<li class="dd-item" referrer="' . $each_item['type'] . '" data-type="' . $each_item['type'] . '" unique-id="' . $each_item['type'] . '"';

            // set var for component exists check
            $check = false;

            if (isset($each_item['auto_create']) && $each_item['auto_create'] == "forward") {

                // prevent errors
                $url = isset($each_item['url']) ? $each_item['url'] : "/";

                // if component_id was added to the recipe
                if (isset($each_item['component_id'])) {
                    $check = true;
                    $component_id = $each_item['component_id'];
                    $content .= ' data-component_id="' . $component_id . '"';
                } else {
                    if (count($current_level)) {

                        // no longer using $previous_exist but keeping until there is time to check this all out
                        //if (isset($current_level[$counter]) && $each_item['type'] == $current_level[$counter]->meta_value) {

                        if (isset($current_level[0]->component_id) && $previous_exist) {

                            // This was probably created when the recipe was saved, right?
                            $check = true;
                            // get the current component_id from the first array element
                            $component_id = $current_level[0]->component_id;
                            $content .= ' data-component_id="' . $component_id . '"';

                        }

                        // get the current component_id from the first array element
                        // $component_id = $current_level[0]->component_id;
                        // $content .= ' data-component_id="' . $component_id . '"';

                        // remove the first array element
                        array_shift($current_level);

                    }

                }

            } else {

                // prevent auto_create components from being static if they are deeper in the recipe
                $previous_exist = false;

            }

            $content .= '>';

            $content .= '<div class="dd-handle dd3-handle';

            if ($check) {
                $content .= ' dd-nodrag';
            }

            $content .= '">&nbsp;</div>';

            $content .= '<div class="dd-content"><div class="dd-title">';

            if ($check) {
                $key_match = false;
                $content .= '<select name="type" class="select-component-type">';
                // cycle through installed components to create select menu
                foreach (json_decode($vce->site->activated_components, true) as $key => $value) {

                    // load component class to get name
                    require_once BASEPATH . '/' . $value;

                    // instance
                    $each_component = new $key;

                    // get info for component
                    $component_info = $each_component->component_info();

                    $content .= '<option value="' . $key . '"';
                    if ($key == $each_item['type']) {
                        $key_match = true;
                        $content .= ' selected';
                    }
                    $content .= '>' . $component_info['name'] . '</option>';
                }

                // if a component has been disabled, add option onto end with message
                if (!$key_match) {
                    $content .= '<option value="' . $each_item['type'] . '" selected>' . $info['name'] . '</option>';
                }

                $content .= '</select>';

            } else {

                $content .= $info['name'];

            }

            $content .= '</div><div class="dd-toggle"></div>';

            $content .= '<div class="dd-content-extended">' . $access->recipe_fields($this_recipe);

            $content .= '<label><div class="input-padding">' . $info['description'] . '</div></label>';

            if (!$check) {
                $content .= '<button class="remove-button" data-action="remove" type="button">Remove</button>';
            }

            $content .= '</div></div>';

            if (isset($each_item['components'])) {

                $content .= '<ol class="dd-list">';

                $level++;

                $content .= self::cycle_though_recipe($each_item['components'], $component_id, $previous_exist, $level);

                $content .= '</ol></li>';

            }
        }

        return $content;

    }

    /**
     * Create a new recipe
     */
    protected function create($input) {

        $recipe_name = $input['recipe_name'];

        $recipe_array = [];

        // see if admin is passing in json recipe json
        $recipe = isset($input['json_text']) && $input['json_text'] != '' ? json_decode(stripcslashes(html_entity_decode($input['json_text'])), true) : null;

        if ($recipe) {
            $recipe_array = $recipe['recipe'];
        } else {

            // create an associate array from the json object of recipe
            $recipe_array = isset($input['json']) ? json_decode($input['json'], true) : null;
            $recipe['recipe'] = $recipe_array;
        }

        // no recipe created
        if (!count($recipe_array)) {
            echo json_encode(array('response' => 'error', 'message' => 'Add a component'));
            return;
        }

        // more than one first level component
        if (count($recipe_array) > 1) {
            echo json_encode(array('response' => 'error', 'message' => 'Only one first level component is allowed.'));
            return;
        }

        // first level component must be one that auto_creates
        if (!isset($recipe_array[0]['auto_create'])) {
            echo json_encode(array('response' => 'error', 'message' => $recipe_array[0]['type'] . ' cannot be a first level component'));
            return;
        }

        // remove type so that first recipe component is not effected
        unset($input['type']);

        // adds additional meta_data to recipe from hooks, et cetera
        foreach ($input as $key => $value) {
            if ($key != 'json' && $key != 'json_text') {
                $recipe[$key] = $value;
            }
        }

        // call to recursive funtion to process and create components that need to be created on save
        self::process_recipe($recipe_array, $recipe);

        echo json_encode(array('response' => 'success', 'message' => 'Recipe Created!'));
        return;

    }

    /**
     * edit recipe
     */
    protected function edit($input) {

        // add attributes to page object for next page load using session
        global $site;

        $site->add_attributes('component_id', $input['component_id']);

        echo json_encode(array('response' => 'success', 'message' => 'session data saved', 'form' => 'edit'));
        return;

    }

    /**
     * Update an existing recipe
     */
    protected function update($input) {

        // create an associate array from the json object of recipe
        if (isset($input['json'])) {
            $recipe_array = json_decode($input['json'], true);
        } else {
            echo json_encode(array('response' => 'error', 'message' => 'json empty'));
            return;
        }

        //$component_id = $input['component_id'];
        $recipe_name = $input['recipe_name'];

        // more than one first level component
        if (count($recipe_array) > 1) {
            echo json_encode(array('response' => 'error', 'message' => 'Only one first level component is allowed.'));
            return;
        }

        // first level component must be one that auto_creates
        if (!isset($recipe_array[0]['auto_create'])) {
            echo json_encode(array('response' => 'error', 'message' => $recipe_array[0]['type'] . ' cannot be a first level component'));
            return;
        }

        // remove type so that first recipe component is not effected
        unset($input['type']);

        $recipe['recipe'] = $recipe_array;

        // adds additional meta_data to recipe from hooks, et cetera
        foreach ($input as $key => $value) {
            if ($key != 'json') {
                $recipe[$key] = $value;
            }
        }

        self::process_recipe($recipe_array, $recipe);

        echo json_encode(array('response' => 'updated', 'message' => 'Recipe has been updated'));
        return;

    }

    /**
     * process the current recipe, create / update components that need to be created on save (auto_create)
     */
    private function process_recipe($recipe, $recipe_object, $previous = '0', $level = 1) {

        global $db;
        global $user;
        global $site;

        foreach ($recipe as $order => $each_recipe) {

            // sub_components to var before clean-up
            $sub_components = isset($each_recipe['components']) ? $each_recipe['components'] : null;

            // check to see a component_id is associated with this item - update instead of create
            if (isset($each_recipe['component_id'])) {

                // get component_id from recipe
                $component_id = $each_recipe['component_id'];

                // update the url within the components table
                if (isset($each_recipe['url'])) {
                    $update = array('url' => $each_recipe['url']);
                    $update_where = array('component_id' => $component_id);
                    $db->update('components', $update, $update_where);
                }

                // clean up before creating meta_data records for component
                unset($each_recipe['url'], $each_recipe['component_id'], $each_recipe['components'], $each_recipe['auto_create'], $each_recipe['full_object']);

                // get old meta_data
                $query = "SELECT meta_key FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $component_id . "'";
                $old_data = $db->get_data_object($query);
                $old_meta = array();
                foreach ($old_data as $old_value) {
                    foreach ($old_value as $old_key) {
                        $old_meta_keys[$old_key] = true;
                    }
                }

                //cycle through recipe keys
                foreach ($each_recipe as $key => $value) {

                    // check to see if key has already been set
                    $query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $component_id . "' AND meta_key ='" . $key . "'";
                    $meta_data = $db->get_data_object($query);

                    if (!empty($meta_data)) {

                        // key has been stored so update
                        $update = array('meta_value' => $value);
                        $update_where = array('component_id' => $component_id, 'meta_key' => $key);
                        $db->update('components_meta', $update, $update_where);

                    } else {

                        // prepare data to write to components_meta table
                        $records[] = array(
                            'component_id' => $component_id,
                            'meta_key' => $key,
                            'meta_value' => $value,
                            'minutia' => null,
                        );

                        $db->insert('components_meta', $records);

                    }

                    // remove set keys from old data list
                    unset($old_meta_keys[$key]);

                }

                // unset a few extra
                unset($old_meta_keys["created_by"], $old_meta_keys["created_at"], $old_meta_keys["recipe"]);

                // clean-up!
                foreach ($old_meta_keys as $old_meta_key => $junk_value) {
                    // delete old component meta data that is not used
                    $where = array('component_id' => $component_id, 'meta_key' => $old_meta_key);
                    $db->delete('components_meta', $where);
                }

                // if this is the root component, then update the recipe here
                if ($previous == '0') {

                    // clean up at this point before saving.
                    // replace url from recipe
                    // '/"url":"[^\"]*"\,*/'

                    // remove component_id from recipe
                    $cleaners = array('/"component_id":\d*\,*/');

                    $clean_recipe = preg_replace($cleaners, '', json_encode($recipe_object));

                    $update = array('meta_value' => $clean_recipe);
                    $update_where = array('component_id' => $component_id, 'meta_key' => 'recipe');
                    $db->update('components_meta', $update, $update_where);

                    // component_id for when page reloads
                    $site->add_attributes('component_id', $component_id);

                }

            } else {
                // item doesn't exist, so create if auto_create is in recipe

                // should this component be created on save?
                if (isset($each_recipe['auto_create']) && $each_recipe['auto_create'] == "forward" && ($previous != "0" || $level == 1)) {

                    $data = array();
                    $data['parent_id'] = $previous;
                    $data['sequence'] = $order + 1;
                    $data['url'] = isset($each_recipe['url']) ? $each_recipe['url'] : '';

                    // write data to components table
                    $component_id = $db->insert('components', $data);

                    // unset url and next level up
                    unset($each_recipe['url'], $each_recipe['components'], $each_recipe['auto_create'], $each_recipe['full_object']);

                    $records = array();

                    $records[] = array(
                        'component_id' => $component_id,
                        'meta_key' => 'created_by',
                        'meta_value' => $user->user_id,
                        'minutia' => null,
                    );

                    $records[] = array(
                        'component_id' => $component_id,
                        'meta_key' => 'created_at',
                        'meta_value' => time(),
                        'minutia' => null,
                    );

                    // if this is the root component, then save the recipe here
                    if ($previous == '0') {
                        // recipe as a json_encode array
                        $records[] = array(
                            'component_id' => $component_id,
                            'meta_key' => 'recipe',
                            'meta_value' => json_encode($recipe_object),
                            'minutia' => null,
                        );

                        // component_id for when page reloads
                        $site->add_attributes('component_id', $component_id);

                    }

                    foreach ($each_recipe as $key => $value) {
                        // component type
                        $records[] = array(
                            'component_id' => $component_id,
                            'meta_key' => $key,
                            'meta_value' => $value,
                            'minutia' => null,
                        );
                    }

                    $db->insert('components_meta', $records);

                }

            }

            // if sub_components, recursve call back to this function with parent component id
            if ($sub_components) {

                // prevent any error
                $component_id = isset($component_id) ? $component_id : '0';

                $level++;

                self::process_recipe($sub_components, $recipe_object, $component_id, $level);

            }

        }

    }

    /**
     * Delete
     */
    protected function delete($input) {

        $parent_url = self::delete_component($input);

        if (isset($parent_url)) {

            echo json_encode(array('response' => 'success', 'message' => 'Delete!', 'form' => 'delete'));
            return;
        }

        echo json_encode(array('response' => 'error', 'procedure' => 'update', 'message' => "Error"));
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