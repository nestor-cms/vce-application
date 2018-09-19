<?php

class Comments extends Component {

    /**
     * basic info about the component
     */
    public function component_info() {
        return array(
            'name' => 'Comments',
            'description' => 'Allows for comments to be added.',
            'category' => 'site',
        );
    }

    /**
     * things to do when this component is preloaded
     */
    public function preload_component() {

        $content_hook = array(
            'page_build_content_callback' => 'Comments::page_build_content_callback',
        );

        return $content_hook;

    }

	/**
	 * Reorder comments based on video timestamp
	 *
	 * @param [array] $sub_components
	 * @param [object] $each_component
	 * @param [object] $vce
	 * @return the ordered components
	 */
    public static function page_build_content_callback($sub_components, $each_component, $vce) {

		foreach ($sub_components as $each_component) {
			if ($each_component->type == "Comments") {
				global $vce;
				$sub_components = $vce->sorter($sub_components, $key='timestamp', $order='asc', $type='integer');
				break;
			}
		}

        return $sub_components;
    }

    /**
     *
     */
    public function as_content($each_component, $vce) {

        //add javascript
        $vce->site->add_script(dirname(__FILE__) . '/js/script.js');

        //add stylesheet
        $vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'comments-style');

        $created_at = date("F j, Y, g:i a", $each_component->created_at);

        $site_url = defined('ASSETS_URL') ? ASSETS_URL : $vce->site->site_url;

        if (!empty($each_component->created_by)) {

            // send to function for user meta_data
            $user_info = $vce->user->get_users(array('user_ids' => $each_component->created_by));

            $name = $user_info[0]->first_name . ' ' . $user_info[0]->last_name;

            $user_image = $site_url . '/vce-application/images/user_' . ($each_component->created_by % 5) . '.png';

        } else {

            $name = "Anonymous";

            $user_image = $site_url . '/vce-application/images/user_1.png';

        }

        // convert line breaks
        $comment_text = nl2br($each_component->text, false);
        $comment_edit_text = $each_component->text;
        $timestamp = isset($each_component->timestamp) ? $each_component->timestamp : null;

        $content = <<<EOF
<div id="comment-$each_component->component_id" class="indent-comment">
<div class="comment-group-container">
<div class="comment-row">
<div class="user-image-comment"><img src="$user_image"></div>
<div class="comment-row-content arrow-box">
<p><span class="user-name-comment">$name</span><span class="comment-date">$created_at</span></p>
EOF;

        if ($timestamp) {

            $milliseconds = $timestamp;
            $seconds_full = floor($milliseconds / 1000);
            $seconds = sprintf("%02d", $seconds_full % 60);
            $minutes = str_replace('60', '00', sprintf("%02d", floor($seconds_full / 60)));
            $hours = floor($seconds_full / (60 * 60));

            $nice_timestamp = $hours . ':' . $minutes . ':' . $seconds;

            $content .= <<<EOF
<div class="comment-timestamp" timestamp="$timestamp">&#9654; $nice_timestamp</div>
EOF;

        }

        $content .= <<<EOF
<p class="comment-text">$comment_text</p>
EOF;

        if ($each_component->created_by == $vce->user->user_id || $vce->user->role_id == 1) {
            // normally this would be if ($page->can_edit($each_component)) {

            // the instructions to pass through the form with specifics
            $dossier = array(
                'type' => 'Comments',
                'procedure' => 'update',
                'component_id' => $each_component->component_id,
                'created_at' => $each_component->created_at,
                'title' => 'comment',
            );

            // add dossier, which is an encrypted json object of details uses in the form
            $dossier_for_update = $vce->generate_dossier($dossier);

            $content .= <<<EOF
<div class="update-form">
<form id="add_comments" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_update">
<label>
<textarea name="text" class="textarea-input comments-input" tag="required" placeholder="Enter Your Comment">$comment_edit_text</textarea>
<div class="label-text">
<div class="label-message">Comment</div>
<div class="label-error">You cannot post an empty comment</div>
</div>
</label>
<input type="submit" value="Update Comment"> <a href class="link-button update-form-cancel">Cancel</a>
</form>
</div>
EOF;

        }

        // the instructions to pass through the form with specifics
        $dossier = array(
            'type' => 'Comments',
            'procedure' => 'create',
            'parent_id' => $each_component->component_id,
            'title' => 'comment',
        );

        // add dossier, which is an encrypted json object of details uses in the form
        $dossier_for_create = $vce->generate_dossier($dossier);

        $content .= <<<EOF
<div class="reply-form">
<form id="add_comments" class="asynchronous-form" method="post" action="$vce->input_path" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_create">
<label>
<textarea name="text" class="textarea-input comments-input" tag="required" placeholder="Enter Your Comment"></textarea>
<div class="label-text">
<div class="label-message">Comment</div>
<div class="label-error">You cannot post an empty comment</div>
</div>
</label>
<input type="submit" value="Save Comment"> <a href class="link-button reply-form-cancel">Cancel</a>
</form>
</div>
<div class="comment-reply-delete">
EOF;

        if (!isset($page->prevent_sub_components)) {

            $content .= <<<EOF
<div class="link-inline reply-form-link">Reply</div>
EOF;

            if ((!empty($each_component->created_by) && $each_component->created_by == $vce->user->user_id) || $vce->user->role_id == 1) {
                // normally this would be if ($page->can_delete($each_component)) {

                // the instructions to pass through the form with specifics
                $dossier = array(
                    'type' => 'Comments',
                    'procedure' => 'delete',
                    'component_id' => $each_component->component_id,
                    'created_at' => $each_component->created_at,
                    'parent_url' => $vce->requested_url,
                );

                // add dossier, which is an encrypted json object of details uses in the form
                $dossier_for_delete = $vce->generate_dossier($dossier);

                $content .= <<<EOF
<div class="pipe"></div>
<div class="link-inline edit-form-link">Edit</div>
<div class="pipe"></div>
<div class="link-inline delete-comment" comment="$each_component->component_id" action="$vce->input_path" dossier="$dossier_for_delete">Delete</div>
EOF;

            }

        }

        $content .= <<<EOF
</div>
</div>
<div class="comment-group-container indent-comment">
</div>
</div>
</div>
EOF;

        // $each_component->parent_id
        $vce->content->add('main', $content);

    }

    /**
     *
     */
    public function as_content_finish($each_component, $vce) {

        // $each_component->parent_id
        $vce->content->add('main', '</div>');

    }

    public function add_component_finish($each_component, $vce) {

        // check for special property main_*parent_id*
        $add_component = 'main_' . $each_component->component_id;

        // If there are exiting comments at this level, then "Add Comments" would be placed under comments

        if (!empty($vce->content->$add_component)) {
            $vce->content->add('main', $vce->content->$add_component);
        }

    }

    /**
     *
     */
    public function add_component($recipe_component, $vce) {
        // If no comments have been created, then the "Add Comments" block should be added to main.

        $location = 'main';

        // but if there are exiting comments, then "Add Comments" should go under comments.
        // to do this, we add "Add Components" block to a special property main_*parent_id*
        // and then add it to main within the as_content_finish function

        if (isset($recipe_component->sibling_components)) {

            $existing_comments = false;

            foreach ($recipe_component->sibling_components as $each_sibling_component) {
                if ($each_sibling_component->type == "Comments") {
                    $existing_comments = true;
                    break;
                }
            }

            if ($existing_comments) {
                $location = 'main_' . $recipe_component->parent_id;
            }

        }

        // create dossier
        $dossier_for_create = $vce->generate_dossier($recipe_component->dossier);

        // add javascript to page
        $vce->site->add_script(dirname(__FILE__) . '/js/script.js');

        //add stylesheet
        $vce->site->add_style(dirname(__FILE__) . '/css/style.css', 'comments-style');

        // user name
        $user_name = $vce->user->first_name . ' ' . $vce->user->last_name;

        $site_url = defined('ASSETS_URL') ? ASSETS_URL : $vce->site->site_url;

        // user image
        $user_image = $site_url . '/vce-application/images/user_' . ($vce->user->user_id % 5) . '.png';

        $content = <<<EOF
<div id="combar-$recipe_component->parent_id" class="clickbar-container add-container ignore-admin-toggle">
<div class="clickbar-content">
<form id="add_comments" class="asynchronous-comment-form" method="post" action="$vce->input_path" combar="combar-$recipe_component->parent_id" autocomplete="off">
<input type="hidden" name="dossier" value="$dossier_for_create">
<input type="hidden" name="title" value="comment">
<label>
<textarea name="text" class="textarea-input comments-input" tag="required" placeholder="Enter Your Comment"></textarea>
<div class="label-text">
<div class="label-message">Comment</div>
<div class="label-error">You cannot post an empty comment</div>
</div>
</label>
<input type="submit" value="Add Comment">
</form>

<div id="comments-asynchronous-content" class="asynchronous-content" style="display:none">
	<div class="indent-comment">
	<div class="comment-group-container">
	<div class="comment-row">
		<div class="user-image-comment"><img src="$user_image"></div>
	<div class="comment-row-content arrow-box">
		<p><span class="user-name-comment">$user_name</span><span class="comment-date">{created-at}</span></p>
			<div class="comment-timestamp" timestamp="{timestamp}">&#9654; {nice-timestamp}</div>
		<p class="comment-text">{text}</p>
	<div class="comment-reply-delete"><div class="comment-reload">&#8635;</div></div>
	</div>
	</div>
	</div>
	</div>
</div>

<div class="comment-remote-container remote-container">
	<div class="clickbar-container add-container ignore-admin-toggle">
	<div class="clickbar-content clickbar-open">
	<form id="add_comments_remote" class="asynchronous-comment-form" method="post" action="$vce->input_path" combar="combar-$recipe_component->parent_id" autocomplete="off">
	<input type="hidden" name="dossier" value="$dossier_for_create">
	<input type="hidden" name="title" value="comment">
	<label>
	<textarea name="text" class="textarea-input textarea-input-remote" tag="required" placeholder="Enter Your Comment"></textarea>
	<div class="label-text">
	<div class="label-message">Comment</div>
	<div class="label-error">You cannot post an empty comment</div>
	</div>
	</label>
	<input type="submit" value="Save Comment">
	</form>
	</div>
	<div class="play-clickbar clickbar-title disabled"><span>Add Comments</span></div>
	</div>
</div>

</div>
<div class="clickbar-title clickbar-closed comments-clickbar"><span>Add $recipe_component->title</span></div>
</div>
EOF;

        $vce->content->add($location, $content);

    }

    /**
     * Creates component
     * @param array $input
     * @return calls component's procedure or echos an error message
     */
    protected function create($input) {

        // call to create_component, which returns the newly created component_id
        $component_id = self::create_component($input);

        if ($component_id) {

            echo json_encode(array('response' => 'success', 'procedure' => 'create', 'action' => 'reload', 'message' => 'Created', 'component_id' => $component_id));
            return;

        }

        echo json_encode(array('response' => 'error', 'procedure' => 'update', 'message' => "Error"));
        return;

    }

    /**
     * for ManageRecipes class
     */
    public function recipe_fields($recipe) {

        global $site;

        $title = isset($recipe['title']) ? $recipe['title'] : self::component_info()['name'];

        $elements = <<<EOF
<label>
<input type="text" name="title" value="$title" tag="required" autocomplete="off">
<div class="label-text">
<div class="label-message">Title</div>
<div class="label-error">Enter a Title</div>
</div>
</label>
EOF;

        return $elements;

    }

}