<?php
/**
 * ManageComponents Component.
 *
 * @package Components
 */

/**
 * ManageComponents Class.
 */
class ManageComponents extends Component {

    /**
     * basic info about the component
     */
    public function component_info() {
        return array(
            'name' => 'Manage Components',
            'description' => 'Activate, disable and remove componets.',
            'category' => 'admin',
        );
    }

    /**
     *
     */
    public function as_content($each_component, $vce) {

        // add javascript to page
        $vce->site->add_script(dirname(__FILE__) . '/js/script.js', 'jquery-ui');

        // add javascript to page
        $vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'manage-components-style');

        // get currently installed components
        $installed_components = json_decode($vce->site->installed_components, true);

        // get currently activated components
        $activated_components = json_decode($vce->site->activated_components, true);

        // array for sorting
        $categories = array();

        $content = '<div class="list-container"><div class="component-list">';

        // create dossier values for edit and delete
        $dossier_for_edit = $vce->generate_dossier(array('type' => 'ManageComponents', 'procedure' => 'update'));

        $dossier_for_delete = $vce->generate_dossier(array('type' => 'ManageComponents', 'procedure' => 'delete'));

        $components_list = array();

        // get all installed components
        foreach (array('vce-content', 'vce-application') as $components_dir) {

            $directory_itor = new RecursiveDirectoryIterator(BASEPATH . $components_dir . DIRECTORY_SEPARATOR .'components' . DIRECTORY_SEPARATOR);
            $filter_itor = new RecursiveCallbackFilterIterator($directory_itor, function ($current, $key, $iterator) {
                // Skip hidden files and directories.
                if ($current->getFilename()[0] === '.') {
                    return FALSE;
                }
                if ($current->isDir()) {
                    return TRUE;
                } else {
                    // Only consume .php files that are in a directory of the same name.
                    $ok = fnmatch("*.php", $current->getFilename());
                    $dirs = explode(DIRECTORY_SEPARATOR, $current->getPathname());
                    $ok = $ok && (($dirs[count($dirs) - 2] . '.php') === $current->getFilename());
                    return $ok;
                }
            });
            $itor = new RecursiveIteratorIterator($filter_itor);

            foreach ($itor as $each_component) {

                // Strip BASEPATH from this full path, since we add BASEPATH back in code below.
                $component_path = str_replace(BASEPATH, "", $each_component->getPathname());

                // get the file content to search for Child Class name
                $component_text = file_get_contents(BASEPATH . $component_path, NULL, NULL, 0, 1000);

                // looking for Child Class name
                $pattern = "/class\s+([([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+)\s+extends\s+([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+)\s+{/m";

                // continue if child class not found
                if (!preg_match($pattern, $component_text, $matches)) {
                    continue;
                }

                if (isset($matches[1]) && class_exists($matches[2])) {

                    // Class name for component
                    $type = $matches[1];
                    $parent = $matches[2];

                    // check if class already exists, and if it does, then skip ahead
                    if (isset($components_list[$type])) {
                        continue;
                    }

                    // require the component script
                    require_once BASEPATH . $component_path;

                    // create an instance of the Class
                    $current_component = new $type();

                    // add type to list to check against later
                    $components_list[$type] = true;

                    // get compontent info, such as name and description
                    $info = $current_component->component_info();

                    // add category to array for sorting
                    $categories[$info['category']] = true;

                    $content .= '<div class="all-components each-component ' . $info['category'] . '-component" type="' . $type . '" parent="' . $parent . '" url="' . $component_path . '" state="';

                    if (isset($activated_components[$type])) {
                        $content .= 'activated';
                    } else {
                        $content .= 'disabled';
                    }

                    $content .= '">';

                    $content .= '<div class="each-component-switch"><div class="switch activated';

                    if (isset($activated_components[$type])) {
                        $content .= ' highlight';
                    }

                    if (!isset($installed_components[$type])) {
                        $content .= ' install';
                    }

                    $content .= '">';

                    if (!isset($installed_components[$type])) {
                        $content .= 'Install';
                    } else {
                        $content .= 'Activated';
                    }

                    $content .= '</div><div class="switch disabled';

                    if (!isset($activated_components[$type])) {
                        $content .= ' highlight';
                    }

                    $content .= '">Disabled</div>';

                    // if ASSETS_URL has been set, hide delete because site is using a shared vce
                    if (!isset($activated_components[$type]) && !defined('ASSETS_URL')) {

                        $content .= <<<EOF
<form id="$type-remove" class="delete-component" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="hidden" name="location" value="$components_dir">
<input type="hidden" name="class" value="$type">
<input type="hidden" name="parent" value="$parent">
<input type="hidden" name="component_path" value="$component_path">
<input type="submit" value="Delete">
</form>
EOF;

                    }

                    $content .= '</div><div class="each-componet-name">' . $info['name'] . '</div><div class="each-componet-description">' . $info['description'] . '</div>';

                    if (isset($activated_components[$type]) && $fields = $current_component->component_configuration()) {

                        $dossier_for_configure = $vce->generate_dossier(array('type' => 'ManageComponents', 'procedure' => 'configure', 'component' => $type));

                        $content .= <<<EOF
<div class="clickbar-container">
<div class="clickbar-content">
<form id="$type-configuration" class="configure-component" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_configure">
EOF;
                        $content .= $fields;

                        $content .= <<<EOF
<input type="submit" value="Save">
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>Configure</span></div>
</div>
EOF;

                    }

                    // permissions exist in the component
                    if (isset($info['permissions'])) {

                        $dossier_for_permissions = $vce->generate_dossier(array('type' => 'ManageComponents', 'procedure' => 'permissions', 'component' => $type));

                        $site_roles = json_decode($vce->site->roles, true);

                        $content .= <<<EOF
<div class="clickbar-container">
<div class="clickbar-content">
<form id="$type-permissions" class="configure-component" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_permissions">
<table class="permissions-table">
EOF;

                        $content .= '<tr><td class="empty-cell"></td>';

                        // cycle through component permissions
                        foreach ($info['permissions'] as $each_permission) {

                            $description = isset($each_permission['description']) ? $each_permission['description'] : null;

                            $content .= '<td class="permissions-name"><div class="label-text">' . $each_permission['name'] . '<div class="tooltip-icon"><div class="tooltip-content">' . $description . '</div></div></div></td>';

                        }

                        $content .= '</tr>';

                        foreach ($site_roles as $role_id => $role_info) {

                            if (is_array($role_info)) {

                                $content .= '<tr>';

                                $content .= '<td>' . $role_info['role_name'] . '</td>';

                                foreach ($info['permissions'] as $each_permission) {

                                    $content .= '<td><label class="ignore">';

                                    if (isset($each_permission['type']) && $each_permission['type'] == 'singular') {

                                        $content .= '<input type="radio" name="' . $each_permission['name'] . '" value="' . $role_id . '"';

                                    } else {

                                        $content .= '<input type="checkbox" name="' . $each_permission['name'] . '_' . $role_id . '" value="' . $role_id . '"';

                                    }

                                    if (isset($role_info['permissions'][$type]) && in_array($each_permission['name'], explode(',', $role_info['permissions'][$type]))) {

                                        $content .= ' checked';

                                    }

                                    $content .= '></label></td>';

                                }

                                $content .= '</tr>';

                            }

                        }

                        $content .= <<<EOF
</table>
<input type="submit" value="Save">
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>Permissions</span></div>
</div>
EOF;

                    }

                    $content .= '</div>';

                } else {

                    // add category to array for sorting
                    $categories['error'] = true;

                    $content .= '<div class="all-components each-component error-component">' . $matches[1] . ' can not be loaded because ' . $matches[2] . ' class does not exist.<br>' . $component_path . '</div>';

                }

            }

        }

        $content .= '</div>';

        $content .= <<<EOF
<form id="update" class="components-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_edit ">
<input type="submit" value="Update Components">
</form>
EOF;

        // alpha sort of categories, and then write to screen for sorting
        ksort($categories);
        $content .= '<div class="category-display-buttons">';
        foreach ($categories as $category_key => $category_value) {
            $content .= '<button class="category-display';
            if ($category_key == 'site') {
                $content .= ' highlight';
            }
            $content .= '" category="' . $category_key . '">' . $category_key . '</button>';
        }
        $content .= '</div></div>';

        $vce->content->add('main', $content);

    }

    /**
     * Update components
     */
    protected function update($input) {

        global $vce;
        $db = $vce->db;
        $site = $vce->site;
        $user = $vce->user;

        // check that this is an admin
        if ($user->role_id == "1") {

            // create an associate array from the json object of components
            foreach (json_decode($input['json'], true) as $key => $value) {

                if (class_exists($value['parent'])) {

                    // components that are minions of another Component, such as media types
                    if ($value['parent'] != "Component") {
                        $components_minions['enabled_' . strtolower($value['parent'])][$value['type']] = $value['url'];
                    }

                    $components_list[$value['type']] = $value['url'];

                }

            }

            $installed_components = json_decode($site->installed_components, true);
            $install_items = array();

            $activated_components = json_decode($site->activated_components, true);
            $activate_items = array();

            $preloaded_components = json_decode($site->preloaded_components, true);

            $disable_items = array();

            // find newly disabled components
            foreach ($activated_components as $type => $path) {
                if (!isset($components_list[$type])) {

                    $disable_items[$type] = $path;

                    // remove component from activated list
                    unset($activated_components[$type]);

                    // remove component from preloaded list
                    unset($preloaded_components[$type]);

                    // remove enabled minions
                    foreach ($site as $meta_key => $meta_value) {

                        if (preg_match('/^enabled_/', $meta_key)) {

                            $deputed_list = json_decode($site->$meta_key, true);

                            if (!empty($deputed_list)) {

                                foreach ($deputed_list as $type => $url) {

                                    if (!isset($components_minions[$meta_key][$type])) {
                                        unset($deputed_list[$type]);
                                    }

                                }

                                if (!empty($deputed_list)) {

                                    $update = array('meta_value' => json_encode($deputed_list, JSON_UNESCAPED_SLASHES));
                                    $update_where = array('meta_key' => $meta_key);
                                    $db->update('site_meta', $update, $update_where);

                                } else {

                                    // delete if empty
                                    $where = array('meta_key' => $meta_key);
                                    $db->delete('site_meta', $where);
                                }

                            }

                        }

                    }

                }

            }

            // find newly activated components
            foreach ($components_list as $type => $path) {

                if (!isset($activated_components[$type])) {

                    $activate_items[$type] = $path;

                    // add component to activated_components
                    $activated_components[$type] = $path;

                    if (!isset($installed_components[$type])) {

                        // add component to array to check for installed function after database record is updated
                        $install_items[$type] = $path;

                        // add component to installed_components
                        $installed_components[$type] = $path;

                    }

                    // adding enabled minions
                    if (!empty($components_minions)) {

                        foreach ($components_minions as $this_key => $this_value) {

                            $deputed_list = isset($site->$this_key) ? json_decode($site->$this_key, true) : array();

                            foreach ($this_value as $list => $url) {

                                $deputed_list[$list] = $url;

                            }

                            // check if key already exists
                            $query = "SELECT * FROM " . TABLE_PREFIX . "site_meta WHERE meta_key='" . $this_key . "'";
                            $key_exists = $db->get_data_object($query);

                            if (!empty($key_exists)) {

                                // update
                                $update = array('meta_value' => json_encode($deputed_list, JSON_UNESCAPED_SLASHES));
                                $update_where = array('meta_key' => $this_key);
                                $db->update('site_meta', $update, $update_where);

                            } else {

                                // created
                                $records[] = array(
                                    'meta_key' => $this_key,
                                    'meta_value' => json_encode($deputed_list, JSON_UNESCAPED_SLASHES),
                                    'minutia' => null,
                                );

                                $db->insert('site_meta', $records);

                            }

                        }

                    }

                }

            }

            $update = array('meta_value' => json_encode($installed_components, JSON_UNESCAPED_SLASHES));
            $update_where = array('meta_key' => 'installed_components');
            $db->update('site_meta', $update, $update_where);

            // cycle though newly installed items
            foreach ($install_items as $type => $path) {

                // load class
                require_once BASEPATH . $path;

                $activated = new $type();

                // fire installed function
                $activated->installed();

            }

            // using the $components_list object to update this
            $update = array('meta_value' => json_encode($activated_components, JSON_UNESCAPED_SLASHES));
            $update_where = array('meta_key' => 'activated_components');
            $db->update('site_meta', $update, $update_where);

            // cycle though newly activated items
            foreach ($activate_items as $type => $path) {

                // load class
                require_once BASEPATH . $path;

                $activated = new $type();

                // fire installed function
                $activated->activated();

                if ($activated->preload_component() !== false) {
                    $preloaded_components[$type] = $path;
                }

            }

            $update = array('meta_value' => json_encode($preloaded_components, JSON_UNESCAPED_SLASHES));
            $update_where = array('meta_key' => 'preloaded_components');
            $db->update('site_meta', $update, $update_where);

            // cycle though newly activated items
            foreach ($disable_items as $type => $path) {

                // load class
                require_once BASEPATH . $path;

                $disabled = new $type();

                // fire installed function
                $disabled->disabled();

            }

            echo json_encode(array('response' => 'success', 'procedure' => 'update', 'action' => 'reload', 'message' => 'Updated'));
            return;

        }

        echo json_encode(array('response' => 'error', 'procedure' => 'update', 'message' => 'Unknown Procedure'));
        return;

    }

    /**
     * delete a component
     */
    protected function delete($input) {

        // $input['type'] is added in class.component.php in form_input
        // it returns ManageComponents...
        // we don't want that, but instead want the class name that is contained in $input['class']

        global $vce;
        $db = $vce->db;
        $site = $vce->site;
        $user = $vce->user;

        // check that this is an admin
        if ($user->role_id == "1") {

            // get path to component
            $installed_components = json_decode($site->installed_components, true);

            if (isset($installed_components[$input['class']])) {

                $path = $installed_components[$input['class']];

                // load class
                require_once BASEPATH . $path;

                $component = new $input['class']();

                // fire removed function
                $removed = $component->removed();

            } else {

                // create path
                // $path = $input['location'] .  '/components/' . strtolower($input['class']) . '/' . strtolower($input['class']) . '.php';

                // get path from input value
                $path = $input['component_path'];

            }

            // fullpath
            $dirPath = BASEPATH . dirname($path);

            // delete component directory
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
                $path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
            }
            rmdir($dirPath);

            $installed_components = json_decode($site->installed_components, true);
            unset($installed_components[$input['class']]);

            $update = array('meta_value' => json_encode($installed_components, JSON_UNESCAPED_SLASHES));
            $update_where = array('meta_key' => 'installed_components');
            $db->update('site_meta', $update, $update_where);

            $activated_components = json_decode($site->activated_components, true);
            unset($activated_components[$input['class']]);

            $update = array('meta_value' => json_encode($activated_components, JSON_UNESCAPED_SLASHES));
            $update_where = array('meta_key' => 'activated_components');
            $db->update('site_meta', $update, $update_where);

            $preloaded_components = json_decode($site->preloaded_components, true);
            unset($preloaded_components[$input['class']]);

            $update = array('meta_value' => json_encode($preloaded_components, JSON_UNESCAPED_SLASHES));
            $update_where = array('meta_key' => 'preloaded_components');
            $db->update('site_meta', $update, $update_where);

            // remove from enabled minions list
            if ($input['parent'] != "Components") {

                $minions = 'enabled_' . strtolower($input['parent']);

                $enabled_minions = json_decode($site->$minions, true);
                unset($enabled_minions[$input['class']]);

                if (!empty($enabled_minions)) {

                    $update = array('meta_value' => json_encode($enabled_minions, JSON_UNESCAPED_SLASHES));
                    $update_where = array('meta_key' => $minions);
                    $db->update('site_meta', $update, $update_where);

                } else {

                    // delete if empty
                    $where = array('meta_key' => $minions);
                    $db->delete('site_meta', $where);

                }

            }

            // delete configuration record if it exists
            $where = array('meta_key' => $input['class']);
            $db->delete('site_meta', $where);

            echo json_encode(array('response' => 'success', 'procedure' => 'delete', 'action' => 'reload', 'message' => 'Component Deleted'));
            return;
        }

        echo json_encode(array('response' => 'error', 'procedure' => 'delete', 'action' => 'reload', 'message' => 'Unknown Procedure'));
        return;

    }

    /**
     * add configuration data for component to site_meta table
     */
    protected function configure($input) {

        $component = $input['component'];
        unset($input['type'], $input['component']);

        global $vce;
        $user = $vce->user;
        $vector = $user->create_vector();
        $config = $user->encryption(json_encode($input), $vector);

        $site = $vce->site;

        $db = $vce->db;

        if (isset($site->$component)) {

            $update = array('meta_value' => $config, 'minutia' => $vector);
            $update_where = array('meta_key' => $component);
            $db->update('site_meta', $update, $update_where);

        } else {

            // created
            $records[] = array(
                'meta_key' => $component,
                'meta_value' => $config,
                'minutia' => $vector,
            );

            $db->insert('site_meta', $records);

        }

        echo json_encode(array('response' => 'success', 'procedure' => 'update', 'action' => 'reload', 'message' => 'Configuration Saved'));
        return;

    }

    /**
     * permissions for component
     */
    protected function permissions($input) {

        global $vce;
        $db = $vce->db;
        $site = $vce->site;
        $user = $vce->user;

        $site_roles = json_decode($site->roles, true);

        $component = $input['component'];
        unset($input['type'], $input['component']);

        $permissions_list = array();
        // add permissions to this component
        foreach ($input as $each_key => $each_value) {
            // remove the underscore and number from checkbox name
            $permissions_list[$each_value][] = preg_replace('/_\d+$/', '', $each_key);
        }

        foreach ($site_roles as $role_id => $role_values) {

            // unset component permissions before adding them back
            if (isset($site_roles[$role_id]['permissions'][$component])) {
                // clear current permissions for this component
                unset($site_roles[$role_id]['permissions'][$component]);
            }

            if (isset($permissions_list[$role_id])) {
                $site_roles[$role_id]['permissions'][$component] = implode(',', $permissions_list[$role_id]);
            }

            // clean up if empty
            if (empty($site_roles[$role_id]['permissions'])) {
                unset($site_roles[$role_id]['permissions']);
            }

        }

        $roles = json_encode($site_roles);

        $update = array('meta_value' => $roles);
        $update_where = array('meta_key' => 'roles');
        $db->update('site_meta', $update, $update_where);

        // reset site object
        $site->roles = $roles;

        // pass user id to masquerade as
        $user->make_user_object($user->user_id);

        echo json_encode(array('response' => 'success', 'procedure' => 'update', 'action' => 'reload', 'message' => 'Permissions Saved'));
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
