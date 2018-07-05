<?php

class ManageUsers extends Component {

	/**
	 * basic info about the component
	 */
	public function component_info() {
		return array(
			'name' => 'Manage Users',
			'description' => 'Add, edit and delete site users. You can also masquerade as them using this component.',
			'category' => 'admin'
		);
	}
	
	
	/**
	 *
	 */
	public function as_content($each_component, $page) {
	
		global $db;
		
		// add javascript to page
		$page->site->add_script(dirname(__FILE__) . '/js/script.js', 'tablesorter');
		
		$page->site->add_style(dirname(__FILE__) . '/css/style.css');
		
		// check if value is in page object
		$user_id = isset($page->user_id) ? $page->user_id : null;
		
		//	get roles
		$roles = json_decode($page->site->roles, true);	
	
		// pagination vars
		$pagination_current = isset($page->pagination_current) ? $page->pagination_current : 1;
		$pagination_length = isset($page->pagination_length) ? $page->pagination_length : 10;
		$pagination_offset = ($pagination_current != 1) ? ($pagination_length * ($pagination_current - 1)) : 0;
		
		$filter_by = array();
		$paginate = true;
		
		// if value is set, disable pagination
		if (isset($page->search_results_edit)) {
			$paginate = false;
		}
		
		foreach ($page as $key=>$value) {
			if (strpos($key, 'filter_by_') !== FALSE) {
				$filter_by[str_replace('filter_by_', '', $key)] = $value;
				if ($key != 'filter_by_role_id') {
					$paginate = false;
				}
			}
		}
		
		if (isset($page->user_search_results)) {
		
			$site_users = json_decode($page->user_search_results);
			
			// set value to hide pagination next time around
			$page->site->add_attributes('search_results_edit',true);
			
		
		} else {
		
			// initialize array to store users
			$site_users = array();

			$query = "SELECT user_id, role_id, vector FROM " . TABLE_PREFIX . "users";
			if (isset($user_id)) {
				$query .= " WHERE user_id='" . $user_id . "'";
			} else if (isset($filter_by['role_id'])) {
				$query .= " WHERE role_id='" . $filter_by['role_id'] . "'";
			}

			$all_users = $db->get_data_object($query);
			
			$all_users_total = $all_users;
			
			// only paginate for role_id
			
			if ($paginate === true) {
				// use array_slice to limit users
				$all_users = array_slice($all_users, $pagination_offset, $pagination_length);
			}


			foreach ($all_users as $each_user) {
		
				// create array
				$user_object = array();
		
				// add the values into the user object	
				$user_object['user_id'] = $each_user->user_id;
				$user_object['role_id'] = $each_user->role_id;
			
				$query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE user_id='" . $each_user->user_id . "'  AND minutia=''";
				$metadata = $db->get_data_object($query);
			
				// look through metadata
				foreach ($metadata as $each_metadata) {

					//decrypt the values
					$value = user::decryption($each_metadata->meta_value, $each_user->vector);

					// add the values into the user object	
					$user_object[$each_metadata->meta_key] = $db->clean($value);		
				}
				
				// filter users by anything that is in filter_by
				foreach ($filter_by as $each_filte_key=>$each_filter_value) {
				
					if ($user_object[$each_filte_key] != $each_filter_value && $each_filte_key != 'role_id') {
						// skip to next,. one level up
						continue 2;
					}
				
				}
			
				// save into site_users array
				$site_users[$each_user->user_id] = (object) $user_object;

			}
		
		}
		
		// total number of users
		$pagination_total = isset($all_users_total) ? count($all_users_total) : count($site_users);
		$pagination_count = ceil($pagination_total / $pagination_length);
		

		if (isset($user_id)) {
			// update an exisiting user

			$user_info = $site_users[$user_id];
			
			$dossier_for_update = $page->user->encryption(json_encode(array('type' => 'ManageUsers','procedure' => 'update','user_id' => $user_id)),$page->user->session_vector);
			
			$first_name = isset($user_info->first_name) ? $user_info->first_name : null;
			$last_name = isset($user_info->last_name) ? $user_info->last_name : null;

$content = <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content clickbar-open">
<form id="form" class="asynchronous-form" method="post" action="$page->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
<label>
<div class="input-padding">
$user_info->email
</div>
<div class="label-text">
<div class="label-message">Email</div>
<div class="label-error">Enter your Email</div>
</div>
</label>
<label>
<input type="text" name="first_name" value="$first_name" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">First Name</div>
<div class="label-error">Enter a First Name</div>
</div>
</label>
<label>
<input type="text" name="last_name" value="$last_name" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Last Name</div>
<div class="label-error">Enter a Last Name</div>
</div>
</label>
EOF;

			// load hooks
			if (isset($page->site->hooks['user_attributes'])) {
				foreach($page->site->hooks['user_attributes'] as $hook) {
					$content .= call_user_func($hook, $user_info);
				}
			}

$content .= <<<EOF
<label>
<select name="role_id" tag="required">
<option value=""></option>
EOF;

			foreach (json_decode($page->site->roles, true) as $key => $value) {
				if ($page->user->user_role <= $key) {
					$role_name = is_array($value) ? $value['role_name'] : $value;
					$content .= '<label for=""><option value="' . $key . '"';
					if ($key == $user_info->role_id) {
						$content .= ' selected';
					}
					$content .= '>' . $role_name . '</option>';
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


		} else {
		// to create a new user
		
			$dossier_for_create = $page->user->encryption(json_encode(array('type' => 'ManageUsers','procedure' => 'create')),$page->user->session_vector);

$content = <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content">
<form id="form" class="asynchronous-form" method="post" action="$page->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_create">

<label>
<input type="text" name="email" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Email</div>
<div class="label-error">Enter Email</div>
</div>
</label>

<label>
<input type="text" name="password" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Password</div>
<div class="label-error">Enter your Password</div>
</div>
</label>

<label>
<input type="text" name="first_name" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">First Name</div>
<div class="label-error">Enter a First Name</div>
</div>
</label>

<label>
<input type="text" name="last_name" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Last Name</div>
<div class="label-error">Enter a Last Name</div>
</div>
</label>
EOF;

			// load hooks
			if (isset($page->site->hooks['user_attributes'])) {
				foreach($page->site->hooks['user_attributes'] as $hook) {
					$content .= call_user_func($hook, $content);
				}
			}

$content .= <<<EOF
<label>
<select name="role_id" tag="required">
<option value=""></option>
EOF;

			foreach ($roles as $key => $value) {
				// allow both simple and complex role definitions
				$role_name = is_array($value) ? $value['role_name'] : $value;
				$content .= '<label for=""><option value="' . $key . '">' . $role_name . '</option>';
				
			}
		
$content .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Role</div>
<div class="label-error">Enter your Role</div>
</div>
</label>
<input type="submit" value="Create User">
<div id="generate-password" class="link-button">Generate Password</div>
<div class="link-button cancel-button">Cancel</div>
</form>
</div>
<div class="clickbar-title clickbar-closed"><span>Create A New User</span></div>
</div>
</p>
EOF;

		}
		
		// dossier for search
		$dossier = array(
		'type' => 'ManageUsers',
		'procedure' => 'search'
		);

		// generate dossier
		$dossier_for_search = $page->generate_dossier($dossier);
		
		$clickbar_content = isset($page->search_value) ? 'clickbar-content clickbar-open' : 'clickbar-content';
		$clickbar_title = isset($page->search_value) ? 'clickbar-title' : 'clickbar-title clickbar-closed';
		$input_value = isset($page->search_value) ? $page->search_value : null;
	
$content .= <<<EOF
<div class="clickbar-container">
<div class="$clickbar_content">

<form id="search-users" class="asynchronous-form" method="post" action="$page->input_path" autocomplete="off">
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


		// only show if we are not editing search results
		if (!isset($page->user_id)) {

			$user_attributes_list = array('user_id','last_name','first_name','email');

			// load hooks
			if (isset($page->site->hooks['user_attributes_list'])) {
				foreach($page->site->hooks['user_attributes_list'] as $hook) {
					$user_attributes_list = call_user_func($hook, $user_attributes_list);
				}
			}

// list site users
$content .= <<<EOF
<p>
<div class="clickbar-container">
<div class="clickbar-content no-padding clickbar-open">
<div class="pagination">
EOF;

			// the instructions to pass through the form
			$dossier = array(
			'type' => 'ManageUsers',
			'procedure' => 'filter'
			);

			// add dossier, which is an encrypted json object of details uses in the form
			$dossier_for_filter = $page->generate_dossier($dossier);

$content .= <<<EOF
<label>
<select class="filter-form" name="role_id">
<option></option>
EOF;

			foreach ($roles as $role_id=>$each_role) {

				$content .= '<option value="' . $role_id . '"';
				// $filter_by_role_id = 3;
				if (isset($page->filter_by_role_id) && $role_id == $page->filter_by_role_id) {
					$content .= ' selected';
				}
				$content .= '>' . $each_role['role_name'] . '</option>';

			}

$content .= <<<EOF
</select>
<div class="label-text">
<div class="label-message">Filter By Site Roles</div>
</div>
</label>
EOF;

			// load hooks
			if (isset($page->site->hooks['user_attributes_filter'])) {
				foreach($page->site->hooks['user_attributes_filter'] as $hook) {
					$content .= call_user_func($hook, $filter_by, $content, $page);
				}
			}

$content .= <<<EOF
<div class="filter-form-submit link-button" dossier="$dossier_for_filter" action="$page->input_path" pagination="1">Filter</div>
EOF;

			if ($paginate === true) {

				// the instructions to pass through the form
				$dossier = array(
				'type' => 'ManageUsers',
				'procedure' => 'filter'
				);

				// add dossier, which is an encrypted json object of details uses in the form
				$dossier_for_filter = $page->generate_dossier($dossier);
		
				for ($x = 1;$x <= $pagination_count; $x++) {

					$class = ($x == $pagination_current) ? 'class="highlighted"': '';

		
$content .= <<<EOF
<form class="pagination-form inline-form" method="post" action="$page->input_path">
<input type="hidden" name="dossier" value="$dossier_for_filter">
EOF;

				foreach ($filter_by as $key=>$value) {
					$content .= '<input type="hidden" name="filter_by_' . $key . '" value="' . $value . '">';
				}

$content .= <<<EOF
<input type="hidden" name="pagination_current" value="$x">
<input $class type="submit" value="$x">
</form>
EOF;
		
				}
		
				$start = $pagination_offset + 1;
				$end = ($pagination_offset + $pagination_length) < $pagination_total ? $pagination_offset + $pagination_length : $pagination_total;
				$label_text = $start . ' - ' . $end . ' of ' . $pagination_total . ' total';

$content .= <<<EOF
$label_text
EOF;

				} else {

$content .= count($site_users) . ' total';

				}

$content .= <<<EOF
</div>
<table id="users" class="tablesorter">
<thead>
<tr>
<th></th>
<th></th>
<th></th>
<th>Site Role</th>
EOF;


			foreach ($user_attributes_list as $each_user_attribute) {

				$content .= '<th>' . ucwords(str_replace('_', ' ', $each_user_attribute)) . '</th>';

			}

$content .= <<<EOF
</tr>
</thead>
EOF;

		if (!empty($site_users)) {
			foreach ($site_users as $each_site_user) {
		
				// allow both simple and complex role definitions
				$user_role = is_array($roles[$each_site_user->role_id]) ? $roles[$each_site_user->role_id]['role_name'] : $roles[$each_site_user->role_id];
			
				if ($each_site_user->user_id == "1") {

$content .= <<<EOF
<tr>
<td></td>
<td></td>
<td></td>
<td>$user_role</td>
EOF;

				} else {
			
					$dossier_for_edit = $page->user->encryption(json_encode(array('type' => 'ManageUsers','procedure' => 'edit','user_id' => $each_site_user->user_id)),$page->user->session_vector);
					$dossier_for_masquerade = $page->user->encryption(json_encode(array('type' => 'ManageUsers','procedure' => 'masquerade','user_id' => $each_site_user->user_id)),$page->user->session_vector);
					$dossier_for_delete = $page->user->encryption(json_encode(array('type' => 'ManageUsers','procedure' => 'delete','user_id' => $each_site_user->user_id)),$page->user->session_vector);


$content .= <<<EOF
<tr>
<td class="align-center">
<form class="inline-form asynchronous-form" method="post" action="$page->input_path">
<input type="hidden" name="dossier" value="$dossier_for_edit">
<input type="hidden" name="pagination_current" value="$pagination_current">
<input type="submit" value="Edit">
</form>
</td>
<td class="align-center">
<form class="inline-form asynchronous-form" method="post" action="$page->input_path">
<input type="hidden" name="dossier" value="$dossier_for_masquerade">
<input type="submit" value="Masquerade">
</form>
</td>
<td class="align-center">
<form class="delete-form inline-form asynchronous-form" method="post" action="$page->input_path">
<input type="hidden" name="dossier" value="$dossier_for_delete">
<input type="submit" value="Delete">
</form>
</td>
<td>$user_role</td>
EOF;

				}
			
			
				foreach ($user_attributes_list as $each_user_attribute) {

					$content .= '<td>';
					if (isset($each_site_user->$each_user_attribute)) {
						$content .= $each_site_user->$each_user_attribute;
					}
					$content .= '</td>';

				}

$content .= <<<EOF
</tr>
EOF;

			}
		}
$content .= <<<EOF
</table>
</div>
<div class="clickbar-title disabled"><span>Users</span></div>
</div>
</p>
EOF;


		}

		$page->content->add('main', $content);
	
	
	}

	
	/**
	 * Create a new user
	 */
	protected function create($input) {

		global $db;
		global $site;
		
		// save input values to a new variable
		$hook_input = $input;
	
		// remove type so that it's not created for new user
		unset($input['type']);
	
		// test email address for validity
		$input['email'] = filter_var(strtolower($input['email']), FILTER_SANITIZE_EMAIL);
		if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
			echo json_encode(array('response' => 'error','message' => 'Email is not a valid email address','form' => 'create', 'action' => ''));
			return;
		}
		
		$lookup = user::lookup($input['email']);
		
		// check
		$query = "SELECT id FROM " . TABLE_PREFIX . "users_meta WHERE meta_key='lookup' and meta_value='" . $lookup . "'";
		$lookup_check = $db->get_data_object($query);
		
		if (!empty($lookup_check)) {
			echo json_encode(array('response' => 'error','message' => 'Email is already in use','form' => 'create', 'action' => ''));
			return;
		}
		
		// call to user class to create_hash function
		$hash = user::create_hash($input['email'], $input['password']);
		
		// get a new vector for this user
		$vector = user::create_vector();

		$user_data = array(
		'vector' => $vector, 
		'hash' => $hash,
		'role_id' => $input['role_id']
		);
		$user_id = $db->insert('users', $user_data);
		
		unset($input['procedure']);
		unset($input['password']);
		unset($input['role_id']);
				
		// now add meta data

		$records = array();
				
		$lookup = user::lookup($input['email']);
		
		$records[] = array(
		'user_id' => $user_id,
		'meta_key' => 'lookup',
		'meta_value' => $lookup,
		'minutia' => 'false'
		);
		
		foreach ($input as $key => $value) {

			// encode user data			
			$encrypted = user::encryption($value, $vector);
			
			$records[] = array(
			'user_id' => $user_id,
			'meta_key' => $key,
			'meta_value' => $encrypted,
			'minutia' => null
			);
			
		}
		
		$db->insert('users_meta', $records);
		
		// add user_id and other values to hook input
		$hook_input['user_id'] = $user_id;
		$hook_input['lookup'] = $lookup;
		$hook_input['vector'] = $vector;
		
		// load hooks
		if (isset($site->hooks['manage_user_create'])) {
			foreach($site->hooks['manage_user_create'] as $hook) {
				call_user_func($hook, $hook_input);
			}
		}

		echo json_encode(array('response' => 'success','message' => 'User has been created','form' => 'create','action' => ''));
		return;
	}

	/**
	 * edit user
	 */
	protected function edit($input) {

		// add attributes to page object for next page load using session
		global $site;
		
		$site->add_attributes('user_id',$input['user_id']);
				
		$site->add_attributes('pagination_current',$input['pagination_current']);
	
		
		echo json_encode(array('response' => 'success','message' => 'session data saved', 'form' => 'edit'));
		return;
		
	}

	/**
	 * update user
	 */
	protected function update($input) {
	
		global $db;
		global $site;
	
		// load hooks
		if (isset($site->hooks['manage_user_update'])) {
			foreach($site->hooks['manage_user_update'] as $hook) {
				call_user_func($hook, $input);
			}
		}
	
		$user_id = $input['user_id'];
	
		$query = "SELECT role_id, vector FROM " . TABLE_PREFIX . "users WHERE user_id='" . $user_id . "'";
		$user_info = $db->get_data_object($query);
		
		$role_id = $user_info[0]->role_id;
		$vector = $user_info[0]->vector;
		
		// has role_id been updated?
		if ($input['role_id'] != $role_id) {

			$update = array('role_id' => $input['role_id']);
			$update_where = array('user_id' => $user_id);
			$db->update('users', $update, $update_where );

		}
		
		// clean up
		unset($input['type'],$input['procedure'],$input['role_id'],$input['user_id']);
		
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
			
			$records[] = array(
			'user_id' => $user_id,
			'meta_key' => $key,
			'meta_value' => $encrypted,
			'minutia' => null
			);
			
		}
		
		$db->insert('users_meta', $records);
				
		echo json_encode(array('response' => 'success','message' => 'User Updated','form' => 'create','action' => ''));
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
		
		echo json_encode(array('response' => 'success','message' => 'User masquerade','form' => 'masquerade','action' => $site->site_url));
		return;
	
	}	
	
	
	/**
	 * Delete a user
	 */
	protected function delete($input) {
	
		global $db;
		global $site;
	
		// load hooks
		if (isset($site->hooks['manage_user_delete'])) {
			foreach($site->hooks['manage_user_delete'] as $hook) {
				call_user_func($hook, $input);
			}
		}
	
		// delete user from database
		$where = array('user_id' => $input['user_id']);
		$db->delete('users', $where);
		
		// delete user from database
		$where = array('user_id' => $input['user_id']);
		$db->delete('users_meta', $where);
		
		echo json_encode(array('response' => 'success','message' => 'User has been deleted','form' => 'delete','user_id' => $input['user_id'] ,'action' => ''));
		return;
	
	}
	
	/**
	 * Filter
	 */
	protected function filter($input) {
	
		global $site;
		
		foreach ($input as $key=>$value) {
			if (strpos($key, 'filter_by_') !== FALSE) {
				$site->add_attributes($key,$value);
			}
		}
		
		$site->add_attributes('pagination_current',$input['pagination_current']);
	
		echo json_encode(array('response' => 'success','message' =>'Filter'));
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
			echo json_encode(array('response' => 'success','results' => null));
			return;
		}
		
		// break into array based on spaces
		$search_values = explode('|',preg_replace('/\s+/','|',$input['search']));

		// create the IN
		$role_id_in = "";
		foreach (json_decode($site->roles, true) as $key=>$value) {
			if ($key >= $user->role_id) {
				$role_id_in .= $key . ',';
			}
		}
		$role_id_in = rtrim($role_id_in,',');		
		
		// get all users of specific roles as an array
		$query = "SELECT user_id, role_id, vector FROM " . TABLE_PREFIX . "users WHERE role_id IN (" . $role_id_in . ")";
		$find_users_by_role = $db->get_data_object($query, 0);
		

		// cycle through users
		foreach ($find_users_by_role as $key=>$value) {
			// add user_id to array for the IN contained within database call
			$users_id_in[] = $value['user_id'];
			// and these other values
			$all_users[$value['user_id']]['user_id'] = $value['user_id'];
			$all_users[$value['user_id']]['role_id'] = $value['role_id'];
			$all_users[$value['user_id']]['vector'] = $value['vector'];
			// set for search
			$match[$value['user_id']] = 0;
		}
		
		if (!isset($users_id_in)) {
			echo json_encode(array('response' => 'success','results' => null));
			return;
		}

		$query = "SELECT * FROM " . TABLE_PREFIX . "users_meta WHERE minutia='' AND user_id IN (" . implode(",",$users_id_in) . ")";
		$users_meta_data = $db->get_data_object($query, 0);
		
		foreach ($users_meta_data as $key=>$value) {
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
		foreach ($match as $match_user_id=>$match_user_value) {
			// unset vector
			unset($all_users[$match_user_id]['vector']);
			// if there are fewer than count, then unset
			if ($match_user_value < count($search_values)) {
				// unset user info if the count is less than the total
				unset($all_users[$match_user_id]);
			}
		}
		
		if (count($all_users)) {
			
			$site->add_attributes('search_value',$input['search']);
			$site->add_attributes('user_search_results',json_encode($all_users));
		
			echo json_encode(array('response' => 'success', 'form' => 'edit'));
			return;
		}
		
		$site->add_attributes('search_value',$input['search']);
		$site->add_attributes('user_search_results', null);
		
		echo json_encode(array('response' => 'success','form' => 'edit'));
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