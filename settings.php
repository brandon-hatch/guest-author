<?php
class BS_Guest_Author_Settings {
    /**
     * Holds the values to be used in the fields callbacks
     */
    static $options = [
            'bs-guest-author-integration' => [
                'post-types' => ['post', 'page']
            ]
    ];

    /**
     * Start up
     */
    public function __construct() {
        add_action( 'wp_loaded', array( $this, 'init_options' ) );

        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function init_options () {
        // Set class property
        foreach (self::$options as $option_name => $value) {
            self::$options[$option_name] = get_option( $option_name, self::$options[$option_name] );
        }
    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
        // This page will be under "Settings"
        add_options_page(
            'Guest Author Settings',
            'Guest Author',
            'manage_options',
            'bs-guest-author-settings',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Guest Author Settings</h1>
            <form method="post" action="options.php">
                <?php
                // This prints out all hidden setting fields
                settings_fields( 'main_options_group' );
                do_settings_sections( 'bs-guest-author-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <div class="wrap">
            <p>Having problems? Can't find the option you're looking for? Let us know in the <a target="_blank" href="https://wordpress.org/support/plugin/guest-author">official forum</a>. We reply ASAP!</p>
        </div>
        <?php
        $this->settings_page_styles();
    }

    /**
     * Register and add settings
     */
    public function page_init() {
        register_setting(
            'main_options_group', // Option group
            'bs-guest-author-integration', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'integration_settings_section', // ID
            'Integration', // Title
            function () {  }, // Callback
            'bs-guest-author-settings' // Page
        );

        add_settings_field(
            'bs-guest-author-post-types', // ID
            'Post Types', // Title
            array( $this, 'post_types_list' ), // Callback
            'bs-guest-author-settings', // Page
            'integration_settings_section' // Section
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input ) {
        $new_input = array();

        if ( isset ($input['post-types']) )
            $new_input['post-types'] = $input['post-types'];

        return $new_input;
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function post_types_list () {
        $custom_post_types = get_post_types( ['_builtin' => false, 'public' => true] );
        $post_types = array_merge(['post' => 'post', 'page' => 'page'], $custom_post_types);

        ?>
        <p class="option-description">Enable Guest Author for the following post types</p>

        <span class="checkbox-options-wrapper">
            <?php
            foreach ($post_types as $post_type) {
                $selected = (isset (self::$options['bs-guest-author-integration']['post-types']) ) ? in_array($post_type, self::$options['bs-guest-author-integration']['post-types']) : false;
                $id = "bs-guest-author-integration[$post_type]";
                ?>
                <span class="checkbox-option">
                    <input type="checkbox" id="<?php echo esc_attr($id); ?>" name="bs-guest-author-integration[post-types][]" value="<?php echo esc_attr($post_type); ?>" <?php echo ($selected) ? 'checked' : ''; ?> />
                    <label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($post_type); ?></label>
                </span>
                <?php
            }

        echo "</span>";

    }

    private function settings_page_styles() { ?>

        <style>
            p.option-description {
                margin-bottom: 20px !important;
                color: #666;
            }
            .checkbox-options-wrapper {
                max-width: 300px;
                display: grid;
                grid-template-columns: 50% 50%;
            }

            .checkbox-option {
                margin-bottom: 10px;
            }
        </style>

    <?php
    }

}