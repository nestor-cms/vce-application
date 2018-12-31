<?php

class ManageMenus extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Manage Menus',
			'description' => 'Create, edit, and delete menues for site navigation',
			'category' => 'admin'
		);
	}
	
	
	/**
	 *
	 */
	public function as_content($each_component, $vce) {
			
		$menu_name = isset($_POST['menu_name']) ? $_POST['menu_name'] : null;
		
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
		$vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'manage-menu-style');

		$dossier = $vce->generate_dossier(array('type' => 'ManageMenus','procedure' => 'create'));
		
		$dossier_for_edit = $vce->generate_dossier(array('type' => 'ManageMenus','procedure' => 'update'));

		$dossier_for_delete  = $vce->generate_dossier(array('type' => 'ManageMenus','procedure' => 'delete'));
		
$content = <<<EOF
<div class="clickbar-container">
<div class="clickbar-content
EOF;

		if (isset($menu_name)) {
			$content .= ' clickbar-open';
		}

$content .= <<<EOF
">
<div class="sort-block left-block">
<div class="sort-block-title">Pages</div>
<div class="dd" id="nestable">
<ol class="dd-list">
EOF;

		$roles = json_decode($vce->site->roles, true);
		
		// add public to the roles list
		$roles['x'] = "Public";
		
		// get installed components
		$activated_components = json_decode($vce->site->activated_components, true);
		
		ksort($activated_components);
		
		$in_values = array();
		
		foreach ($activated_components as $component_type=>$component_path) {
		
			// require each and every component
			require_once(BASEPATH . $component_path);
		
			$check = new $component_type;
		
			$elements = $check->recipe_fields(null);
	
			// if we do not find an input for url withing the recipe element, then continue to the next
			if (strpos($elements,'name="url"') === false) {
				continue;	
			}
			
			$in_values[] = "'" . $component_type . "'";
			
		}
		
	
		// get all urls
		$query = "SELECT component_id, url FROM " . TABLE_PREFIX . "components WHERE component_id IN (SELECT component_id FROM " . TABLE_PREFIX . "components_meta WHERE meta_value IN (" . implode(',', $in_values) . "))";
		$urls = $vce->db->get_data_object($query);
		
		// get installed components
		// $activated_components = json_decode($site->activated_components, true);

		foreach ($urls as $each_url) {
		
			// FIX: needed to hack this now and limit to only two / /  in order to prevent huge load time.	
			
			// get the url depth
			$url_depth = ($each_url->url != "/") ? substr_count($each_url->url,'/') : 0;
				
			if ($url_depth > 2) {
				continue;
			}
			
			// was component created on recipe save? If so, then show in Pages
			if (isset($each_url->url)) {

				// get the component title
				$query = "SELECT meta_value AS title FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $each_url->component_id . "' AND meta_key='title'";
				$title = $vce->db->get_data_object($query);

				$each_url->title = isset($title[0]->title) ? $title[0]->title : null;
			
				// Anonymous function to get role access
				$get_role_access = function($id) use (&$get_role_access) {
			
					global $db;
			
					// get role_access
					$query = "SELECT * FROM " . TABLE_PREFIX . "components_meta WHERE component_id='" . $id . "' AND meta_key='role_access'";
					$access = $db->get_data_object($query);
		
					if (isset($access[0]->meta_value)) {
				
						// found role access and returning value
						return $access[0]->meta_value;
			
					} else {
				
						// get parent id of current component
						$query = "SELECT parent_id FROM " . TABLE_PREFIX . "components WHERE component_id='" . $id . "'";
						$parent = $db->get_data_object($query);
			
						if (isset($parent[0]->parent_id)) {
							// recursive call to anonymous function
							return $get_role_access($parent[0]->parent_id);
						} else {
							// as a default, if no role_access is found, return all roles
							global $site;
							return implode('|', array_keys(json_decode($site->roles, true)));
						}
			
					}
			
				};
			
				$role_access = $get_role_access($each_url->component_id);
			
				$each_url->role_access = $role_access;
				
				// get the url depth
				$url_depth = ($each_url->url != "/") ? substr_count($each_url->url,'/') : 0;
				
				$depth[$url_depth] = true;

$content .= <<<EOF
<li class="dd-item depth_all depth_$url_depth" referrer="$each_url->component_id" unique-id="$each_url->component_id" data-id="$each_url->component_id" data-url="$each_url->url" data-title="$each_url->title" data-role_access="$each_url->role_access">
<div class="dd-handle dd3-handle">&nbsp;</div><div class="dd-content"><div class="dd-title">$each_url->title</div><div class="dd-toggle"></div>
<div class="dd-content-extended">
<label>
<input type="text" name="title" value="$each_url->title" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
<label>
<input type="text" name="url" value="$each_url->url" autocomplete="off">
<div class="label-text">
<div class="label-message">URL</div>
<div class="label-error">Enter a URL</div>
</div>
</label>
<label for="">
<div class="input-padding">
EOF;

				foreach ($roles as $key=>$value) {
				
					// allow both simple and complex role definitions
					$role_name = is_array($value) ? $value['role_name'] : $value;

					$content .= '<label class="ignore"><input type="checkbox" name="role_access" value="' . $key . '"';
					if (in_array($key,explode('|',$role_access))) {
						$content .= ' checked="checked"';
					}
					$content .= '>  ' . $role_name . '</label> ';
				}

$content .= <<<EOF
</div>
<div class="label-text">
<div class="label-message">Who Can View This?</div>
<div class="label-error">Must have roles</div>
</div>
</label>
<label>
<div class="input-padding">
<label class="ignore"><input type="checkbox" name="target" value="new_window"> new window</label>
</div>
<div class="label-text">
<div class="label-message">Target</div>
</div>
</label>
<button data-action="remove" type="button">Remove</button>
</div></div>
</li>
EOF;
		
			}
		
		}

	
$content .= <<<EOF
</ol>
<br>
<div class="clickbar-container">
<div class="clickbar-content clickbar-open">
EOF;

		// alpha sort of categories
		ksort($depth);

		//
		foreach ($depth as $depth_key=>$depth_value) {
			$content .= '<button class="depth-display';
			if ($depth_key == 0) {
				$content .= ' highlight';
			}
			$content .= '" category="' . $depth_key . '">' . $depth_key . '</button>';
		}

$content .= <<<EOF
</div>
<div class="clickbar-title"><span>Display By Depth</span></div>
</div>
</div>
</div>
<div class="sort-block right-block">
<div class="sort-block-title">Menu</div>
<div class="dd" id="nestable2">
EOF;

		if (isset($menu_name)) {

			$site_menus = json_decode($vce->site->site_menus,true);

			// call to recursive function
			$content .= '<ol class="dd-list">';
			$content .= self::cycle_though_recipe($menu_name, $site_menus[$menu_name]);
			$content .= '</ol>';

		} else {

			// empty
			$content .= '<div class="dd-empty"></div>';

		}

$content .= <<<EOF
</div>
<form id="create_sets" class="recipe-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier">
<div class="recipe-info" style="clear:both">
<label>
<input type="text" name="menu_name" value="$menu_name" tag="required">
<div class="label-text">
<div class="label-message">Menu Name</div>
<div class="label-error">Enter a Menu Name</div>
</div>
</label>
<input type="submit" value="Save This Menu">
</div>
</form>
</div>
</div>
<div class="clickbar-title
EOF;
		if (!isset($menu_name)) {
			$content .= ' clickbar-closed';
		}
$content .= <<<EOF
"><span>
EOF;

		if (!isset($menu_name)) {
			$content .= 'Create A New Menu';
		} else {
			$content .= 'Edit Menu';
		}

$content .= <<<EOF
</span></div>
</div>
EOF;

$content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content no-padding clickbar-open">
<table id="existing-menus" class="tablesorter">
<thead>
<tr>
<th></th>
<th>Name</th>
<th>Code For Theme</th>
<th></th>
</tr>
</thead>
EOF;
		
		foreach (json_decode($vce->site->site_menus, true) as $key=>$value) {
		
$content .= <<<EOF
<tr>
<td class="align-center">
<form method="post" action="$vce->site_url/$vce->requested_url">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="menu_name" value="$key">
<input type="submit" value="Edit">
</form>
</td>
<td>
$key
</td>
<td>
&lt;?php &#36;content->menu('$key'); ?&gt;
</td>
<td class="align-center">
<form id="menu_" class="delete-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="hidden" name="menu_name" value="$key">
<input type="submit" value="Delete">
</form>
</td>
</tr>
EOF;
		
		}
		
$content .= <<<EOF
</table>
</div>
<div class="clickbar-title"><span>Existing Menus</span></div>
</div>
EOF;

	$vce->content->add('main', $content);
	
	}
	

	
	/**
	 * recursive function
	 */
	private function cycle_though_recipe($menu_name, $site_menus) {
	
		global $site;
		$roles = json_decode($site->roles, true);
		
		// add public to the roles list
		$roles['x'] = "Public";
	
		$content = "";
	
		foreach ($site_menus as $each_item) {
	
			// create a copy
			$each_url = (object) $each_item;
				
			// unset components
			unset($each_url->components);
			
			// clean up role_access by remove duplicates
			$role_access = implode('|', array_unique(explode('|',$each_url->role_access)));
			
$content .= <<<EOF
<li class="dd-item" referrer="$each_url->id" unique-id="$each_url->id" data-id="$each_url->id" data-url="$each_url->url" data-title="$each_url->title" data-role_access="$role_access">
<div class="dd-handle dd3-handle">&nbsp;</div><div class="dd-content"><div class="dd-title">$each_url->title</div><div class="dd-toggle"></div>
<div class="dd-content-extended">

<label>
<input type="text" name="title" value="$each_url->title" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>

<label>
<input type="text" name="url" value="$each_url->url" autocomplete="off">
<div class="label-text">
<div class="label-message">URL</div>
<div class="label-error">Enter a URL</div>
</div>
</label>

<label for="">
<div class="input-padding">
EOF;

			foreach ($roles as $key=>$value) {
			
				// allow both simple and complex role definitions
				$role_name = is_array($value) ? $value['role_name'] : $value;

				$content .= '<label class="ignore"><input type="checkbox" name="role_access" value="' . $key . '"';
				
				if (in_array($key,array_unique(explode('|',$each_url->role_access)))) {
					$content .= ' checked="checked"';
				}
		
				$content .= '>  ' . $role_name . '</label> ';

			}

$content .= <<<EOF
</div>
<div class="label-text">
<div class="label-message">Who Can View This?</div>
<div class="label-error">Must have roles</div>
</div>
</label>

<label>
<div class="input-padding">
<label class="ignore"><input type="checkbox" name="target" value="new_window"
EOF;

			if (isset($each_url->target)){
				$content .= " checked";
			}

$content .= <<<EOF
new window</label>
</div>
<div class="label-text">
<div class="label-message">Target</div>
</div>
</label>

<button data-action="remove" type="button">Remove</button>
</div></div>
EOF;


			if (isset($each_item['components'])) {
	
				$content .= '<ol class="dd-list">';
	
				$content .= self::cycle_though_recipe($menu_name, $each_item['components']);
	
				$content .= '</ol></li>';
	
			} 
	
		}
	
		return $content;
	
	}

	
	/**
	 * Create a new recipe
	 */
	protected function create($input) {
	
		global $db;
		global $site;
		
		$site_menus = isset($site->site_menus) ? json_decode($site->site_menus,true) : array();
	
		$name = $input['menu_name'];
		
		// could prevent saving over a current menu_name: if (!isset($site_menus[$name])) {
		
			// create an associate array from the json object of recipe
			$menu_items = json_decode($input['json'], true);
		
			$site_menus[$name] = $menu_items;
		
			$update = array('meta_value' => json_encode($site_menus, JSON_UNESCAPED_SLASHES));
			$update_where = array('meta_key' => 'site_menus');
			$db->update('site_meta', $update, $update_where);
		
			echo json_encode(array('response' => 'success','message' => 'Menu saved','action' => ''));
			return;
	
		//}

	
	}
	
	
	
	/**
	 * Create a new recipe
	 */
	protected function delete($input) {
	
		global $db;
		global $site;
		
		$main_name = $input['menu_name'];
	
		$site_menus = isset($site->site_menus) ? json_decode($site->site_menus,true) : array();
	
		unset($site_menus[$main_name]);
	
		$update = array('meta_value' => json_encode($site_menus, JSON_UNESCAPED_SLASHES));
		$update_where = array('meta_key' => 'site_menus');
		$db->update('site_meta', $update, $update_where);
	
		echo json_encode(array('response' => 'success','message' => 'deleted'));
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