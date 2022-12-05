<?php

/**
 * Guest Author class definition
 *
 * @package guest-author
 * @since 1.0
 */

if (!class_exists('BS_Guest_Author')) :

    class BS_Guest_Author
    {


        function __construct()
        {

            /**
             * This is done this way, because in ajax requests is_admin() returns true from the frontend and backend
             */
            if (!is_admin() || (function_exists('wp_doing_ajax') && wp_doing_ajax())) {
                $this->plugin_front_setup();
            }

            if (is_admin()) {
                $this->plugin_admin_setup();
            }
        }

        /**
         * Sets up the plugin on the front site,
         * by adding the necessary filters and actions.
         *
         * @since 1.0
         *
         * @return void
         */
        public function plugin_front_setup()
        {
            add_action('wp_head', array($this, 'simple_front_end_css'));
            add_filter('body_class', array($this, 'add_body_class'));

            add_action('pre_get_posts',               array($this, 'filter_guest_authors'));
            add_action('the_post',                    array($this, 'register_authordata'));

            add_filter('the_author',                  array($this, 'author_name'), 12);
            add_filter('get_the_author_display_name', array($this, 'author_name'), 12);
            add_filter('get_the_author_user_nicename', array($this, 'author_name'), 12);
            add_filter('get_the_author_nickname',      array($this, 'author_name'), 12);
            add_filter('get_the_author_ID', array($this, 'author_ID'), 12);
            add_filter('author_link',                 array($this, 'author_link'), 12);
            add_filter('get_the_author_link',         array($this, 'author_link'), 12);
            add_filter('get_the_author_url',          array($this, 'author_link'), 21);

            add_filter('author_description',          array($this, 'author_description'), 12);
            add_filter('get_the_author_description',  array($this, 'author_description'), 12);

            add_filter('get_the_author_user_email',      array($this, '__return_null_meta'), 12);
            add_filter('pre_get_avatar_data',         array($this, 'pre_get_author_avatar_data'), 12, 2);
        }

        /**
         * Sets up the plugin on the dashboard,
         * by adding the necessary filters and actions.
         *
         * @since 1.0
         * @return void
         */
        public function plugin_admin_setup()
        {
            add_action('save_post', array($this, 'save'));

            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
            add_action('current_screen', array($this, 'remove_default_author_meta_box'));
            add_action('add_meta_boxes', array($this, 'add_new_author_box'));

            add_filter('manage_posts_columns', array($this, 'add_author_column'));
            add_action('manage_posts_custom_column', array($this, 'modify_author_column'), 10, 2);

            // Ajax action to refresh the user image
            add_action('wp_ajax_BS_get_image', array($this, 'BS_ajax_get_image'));
        }

        /*
         * Adds the class 'guest-author' to the body of a post that's using a guest author
         * */
        public function add_body_class($classes)
        {
            if ($this->is_guest_author_available()) {
                $classes[] = 'guest-author-' . BS_GUEST_AUTHOR_VERSION;
            }

            return $classes;
        }

        /*
         * Adds the class 'guest-author' to the body of a post that's using a guest author
         * */
        public function simple_front_end_css()
        {
            if ($this->is_guest_author_available()) {
?>
                <style type="text/css">
                    body[class^="guest-author"] a[href=''] {
                        pointer-events: none;
                        color: inherit;
                        text-decoration: inherit;
                    }
                </style>
<?php
            }
        }


        /**
         * Filters the author query results that are written by a guest author.
         *
         *
         * This is needed because each post has the author ID under post_author.
         * Therefore, even if the admin user adds a guest author to a post their ID
         * will still be attached to the post.
         *
         * That being said, when a website user clicks on the a registered user's author link,
         * we don't want to list the posts that have a guest author's name showing in the meta.
         *
         * @since 1.0
         * @return void
         */
        public function filter_guest_authors($query)
        {
            if (
                is_author()
                || $query->is_author
                || (isset($query->author__in) && count($query->author__in)  > 0)
                || (isset($query->author_name) && !empty($query->author_name))
                || (isset($query->author) && !empty($query->author))
            ) {
                $query->set(
                    'meta_query',
                    array(
                        'relation' => 'OR',
                        array(
                            'key'     => "BS_author_type",
                            'value'   => 'BS_author_is_guest',
                            'compare' => '!='
                        ),
                        array(
                            'key'     => "BS_author_type",
                            'compare' => 'NOT EXISTS'
                        )
                    )
                );
            };
        }

        /**
         * Removes the default author meta box (surprise!). This is because
         * we will replace it with an identical box that has a little more to it.
         *
         * @since 1.0
         * @return void
         */
        public function remove_default_author_meta_box()
        {
            if (BS_is_gutenberg()) return;

            $post_type = $this->get_current_post_type();

            if (!$this->is_eligible_post_type($post_type)) {
                $post_type = 'post';
            }

            remove_meta_box('authordiv', $post_type, 'normal');
        }

        /**
         * Adds an author meta box that has more to it than the default one.
         *
         * @since 1.0
         * @return void
         */
        public function add_new_author_box($post_type)
        {
            $post_type = $this->get_current_post_type();

            if (!$this->is_eligible_post_type($post_type)) {
                $post_type = 'post';
            }

            $context = (BS_is_gutenberg()) ? 'side' : 'normal';

            add_meta_box('BS_authordiv', __('Author'), array($this, 'render_meta_box'), $post_type, $context, 'core');
        }

        /**
         * Renders the new author meta box
         *
         * @since 1.0
         * @return void
         */
        public function render_meta_box()
        {
            include_once "templates/meta-box-content.php";
        }

        /**
         * Adds the admin scripts and styles
         *
         * @since 1.0
         * @return void
         */
        public function admin_scripts($page)
        {
            if ($page === 'post.php' || $page === 'post-new.php') {
                wp_enqueue_style('BS_guest_author_styles', plugins_url('/css/style.css', __FILE__), [], BS_GUEST_AUTHOR_VERSION);

                // Enqueue WordPress media scripts
                wp_enqueue_media();
                // Enqueue custom script that will interact with wp.media
                wp_enqueue_script('BS_guest_author_script', plugins_url('/js/script.js', __FILE__), array('jquery'), BS_GUEST_AUTHOR_VERSION);
            }
        }

        /**
         * Renders the new author meta box
         *
         * @since 1.0
         * @return void
         */
        public function BS_ajax_get_image()
        {
            if (isset($_GET['id'])) {

                $image = wp_get_attachment_image(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT), 'medium', false, array('id' => 'BS-preview-image'));
                $data = array('image'    => $image);
                wp_send_json_success($data);
            } else {
                wp_send_json_error();
            }
        }

        /**
         * Saves the custom meta fields when the post is saved/updated
         *
         * @since 1.0
         * @return void
         */
        public function save($post_id)
        {
            global $post;

            $post_type = $this->get_current_post_type();
            if (!$post || !$this->is_eligible_post_type($post_type)) return;

            if (
                !isset($_POST['BS_author_type'])
                || !isset($_POST['BS_guest_author_name'])
            ) return;

            $meta = [
                'BS_author_type'              =>  $_POST['BS_author_type'],
                'BS_guest_author_name'        => ($_POST['BS_guest_author_name']),
                'BS_guest_author_url'         => esc_url(isset($_POST['BS_guest_author_url']) ? $_POST['BS_guest_author_url'] : ''),
                'BS_guest_author_description' => (isset($_POST['BS_guest_author_description']) ? $_POST['BS_guest_author_description'] : ''),
                'BS_guest_author_image_id'    => isset($_POST['BS_guest_author_image_id']) ? $_POST['BS_guest_author_image_id'] : ''
            ];

            foreach ($meta as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        }

        /**
         * Retrieves the author's ID
         *
         * @since 2.2
         *
         * @global object $post
         *
         * @param string $id This parameter is only needed for when
         *                     WordPress uses this function as filter.
         *
         * @return string | NULL returns NULL if it's a guest author post
         *
         */
        public function author_ID($id = '')
        {
            global $post;

            if ($post AND $this->is_guest_author_available()) {
                return NULL;
            }

            return $id;
        }

        /**
         * Retrieves the author's name
         *
         * @since 1.0
         *
         * @global object $post
         *
         * @param string $name This parameter is only needed for when
         *                     WordPress uses this function as filter.
         *
         * @return string The guest author's name if available, otherwise
         *                it will return the original author's name.
         */
        public function author_name($name = '')
        {
            global $post;

            if ($post) {
                $guest_name = get_post_meta($post->ID, 'BS_guest_author_name', true);
                if (!$this->is_guest_author_available()) {
                    return $name;
                }
            }

            if (isset($guest_name)) return $guest_name;

            return $name;
        }


        /**
         * Retrieves the author's email
         *
         * @since 2.1
         *
         * @global object $post
         *
         * @param string $info This parameter is only needed for when
         *                     WordPress uses this function as filter.
         *
         * @return  string|NULL .
         */
        public function __return_null_meta($info = '')
        {
            global $post;

            if ($post && $this->is_guest_author_available()) {
                return NULL;
            }

            return $info;
        }

        /**
         * Retrieves the author's link
         *
         * @since 1.0
         *
         * @global object $post
         *
         * @param string $link This parameter is only needed for when WordPress
         *                      uses this function as filter.
         *
         * @return string The guest author's link if the guest author
         *                is available, otherwise it is the original author's link
         */
        public function author_link($link = '')
        {
            if (!$this->is_guest_author_available()) return $link;

            global $post;
            return get_post_meta($post->ID, 'BS_guest_author_url', true);
        }

        /**
         * Retrieves the author's descriptions
         *
         * @since 1.0
         *
         * @global object $post
         *
         * @param string $desc This parameter is only needed for when WordPress
         *                      uses this function as filter.
         *
         * @return string The guest author's description if the guest author
         *                is available, otherwise it is the original author's description
         */
        public function author_description($desc = "")
        {
            global $post;

            if (!$this->is_guest_author_available()) {
                return $desc;
            }

            return get_post_meta($post->ID, 'BS_guest_author_description', true);
        }


        /**
         * Retrieves the author's avatar
         *
         * @since 1.0
         *
         * @global object $post
         *
         * @param string $img This parameter is only needed for when WordPress
         *                      uses this function as filter.
         *
         * @return string The guest author's avatar as an <img> element if the guest author
         *                is available, otherwise it is the original author's avatar
         */
        public function author_avatar($img = '')
        {
            global $post;

            if (!$this->is_guest_author_available()) {
                return $img;
            }

            $image_id = get_post_meta($post->ID, 'BS_guest_author_image_id', true);

            if ($image_id) {
                return wp_get_attachment_image($image_id, array(100, 100), false);
            } else {
                // Default image
                return "<img src=\"" . plugins_url('/img/default.jpg', __FILE__) . "\">";
            }
        }

        /**
         * Retrieves the author's avatar
         *
         * @since 1.9.1
         *
         * @global object $post
         *
         * @param string $args This parameter is only needed for when WordPress
         *                      uses this function as filter.
         *
         * @return string   The URL of the author image
         *
         */
        public function pre_get_author_avatar_data($args = [], $id_or_email = '')
        {
            global $post;

            if (!$post) return $args;

            if (!$this->is_guest_author_available()) {
                return $args;
            }

            // This assumes that if the id passed is empty, it means we're trying to get the guest author
            //
            // It also assumes that if the author id being fetched is the id of the real post author, then it probably
            // means that we should replace it with the guest author. This is for developers that use $post->post_author to
            // call the get_avatar_data() function
            //
            if (empty($id_or_email) || ($post && $post->post_author === $id_or_email)) {

                if (isset($args['url'])) {
                    return $args;
                }

                $image_id = get_post_meta($post->ID, 'BS_guest_author_image_id', true);

                if ($image_id) {

                    $img_size = $args['size'];

                    if ($args['width'] && $args['height']) {
                        $img_size = [$args['width'], $args['height']];
                    }

                    $image = wp_get_attachment_image_src(intval($image_id), $img_size);

                    $args['url'] = $image[0];
                    $args['found_avatar'] = true;
                }
            }

            return $args;
        }

        /**
         * Disables the avatar filter
         *
         * @since 1.0
         *
         * @return void
         */
        public function remove_author_avatar_filter()
        {
            remove_filter('get_avatar', array($this, 'author_avatar'));
        }

        /**
         * Disables the avatar filter when the comments are rendered. Why?
         * This kind of a weird measure, but it is needed because if we don't
         * remove the filter at some point, EVERY avatar in page will have the author's
         * image. So we figure, in MOST cases the comments will be rendered AFTER the post itself.
         *
         * @since 1.0
         *
         * @return void
         */
        public function remove_author_avatar_filter_at_comments()
        {
            $this->remove_author_avatar_filter();
        }

        /**
         * Registers the guest author info into the global variable $authordata
         *
         * @since 1.2
         *
         * @return void
         */
        public function register_authordata()
        {
            global $authordata;
            global $post;

            if (!$this->is_guest_author_available()) {
                return;
            }

            $guest_author = new WP_User();

            $guest_author->ID = NULL;

            $guest_author->user_url = $this->author_link();
            $guest_author->user_description = $this->author_description();
            $guest_author->display_name = $this->author_name();
            $guest_author->user_nicename = $this->author_name();

            $guest_author->user_login = NULL;
            $guest_author->user_pass = NULL;
            $guest_author->user_email = NULL;
            $guest_author->user_registered = NULL;
            $guest_author->user_activation_key = NULL;
            $guest_author->user_status = NULL;

            $authordata = $guest_author;
        }

        /**
         * Simply replaces the default author column in the posts list (of the dashboard),
         * with an identical one. The difference? the posts who have a guest author, will have the
         * notation ' — Guest Entry' next to the authoring user's name
         *
         * @since 1.0
         *
         * @return array the new columns array.
         */
        public function add_author_column($columns)
        {
            $new_columns_array = [];

            foreach ($columns as $key => $value) {
                if ($key === 'author') {
                    $new_columns_array['author_modified'] = $value;
                } else {
                    $new_columns_array[$key] = $value;
                }
            }
            return $new_columns_array;
        }
        public function modify_author_column($columns)
        {
            global $post;
            switch ($columns) {
                case 'author_modified':
                    $is_guest_author = $this->is_guest_author_selected();
                    $args = array(
                        'post_type' => $post->post_type,
                        'author' => get_the_author_meta('ID')
                    );
                    $author_text = $this->get_edit_link($args, get_the_author());
                    if ($is_guest_author) {
                        $author_text .= '  — <b>Guest Entry</b>';
                    }
                    self::wp_kses_wf($author_text);
            }
        }

        /**
         * From WordPress core: /wp-admin/includes/class-wp-posts-list-table.php
         *
         * But it was protected so I had to copy it over
         */
        private function get_edit_link($args, $label)
        {
            $url = add_query_arg($args, 'edit.php');

            $class_html = $aria_current = '';
            if (!empty($class)) {
                $class_html = sprintf(
                    ' class="%s"',
                    esc_attr($class)
                );

                if ('current' === $class) {
                    $aria_current = ' aria-current="page"';
                }
            }

            return sprintf(
                '<a href="%s"%s%s>%s</a>',
                esc_url($url),
                $class_html,
                $aria_current,
                $label
            );
        }

        /**
         * Determines whether the guest author tab is selected
         *
         * @since 1.0
         *
         * @global object $post
         *
         * @return boolean True if the is guest author tab is selected instead of the user author.
         */
        protected function is_guest_author_selected()
        {
            global $post;
            $author_type = get_post_meta($post->ID, 'BS_author_type', true);

            return $author_type === 'BS_author_is_guest';
        }

        /**
         * Determines whether the guest author's name is empty or not
         *
         * @since 1.0
         *
         * @global object $post
         *
         * @return boolean True if there is a name entered to the guest author's field
         */
        protected function guest_author_has_name()
        {
            global $post;
            $name = get_post_meta($post->ID, 'BS_guest_author_name', true);
            return !empty($name);
        }

        /**
         * Combines the previous two.
         *
         * @since 1.0
         *
         * @return boolean True if the guest author is available to use
         */
        public function is_guest_author_available()
        {
            return $this->is_eligible_post_type() && $this->is_guest_author_selected() && $this->guest_author_has_name();
        }

        /**
         * Checks to see if the post type is selected in the backend options
         *
         * @since 1.3
         *
         * @return boolean True if the guest author is available to use
         */
        protected function is_eligible_post_type($post_type = null)
        {
            if ($post_type === null)
                $post_type = $this->get_current_post_type();

            if (class_exists('BS_Guest_Author_Settings')) {
                $valid_post_types = BS_Guest_Author_Settings::$options['bs-guest-author-integration']['post-types'];
                if (in_array($post_type, $valid_post_types)) {
                    return true;
                }
            } else if ($post_type === 'post') {
                return true;
            }

            return false;
        }

        /**
         * Gets the current post type in the WordPress Admin
         *
         * @since 1.3
         *
         * @credit: https://gist.github.com/bradvin/1980309
         *
         * @return string|null post type name
         */
        function get_current_post_type()
        {
            global $post, $typenow, $current_screen;

            //we have a post so we can just get the post type from that
            if ($post && $post->post_type)
                return $post->post_type;

            //check the global $typenow - set in admin.php
            elseif ($typenow)
                return $typenow;

            //check the global $current_screen object - set in sceen.php
            elseif ($current_screen && $current_screen->post_type)
                return $current_screen->post_type;

            //lastly check the post_type querystring
            elseif (isset($_REQUEST['post_type']))
                return sanitize_key($_REQUEST['post_type']);

            //we do not know the post type!
            return null;
        }

        /**
         * Checks if Gutenberg is enabled
         *
         * @since 1.9.1
         *
         * @credit: https://wordpress.stackexchange.com/questions/321368/how-to-check-if-current-admin-page-is-gutenberg-editor
         *
         * @return boolean True if gutenberg is enabled
         */
        protected function is_gutenberg()
        {
            global $current_screen;
            $current_screen = get_current_screen();

            return (method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor())
                || ((function_exists('is_gutenberg_page')) && is_gutenberg_page());
        }

        public function wp_kses_wf($html)
        {
            add_filter('safe_style_css', function ($styles) {
                $styles_wf = array(
                    'text-align',
                    'margin',
                    'color',
                    'float',
                    'border',
                    'background',
                    'background-color',
                    'border-bottom',
                    'border-bottom-color',
                    'border-bottom-style',
                    'border-bottom-width',
                    'border-collapse',
                    'border-color',
                    'border-left',
                    'border-left-color',
                    'border-left-style',
                    'border-left-width',
                    'border-right',
                    'border-right-color',
                    'border-right-style',
                    'border-right-width',
                    'border-spacing',
                    'border-style',
                    'border-top',
                    'border-top-color',
                    'border-top-style',
                    'border-top-width',
                    'border-width',
                    'caption-side',
                    'clear',
                    'cursor',
                    'direction',
                    'font',
                    'font-family',
                    'font-size',
                    'font-style',
                    'font-variant',
                    'font-weight',
                    'height',
                    'letter-spacing',
                    'line-height',
                    'margin-bottom',
                    'margin-left',
                    'margin-right',
                    'margin-top',
                    'overflow',
                    'padding',
                    'padding-bottom',
                    'padding-left',
                    'padding-right',
                    'padding-top',
                    'text-decoration',
                    'text-indent',
                    'vertical-align',
                    'width',
                    'display',
                );

                foreach ($styles_wf as $style_wf) {
                    $styles[] = $style_wf;
                }
                return $styles;
            });

            $allowed_tags = wp_kses_allowed_html('post');
            $allowed_tags['input'] = array(
                'type' => true,
                'style' => true,
                'class' => true,
                'id' => true,
                'checked' => true,
                'disabled' => true,
                'name' => true,
                'size' => true,
                'placeholder' => true,
                'value' => true,
                'data-*' => true,
                'size' => true,
                'disabled' => true
            );

            $allowed_tags['textarea'] = array(
                'type' => true,
                'style' => true,
                'class' => true,
                'id' => true,
                'checked' => true,
                'disabled' => true,
                'name' => true,
                'size' => true,
                'placeholder' => true,
                'value' => true,
                'data-*' => true,
                'cols' => true,
                'rows' => true,
                'disabled' => true,
                'autocomplete' => true
            );

            $allowed_tags['select'] = array(
                'type' => true,
                'style' => true,
                'class' => true,
                'id' => true,
                'checked' => true,
                'disabled' => true,
                'name' => true,
                'size' => true,
                'placeholder' => true,
                'value' => true,
                'data-*' => true,
                'multiple' => true,
                'disabled' => true
            );

            $allowed_tags['option'] = array(
                'type' => true,
                'style' => true,
                'class' => true,
                'id' => true,
                'checked' => true,
                'disabled' => true,
                'name' => true,
                'size' => true,
                'placeholder' => true,
                'value' => true,
                'selected' => true,
                'data-*' => true
            );
            $allowed_tags['optgroup'] = array(
                'type' => true,
                'style' => true,
                'class' => true,
                'id' => true,
                'checked' => true,
                'disabled' => true,
                'name' => true,
                'size' => true,
                'placeholder' => true,
                'value' => true,
                'selected' => true,
                'data-*' => true,
                'label' => true
            );

            $allowed_tags['a'] = array(
                'href' => true,
                'data-*' => true,
                'class' => true,
                'style' => true,
                'id' => true,
                'target' => true,
                'data-*' => true,
                'role' => true,
                'aria-controls' => true,
                'aria-selected' => true,
                'disabled' => true
            );

            $allowed_tags['div'] = array(
                'style' => true,
                'class' => true,
                'id' => true,
                'data-*' => true,
                'role' => true,
                'aria-labelledby' => true,
                'value' => true,
                'aria-modal' => true,
                'tabindex' => true
            );

            $allowed_tags['li'] = array(
                'style' => true,
                'class' => true,
                'id' => true,
                'data-*' => true,
                'role' => true,
                'aria-labelledby' => true,
                'value' => true,
                'aria-modal' => true,
                'tabindex' => true
            );

            $allowed_tags['span'] = array(
                'style' => true,
                'class' => true,
                'id' => true,
                'data-*' => true,
                'aria-hidden' => true
            );

            $allowed_tags['style'] = array(
                'class' => true,
                'id' => true,
                'type' => true
            );

            $allowed_tags['fieldset'] = array(
                'class' => true,
                'id' => true,
                'type' => true
            );

            $allowed_tags['link'] = array(
                'class' => true,
                'id' => true,
                'type' => true,
                'rel' => true,
                'href' => true,
                'media' => true
            );

            $allowed_tags['form'] = array(
                'style' => true,
                'class' => true,
                'id' => true,
                'method' => true,
                'action' => true,
                'data-*' => true
            );

            $allowed_tags['script'] = array(
                'class' => true,
                'id' => true,
                'type' => true,
                'src' => true
            );

            echo wp_kses($html, $allowed_tags);

            add_filter('safe_style_css', function ($styles) {
                $styles_wf = array(
                    'text-align',
                    'margin',
                    'color',
                    'float',
                    'border',
                    'background',
                    'background-color',
                    'border-bottom',
                    'border-bottom-color',
                    'border-bottom-style',
                    'border-bottom-width',
                    'border-collapse',
                    'border-color',
                    'border-left',
                    'border-left-color',
                    'border-left-style',
                    'border-left-width',
                    'border-right',
                    'border-right-color',
                    'border-right-style',
                    'border-right-width',
                    'border-spacing',
                    'border-style',
                    'border-top',
                    'border-top-color',
                    'border-top-style',
                    'border-top-width',
                    'border-width',
                    'caption-side',
                    'clear',
                    'cursor',
                    'direction',
                    'font',
                    'font-family',
                    'font-size',
                    'font-style',
                    'font-variant',
                    'font-weight',
                    'height',
                    'letter-spacing',
                    'line-height',
                    'margin-bottom',
                    'margin-left',
                    'margin-right',
                    'margin-top',
                    'overflow',
                    'padding',
                    'padding-bottom',
                    'padding-left',
                    'padding-right',
                    'padding-top',
                    'text-decoration',
                    'text-indent',
                    'vertical-align',
                    'width'
                );

                foreach ($styles_wf as $style_wf) {
                    if (($key = array_search($style_wf, $styles)) !== false) {
                        unset($styles[$key]);
                    }
                }
                return $styles;
            });
        }
    }

endif;
