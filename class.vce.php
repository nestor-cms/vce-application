<?php
/**
 * The class that creates the foundation object within Nestor
 *
 * notes: Nothing from class.db.php, class.user.php needs moving over
 */
class VCE {

	/**
	 * Create encrypted dossier and return it as a string
	 * This is a shortcut method for the following
	 * $user->encryption(json_encode(array('type' => 'ManageSite','procedure' => 'update')),$user->session_vector);
	 *
	 * @param object $dossier_elements
	 * @return string
	 */
	public function generate_dossier($dossier_elements_input) {
	
		// cast to array
		$dossier_elements = (array) $dossier_elements_input;
	
		// clean-up nulls and any empty array
		foreach ($dossier_elements as $dossier_name=>$dossier_value) {
			if (is_null($dossier_value) || count($dossier_value) < 1) {
				unset($dossier_elements[$dossier_name]);
			}
		}
		
		// encrypt dossier with session_vector for user
		return $this->user->encryption(json_encode($dossier_elements),$this->user->session_vector);
	
	}

	/**
	 * A method to check component specific permissions
	 *
	 * @param string $permission_name
	 * @param string $component_name
	 * @return bool
	 */
	public function check_permissions($permission_name, $component_name = null) {
	
		global $vce;

		// find the calling class by using debug_backtrace
		if (!$component_name) {
			$backtrace = debug_backtrace(false, 2);
			$component_name = $backtrace[1]['class'];
		}
		// add permissions onto the component name
		$component_permissions = $component_name . '_permissions';
		if (in_array($permission_name, explode(',', $vce->user->$component_permissions))) {
			return true;
		}
		return false;
	}


	/**
	 * Sends mail using PHP mail function or transport agent
	 * @param array $attributes
	 * SITE_MAIL = false will prevent mail from being sent
	 * attributes to send to mail
	 *
	 * $attributes = array (
	 * 'from' => array('*email*', '*name*'),
	 * 'to' => array(
	 * array('*email*', '*name*'),
	 * array('*email*', '*name*')
	 * ),
	 * 'subject' => '*subject*',
	 * 'message' => '*copy*'
	 * );
	 *
	 * @return notice of mail failure or silent success
	 */
	public function mail($attributes) { 
	
		if (!defined('SITE_MAIL') || SITE_MAIL == true) {
		
			global $vce;
	
			// load hooks
			// site_mail_transport
			if (isset($vce->site->hooks['site_mail_transport'])) {
				foreach($vce->site->hooks['site_mail_transport'] as $hook) {
					$status = call_user_func($hook, $vce, $attributes);
				}
			} else {
		
				// PHP mail function
				// http://php.net/manual/en/function.mail.php
	
				// create a new object
				$mail = new stdClass();
		
				foreach ($attributes as $key=>$value) {
					if (is_array($value)) {
						$each_values = array_values($value);
						if (is_array($each_values[0])) {
							foreach ($each_values as $sub_key=>$sub_value) {
								$address = isset($sub_value[0]) ? $sub_value[0] : null;
								$name = isset($sub_value[1]) ? $sub_value[1] : null;
								// call
								$mail->$key .= trim($name)  . ' <' . $address . '>,';
							}
						} else {
							$address = isset($each_values[0]) ? $each_values[0] : null;
							$name = isset($each_values[1]) ? $each_values[1] : null;
							// call
							$mail->$key = trim($name)  . ' <' . $address . '>';
						}
					} else {
						$mail->$key = trim($value);
					}
				}
				
				if (isset($attributes['html'])) {
					// To send HTML mail, the Content-type header must be set
					$headers[] = 'MIME-Version: 1.0';
					$headers[] = 'Content-type: text/html; charset=iso-8859-1';
					$mail->message = html_entity_decode(stripcslashes($mail->message));
				}

				// array to translate methods from vce to mail function
				$translate = array(
				'from' => 'From',
				'to' => 'To',
				'reply' => 'Reply-To',
				'cc' => 'Cc',
				'bcc' => 'Bcc'
				);

				// create header
				foreach ($translate as $input=>$output) {
					if (isset($mail->$input) && $input != 'to') {
						$headers[] = $output . ': ' . trim($mail->$input,",");
					}
				}

				// PHP mail function
				mail(trim($mail->to,","), $mail->subject, $mail->message, implode("\r\n", $headers));

			}
		
			return true;
		
		} else {
		
			return false;
		
		}
		
	}


	/**
	 * sort an object or array by associative key
	 *
	 * @param object/array $data
	 * @param string $key
	 * @param string $order
	 * @param string $type
	 * @return object/array
	 */
	public static function sorter($data, $key='title', $order='asc', $type='string') {
	
		usort($data, function($a, $b) use ($key, $order, $type) {
			// check if this is an object or an array
			if (is_object($a)) {
				$a_sort = isset($a->$key) ? $a->$key : null;
			} else {
				$a_sort = isset($a[$key]) ? $a[$key] : null;
			}
			if (is_object($b)) {
				$b_sort = isset($b->$key) ? $b->$key : null;
			} else {
				$b_sort = isset($b[$key]) ? $b[$key] : null;
			}
			if (isset($a_sort) && isset($b_sort)) {
				// sort as string
				if ($type == 'string') {
					if ($order == "asc") {
						return (strcmp($a_sort, $b_sort) > 0) ? 1 : -1;
					} else {
						return (strcmp($a_sort, $b_sort) > 0) ? -1 : 1;
					}
				} elseif ($type == 'time') {
					// sort as time
					if ($order == "asc") {
						return strtotime($a_sort) > strtotime($b_sort) ? 1 : -1;
					} else {
						return strtotime($a_sort) > strtotime($b_sort) ? -1 : 1;
					}
				} elseif ($type == 'integer') {
					// sort as time
					if ($order == "asc") {
						return (integer)$a_sort > (integer)$b_sort ? 1 : -1;
					} else {
						return (integer)$a_sort > (integer)$b_sort ? -1 : 1;
					}
				} else {
					return 1;
				}
			} else {
				return 1;
			}
		});
		// return the sorted object/array
		return $data;
	}


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
	 * $site->create_datalist($attributes);
	 * @param array $attributes
	 * @global object $db
	 * @return int $datalist_id
	 */
	public function create_datalist($attributes) {
	
		// todo: add a flag that would be checked to make sure we don't create a duplicate

		global $vce;
	
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
		'sequence' => 0
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
		foreach ($aspects as $aspect_key=>$aspect_value) {
			$aspects_records[] = array(
			'datalist_id' => $new_datalist_id,
			'meta_key' => $aspect_key,
			'meta_value' => $aspect_value,
			'minutia' => null
			);
		}

		$vce->db->insert('datalists_meta', $aspects_records);
		
		// if items need to be created
		if (isset($attributes['items'])) {
			// pass datalist_id that was created and items
			self::insert_datalist_items(array('datalist_id' => $new_datalist_id, 'items' => $attributes['items'] ));
		}
		
		// return the id for the datalist
		return $new_datalist_id;
	}
	
	/**
	 * Cycles though items in attributes and call to add_datalist_item function to add each item.
	 * @example
	 * $attributes = array (
	 * 'datalist_id' => '1',
	 * 'items' => array ( array ('key' => 'value', 'key' => 'value' ) )
	 * );
	 * 
	 * $site->insert_datalist_items($attributes);
	 * @param array $attributes
	 * @return inserts items into datalist
	 */
	public function insert_datalist_items($attributes) {
	
		foreach ($attributes['items'] as $sequence=>$each_item) {
		
			$input = array();
		
			// datalist_id
			$input['datalist_id'] = $attributes['datalist_id'];
			
			// sequence
			$input['sequence'] = ($sequence + 1);
		
			// meta data at current level
			$this_item = $each_item;
			unset($this_item['items']);
			
			foreach ($this_item as $key=>$value) {
			
				$input[$key] = $value;
			
			}
			
			// call to function to add the datalist item
			$new_datalist_id = self::add_datalist_item($input);
					
			if (isset($each_item['items'])) {
				
				// make a copy and then change datalsit_id and items
				$this_attributes = $attributes;
				$this_attributes['datalist_id'] = $new_datalist_id;
				$this_attributes['items'] = $each_item['items'];	

				self::insert_datalist_items($this_attributes);
			
			}
		}	
	}


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
	 public function add_datalist_item($input) {
	 
		 global $vce;
	 
		// get meta_data associated with datalist_id
		$query = "SELECT meta_key, meta_value FROM " . TABLE_PREFIX . "datalists_meta WHERE datalist_id='"  . $input['datalist_id'] . "'";
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
		'sequence' => $sequence
		);
		
		$item_id = $vce->db->insert('datalists_items', $records);

		// add key value pairs
		foreach ($input as $key=>$value) {
		
			$add_items_meta[] = array(
			'item_id' => $item_id,
			'meta_key' => $key,
			'meta_value' => $value,
			'minutia' => null
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
			'user_id' => null
			);
		
			// get the id of the insert
			$new_datalist_id = $vce->db->insert('datalists', $add_lists)[0];
			
			unset($add_meta, $datalist->datalist, $datalist->hierarchy);
		
			foreach ($datalist as $key=>$value) {
			
				$add_meta[] = array(
				'datalist_id' => $new_datalist_id,
				'meta_key' => $key,
				'meta_value' => $value,
				'minutia' => null
				);
			
			}
			
			if ($hierarchy) {
				$add_meta[] = array(
				'datalist_id' => $new_datalist_id,
				'meta_key' => 'hierarchy',
				'meta_value' => json_encode($hierarchy),
				'minutia' => null
				);
			}
		
			$vce->db->insert('datalists_meta', $add_meta);

		}
			
		// return new datalist_id if it exists		
		return isset($new_datalist_id) ? $new_datalist_id : $item_id;

	 }
	 
	 
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
	 * $site->update_datalist($attributes);
	 *
	 * @global object $db
	 * @return updates the datalist
	 */
	public function update_datalist($attributes) {
	
		global $vce;

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
		
		foreach (array('parent_id','item_id','component_id','user_id','sequence') as $each_update) {
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
			foreach ($attributes['meta_data'] as $key=>$value) {		
				$update = array('meta_value' => $value);
				$update_where = array($where_key => $where_value,'meta_key' => $key);
				$vce->db->update('datalists_meta', $update, $update_where);
			}
		}
		
		return true;
		
	}
	
	
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
	 * $site->update_datalist_list($attributes);
	 *
	 * @global object $db
	 * @return updates the datalist
	 */
	public function update_datalist_item($attributes) {
	
		global $vce;
		
		if (!isset($attributes['item_id'])) {
			// no identifier found
			return false;
		}
	
		foreach (array('datalist_id','sequence') as $each_update) {
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
			foreach ($attributes['meta_data'] as $key=>$value) {		
				$update = array('meta_value' => $value);
				$update_where = array('item_id' => $attributes['item_id'],'meta_key' => $key);
				$vce->db->update('datalists_items_meta', $update, $update_where);
			}
		}
			
		return true;

	}
	
	
	/**
	 * Removes datalist associated data 
	 * Removes data from datalist by: datalist, datalist_id, item_id
	 * @param array $attributes
	 * @global object $db
	 * @return removes datalist
	 */
	public function remove_datalist($attributes) {
	
		global $vce;
		
		// datalist is named, delete everything associated with that datalist including meta and items	
		if (isset($attributes['datalist']) && !isset($attributes['datalist_id']))  {
		
			// get all datalist_id associated with the datalist
			$query = "SELECT datalist_id FROM " . TABLE_PREFIX . "datalists_meta WHERE meta_key='datalist' AND meta_value='" . $attributes['datalist'] . "'";
			$datalist_ids = $vce->db->get_data_object($query);
			
			// cycle through results
			foreach ($datalist_ids as $each_datalist_id) {
				// send each datalist_id to search_and_destroy with 'all' items to be deleted
				self::extirpate_datalist('all', $each_datalist_id->datalist_id);
			}
			
		}
		
		// datalist_id is given, and if no item_id is set, then delete all items assocaited with the datalist_id
		if (isset($attributes['datalist_id']))  {
		
			// if no item_id, then delete all items associated with this datalist_id
			$item_id = isset($attributes['item_id']) ? $attributes['item_id'] : 'all';
			
			self::extirpate_datalist($item_id, $attributes['datalist_id']);
		
		}
		
		// item_id is given, and if no datalist_id is set, then just set it to null
		if (isset($attributes['item_id']))  {
		
			$datalist_id = isset($attributes['datalist_id']) ? $attributes['datalist_id'] : null;
			
			self::extirpate_datalist($attributes['item_id'], $datalist_id);
		
		}
		
	}
	
	
	/**
	 * Recursively deletes datalists
	 * {@internal}$item_id = "all" to remove everything}
	 * @param string $item_id
	 * @param $datalist_id
	 * @global object $db
	 * @return removes datalists
	 */
	private function extirpate_datalist($item_id, $datalist_id) {
	
		global $vce;
		
		// search for all item_id in datalist_items
		
		if ($item_id == "all") {
	
			// search for datalist associated with this item
			$query = "SELECT item_id FROM " . TABLE_PREFIX . "datalists_items WHERE datalist_id='"  . $datalist_id . "'";
			$items = $vce->db->get_data_object($query);
		
			foreach ($items as $each_item) {
				// recursive call for children
				self::extirpate_datalist($each_item->item_id, $datalist_id);
			}
		
			// delete from datalists where datalist_id = $datalist_id
			$where = array('datalist_id' => $datalist_id);
			$vce->db->delete('datalists', $where);
		
			// delete rows from datalists_meta where datalist_id =  $datalist_id
			$where = array('datalist_id' => $datalist_id);
			$vce->db->delete('datalists_meta', $where);
	
		} else {

			// search for datalist associated with this item
			$query = "SELECT datalist_id FROM " . TABLE_PREFIX . "datalists WHERE item_id='"  . $item_id . "'";
			$children = $vce->db->get_data_object($query);
	
			// if there is a datalist, then we have children
			if (isset($children[0]->datalist_id)) {
			
				// search for datalist associated with this item
				$query = "SELECT item_id FROM " . TABLE_PREFIX . "datalists_items WHERE datalist_id='"  . $children[0]->datalist_id . "'";
				$items = $vce->db->get_data_object($query);
		
				foreach ($items as $each_item) {
					// recursive call for children
					self::extirpate_datalist($each_item->item_id, $item_id);
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
		
	}
	
	
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
	public function get_datalist($attributes) {

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
			$query .= "WHERE " . TABLE_PREFIX . "datalists.user_id='" . $user_id . "' AND " . TABLE_PREFIX . "datalists.datalist_id IN (SELECT datalist_id FROM " . TABLE_PREFIX . "datalists_meta WHERE " . TABLE_PREFIX . "datalists_meta.meta_key='datalist' AND " . TABLE_PREFIX . "datalists_meta.meta_value='" .  $datalist . "')";
		} elseif (isset($datalist)) {
			$query .= "WHERE " . TABLE_PREFIX . "datalists.datalist_id IN (SELECT datalist_id FROM " . TABLE_PREFIX . "datalists_meta WHERE " . TABLE_PREFIX . "datalists_meta.meta_key='datalist' AND " . TABLE_PREFIX . "datalists_meta.meta_value='" .  $datalist . "')";
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
			foreach($not_requested as $each_not_requested) {
				// remove item from array
				unset($our_datalists[$each_not_requested]);
			}
		}
		
		// return datalists array
		return $our_datalists;
		
	}


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
	 * $site->get_datalist_items($attributes)
	 *
	 * @param array $attributes
	 * @global object $db
	 * @return array $options
	 */
	public function get_datalist_items($attributes) {
	
		global $vce;

		// options to search by
		if (isset($attributes['datalist_id'])) {
			$query = "SELECT * FROM " . TABLE_PREFIX . "datalists WHERE datalist_id='" . $attributes['datalist_id'] . "'";
		} elseif (isset($attributes['name'])) {
			$query = "SELECT * FROM " . TABLE_PREFIX . "datalists_meta WHERE meta_key='name' AND meta_value='" . $attributes['name'] . "'";
		} elseif (isset($attributes['parent_id']) && isset($attributes['item_id'])) {	
			$query = "SELECT " . TABLE_PREFIX . "datalists.* FROM " . TABLE_PREFIX . "datalists WHERE parent_id='" . $attributes['parent_id'] . "' AND item_id='" . $attributes['item_id'] . "'";
		} elseif (isset($attributes['user_id']) && isset($attributes['datalist'])) {
			$query = "SELECT " . TABLE_PREFIX . "datalists.* FROM " . TABLE_PREFIX . "datalists JOIN  " . TABLE_PREFIX . "datalists_meta ON " . TABLE_PREFIX . "datalists_meta.datalist_id=" . TABLE_PREFIX . "datalists.datalist_id WHERE " . TABLE_PREFIX . "datalists.user_id='" . $attributes['user_id'] . "' AND " . TABLE_PREFIX . "datalists_meta.meta_key='datalist' AND " . TABLE_PREFIX . "datalists_meta.meta_value='" .  $attributes['datalist'] . "'";
		} elseif (isset($attributes['datalist'])) {
			$query = "SELECT * FROM " . TABLE_PREFIX . "datalists WHERE datalist_id IN (SELECT datalist_id FROM " . TABLE_PREFIX . "datalists_meta WHERE meta_key='datalist' AND meta_value='" .  $attributes['datalist'] . "')";
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
		
			foreach ($datalist_info as $datalist_info_key=>$datalist_info_value) {
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
	
	}
	
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
	public function manage_cron_task($attributes) {
		
		// check for a action
		if (isset($attributes['action'])) {
			// move a single attribute to the first array element
			$attributes = array('0' => $attributes);
		}
		
		// check that attributes is not empty
		if (!empty($attributes)) {
		
			global $vce;
		
			foreach ($attributes as $key=>$each) {
				// add a cron_task
				if ($each['action'] == "add") {
		
					$data = array(
					'meta_key' => 'cron_task', 
					'meta_value' => $each['value'],
					'minutia' => $each['timestamp']
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
	
	}

	/**
	 * a function that converts to bytes
	 * @param string $size
	 * @return int of bytes
	 */
	public static function convert_to_bytes($size) {
		$size = strtolower($size);
		$bytes = (int) $size;
		preg_match('/\d+([a-z]+)/',$size,$matches);
		$unit = array(
		'k' => 1024,
		'kb' => 1024,
		'm' => 1024 * 1024,
		'mb' => 1024 * 1024,
		'g' => 1024 * 1024 * 1024,
		'gb' => 1024 * 1024 * 1024
		);
		
		if (isset($unit[$matches[1]])) {
			$bytes = intval($size) * $unit[$matches[1]];
		}
		
		return $bytes;
	}

	/**
	 * a function that converts from bytes to a nice readable size
	 * @param string $size
	 * @return int of bytes
	 */
	public static function convert_from_bytes($size) {
		$sz = 'BKMGTP';
		$decimals = 2;
		$factor = floor((strlen($size) - 1) / 3);
		$nice_size = sprintf("%.{$decimals}f", $size / pow(1024, $factor)) . @$sz[$factor];

		return $nice_size;
	}

	/**
	 * returns an ilkyo id, a 14 digit number, for any string
	 * like a pheonix from the ashes, ilkyo returns. Viva la ilkyo and special thanks to Mike Min.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function ilkyo($string) {
		// the argument is treated as an integer, and presented as an unsigned decimal number.
		sscanf(crc32($string), "%u", $front);
		// now in reverse
		sscanf(crc32(strrev($string)), "%u", $back);
		// return ilkyo id, which is 14 digits in length
		return $front . substr($back, 0, (14-strlen($front)));
	}

	/**
	 * Dumps JSON object into log file
	 * 
	 * @param string $var
	 * @return file_write of print_r(object)
	 */
	public function log($var, $file = "log.txt") {
		$basepath = defined('INSTANCE_BASEPATH') ? INSTANCE_BASEPATH : BASEPATH;
		file_put_contents($basepath . $file, json_encode($var) . PHP_EOL, FILE_APPEND);
	}

	/**
	 * Dumps array in a pre tag with a yellow background
	 * Outputs dump of whatever object is specified to the top of the browser window. 
	 * 
	 * @param string $var
	 * @param string $color
	 * @return string of print_r(object)
	 */
	public function dump($var, $color = 'ffc') {
		echo '<pre style="background:#' . $color  . ';">' . print_r($var, true) . '</pre>';
	}

	/**
	 * Allows for calling object properties from template pages in theme and then return or print them.
	 * @param string $name
	 * @param array $args
	 */
	public function __call($name, $args) {
		if (isset($this->$name)) {
			if ($args) {
				// return object property
				return $this->$name;
			} else {
				// print object property
				echo $this->$name;
			}
		} else {
			if (!VCE_DEBUG) {
				return false;
			} else {
				// print name of none existant component
				echo 'Call to non-existant property ' . '$' . strtolower(get_class()) . '->' . $name . '()'  . ' in ' . debug_backtrace()[0]['file'] . ' on line ' . debug_backtrace()[0]['line'];
			}
		}
	}

	/**
	 * Returns false instead of "Notice: Undefined property error" when reading data from inaccessible properties
	 */
	public function __get($var) {
		return false;
	}


}