<?php
/**
 * Settings class file.
 *
 * @package WordPress Plugin Template/Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings class.
 */
class Import_Products_from_Kevro_Settings
{

    /**
     * The single instance of Import_Products_from_Kevro_Settings.
     *
     * @var     object
     * @access  private
     * @since   1.0.0
     */
    private static $_instance = null; //phpcs:ignore

    /**
     * The main plugin object.
     *
     * @var     object
     * @access  public
     * @since   1.0.0
     */
    public $parent = null;

    /**
     * Prefix for plugin settings.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $base = '';

    /**
     * Available settings for plugin.
     *
     * @var     array
     * @access  public
     * @since   1.0.0
     */
    public $settings = array();

    /**
     * Constructor function.
     *
     * @param object $parent Parent object.
     */
    public function __construct($parent)
    {
        $this->parent = $parent;

        $this->base = 'wpt_';

        // Initialise settings.
        add_action('init', array($this, 'init_settings'), 11);

        // Register plugin settings.
        add_action('admin_init', array($this, 'register_settings'));

        // Add settings page to menu.
        add_action('admin_menu', array($this, 'add_menu_item'));

        // Add settings link to plugins page.
        add_filter(
            'plugin_action_links_' . plugin_basename($this->parent->file),
            array(
                $this,
                'add_settings_link',
            )
        );

        // Configure placement of plugin settings page. See readme for implementation.
        add_filter($this->base . 'menu_settings', array($this, 'configure_settings'));
    }

    /**
     * Initialise settings
     *
     * @return void
     */
    public function init_settings()
    {
        $this->settings = $this->settings_fields();
    }

    /**
     * Add settings page to admin menu
     *
     * @return void
     */
    public function add_menu_item()
    {

        $args = $this->menu_settings();

        // Do nothing if wrong location key is set.
        if (is_array($args) && isset($args['location']) && function_exists('add_' . $args['location'] . '_page')) {
            switch ($args['location']) {
                case 'options':
                case 'submenu':
                    $page = add_submenu_page($args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function']);
                    break;
                case 'menu':
                    $page = add_menu_page($args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'], $args['icon_url'], $args['position']);
                    break;
                default:
                    return;
            }
            add_action('admin_print_styles-' . $page, array($this, 'settings_assets'));
        }
    }

    /**
     * Prepare default settings page arguments
     *
     * @return mixed|void
     */
    private function menu_settings()
    {
        return apply_filters(
            $this->base . 'menu_settings',
            array(
                'location' => 'options', // Possible settings: options, menu, submenu.
                'parent_slug' => 'options-general.php',
                'page_title' => __('Kevro Import Plugin Settings', 'import-products-from-kevro'),
                'menu_title' => __('Kevro Import Plugin Settings', 'import-products-from-kevro'),
                'capability' => 'manage_options',
                'menu_slug' => $this->parent->_token . '_settings',
                'function' => array($this, 'settings_page'),
                'icon_url' => '',
                'position' => null,
            )
        );
    }

    /**
     * Container for settings page arguments
     *
     * @param array $settings Settings array.
     *
     * @return array
     */
    public function configure_settings($settings = array())
    {
        return $settings;
    }

    /**
     * Load settings JS & CSS
     *
     * @return void
     */
    public function settings_assets()
    {

        // We're including the farbtastic script & styles here because they're needed for the colour picker
        // If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below.
        wp_enqueue_style('farbtastic');
        wp_enqueue_script('farbtastic');

        // We're including the WP media scripts here because they're needed for the image upload field.
        // If you're not including an image upload then you can leave this function call out.
        wp_enqueue_media();

        wp_register_script($this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array('farbtastic', 'jquery'), '1.0.0', true);
        wp_enqueue_script($this->parent->_token . '-settings-js');
    }

    /**
     * Add settings link to plugin list table
     *
     * @param  array $links Existing links.
     * @return array        Modified links.
     */
    public function add_settings_link($links)
    {
        $settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __('Settings', 'import-products-from-kevro') . '</a>';
        array_push($links, $settings_link);
        return $links;
    }

    /**
     * Build settings fields
     *
     * @return array Fields to be displayed on settings page
     */
    private function settings_fields()
    {

        $settings['standard'] = array(
            'title' => __('Start Import Process', 'import-products-from-kevro'),
            'description' => __('This command will overwrite and update all existing products that match from the
            supplier website. This process will take time with no gurantee that it will complete.<br><br>
            If a product exists, certain fields will be updated including images.<br><br>
            Nothing will be done if the unique import ID is blank. 
            ', 'import-products-from-kevro'),
            'fields' => array(
                array(
                    'id' => 'text_field',
                    'label' => __('Unique Import ID', 'import-products-from-kevro'),
                    'description' => __('To allow this process to resume in case of it stopping halfway, specify a unique import ID here.
                    ', 'import-products-from-kevro'),
                    'type' => 'text',
                    'default' => '',
                    'placeholder' => __('EG 2019-11-11', 'import-products-from-kevro'),

                ),
            ),
        );

        $settings = apply_filters($this->parent->_token . '_settings_fields', $settings);

        return $settings;
    }


    public function attach_product_thumbnail($post_id, $url, $flag){
        /*
         * If allow_url_fopen is enable in php.ini then use this
         */
        $image_url = $url;
        $url_array = explode('/',$url);
        $image_name = $url_array[count($url_array)-1];
        $image_data = file_get_contents($image_url); // Get image data

        $upload_dir = wp_upload_dir(); // Set upload folder
        $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); //    Generate unique name
        $filename = basename( $unique_file_name ); // Create image file name
        // Check folder permission and define file location
        if( wp_mkdir_p( $upload_dir['path'] ) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }
        // Create the image file on the server
        file_put_contents( $file, $image_data );
        // Check image file type
        $wp_filetype = wp_check_filetype( $filename, null );
        // Set attachment data
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name( $filename ),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        // Create the attachment
        $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
        // Include image.php
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
        // Assign metadata to attachment
        wp_update_attachment_metadata( $attach_id, $attach_data );
        // asign to feature image
        if( $flag == 0){
            // And finally assign featured image to post
            set_post_thumbnail( $post_id, $attach_id );
        }
        // assign to the product gallery
        if( $flag == 1 ){
            // Add gallery image to product
            $attach_id_array = get_post_meta($post_id,'_product_image_gallery', true);
            $attach_id_array .= ','.$attach_id;
            update_post_meta($post_id,'_product_image_gallery',$attach_id_array);
        }
    }

    //public function getProductsFromKevro()

    /**
     * Register plugin settings
     *
     * @return void
     */
    public function register_settings()
    {
        if (is_array($this->settings)) {

            // Check posted/selected tab.
            //phpcs:disable
            $current_section = '';
            if (isset($_POST['tab']) && $_POST['tab']) {
                $current_section = $_POST['tab'];
            } else {
                if (isset($_GET['tab']) && $_GET['tab']) {
                    $current_section = $_GET['tab'];
                }
            }
            //phpcs:enable

            if (isset($_POST['submitted']) && $_POST['submitted'] && $_POST['submitted'] == "submitted" && isset($_POST['wpt_text_field']) && $_POST['wpt_text_field']) {

                $uploads = wp_upload_dir();

                //file_put_contents($uploads['basedir']  . "/uplaods dir-" . $_POST["wpt_text_field"] . ".txt" ,  $uploads['basedir'] );

                if (file_exists($uploads['basedir'] . "/StockJSON-Parts-" . $_POST["wpt_text_field"] . ".txt")) {
                    //the file exists, dont read from the server, read the file contents only
                    $json = json_decode(file_get_contents($uploads['basedir'] . "/StockJSON-Parts-" . $_POST["wpt_text_field"] . ".txt"));
                } else {
                    $soapclient_options = array();
                    $soapclient_options['login'] = 'stkuser';
                    $soapclient_options['password'] = 'B@rron0n';

                    $wsdl = 'https://' . $soapclient_options['login'] . ':' . $soapclient_options['password'] . '@wslive.kevro.co.za/StockFeed.asmx?wsdl';

                    $client = new SoapClient($wsdl, $soapclient_options);

                    $params = array('TokenKey' => 'T4QzhLB5UP8hygrUeEchBLdz9LtK2nSz', 'username' => 'KPW', 'psw' => 'ksgSciZduyw=', 'EntityName' => 'Customer-KPW-12512', 'entityID' => '12512');

                    $response = $client->login($params);

                    $testarray = array();
                    $testarray = get_object_vars($response);

                    if ($testarray[0] = true) {

                        $params = array('entityID' => '12512', 'username' => 'KPW', 'psw' => 'ksgSciZduyw=', 'ReturnType' => 'JSON');

                        $response2 = $client->GetFeedByEntityID($params);

                        $blog = get_object_vars($response2);
                        $content = get_object_vars($blog['GetFeedByEntityIDResult']);

                        if ($content['Callresult'] = true) {

                            $res = $content['ResponseData'];

                            //after getting the contents, process them one by one including putting them into woocommerce

                        } else {
                            var_dump($content['ErrorMsg']);

                        }

                    } else {
                        var_dump($response);
                    }

                    $json = json_decode($content['ResponseData']);

                    foreach ($json as $item) {
                        $item->ImportID = $_POST["wpt_text_field"];
                    }

                    file_put_contents($uploads['basedir'] . "/StockJSON-Parts-" . $_POST["wpt_text_field"] . ".txt", json_encode($json));

                }

                //do we have a file of imported items
                if (file_exists($uploads['basedir'] . "/StockJSON-Parts-Imported-" . $_POST["wpt_text_field"] . ".txt")) {
                    //read this file
                    $updatedJson = json_decode(file_get_contents($uploads['basedir'] . "/StockJSON-Parts-Imported--" . $_POST["wpt_text_field"] . ".txt"));

                } else {
                    $updated = array();

                    // This appends a new element to $d, in this case the value is another array
                    //$d[] = array('item' => "$name" ,'rate' => "$rating");

                    $updatedJson = json_encode($updated);
                }

                //echo count($json);

                //file_put_contents($uploads['basedir']  . "/control-" . $_POST["wpt_text_field"] . ".txt" ,  json_encode($json));

                $count = 0;
                foreach ($json as $item) {

                    //file_put_contents("./../StockJSON-Parts.txt", json_encode($item), FILE_APPEND);

                    //echo $item->StockCode . " - ";
                    //echo $item->Category . "<br>";
                    //1. Apparel 2. Bags 3. Chef Wear 4. Gifts 5. Head Wear 6. Sports Wear 7. Work Wear
                    $cats = array("Apparel", "Bags", "Chef Wear", "Gifts", "Head Wear", "Sports Wear", "Work Wear");

                    if (in_array($item->Category, $cats)) {
                        // if ($count == 0) {
                        //     var_dump($item);
                        //     $filename = "images/" . basename(parse_url($item->Image)['path']);
                        //     // echo $filename . "---------";
                        //     // echo $item->Image . "<br>";
                        //     //save_image($item->Image, $filename);

                        // }
                        if ($count <= 100) {
                            $post_id = wp_insert_post(array(
                                'post_title' => $item->Description,
                                'post_type' => 'product',
                                'post_status' => 'publish',
                                'post_content' => $item->Description,
                                'post_excerpt' => $item->Description,
                            ));

                            wp_set_object_terms($post_id, 'simple', 'product_type');
                            update_post_meta($post_id, '_visibility', 'visible');
                            update_post_meta($post_id, '_stock_status', 'instock');
                            update_post_meta($post_id, '_regular_price', $item->BasePrice);
                            update_post_meta($post_id, '_sku', $item->StockID);
                            update_post_meta($post_id, '_product_attributes', array());
                            update_post_meta($post_id, '_manage_stock', 'yes');
                            update_post_meta($post_id, '_stock', $item->QtyAvailable);
                            wc_update_product_stock($post_id, $item->QtyAvailable, 'set');


                            // For every product that costs R 100-500 we add 100 Pula, 
                            // R501 upwards we add P 200,anything less than a hundred is P50
                            if ($item->BasePrice < 100){
                                $topup = 50; 
                            }
                            if ($item->BasePrice > 100 && $item->BasePrice < 500){
                                $topup = 100; 
                            }
                            if ($item->BasePrice > 500){
                                $topup = 200; 
                            }                            
                        
                            update_post_meta($post_id, '_price',$item->BasePrice + $topup);

                            update_post_meta($post_id, 'post_status', 'publish');

                            $this->attach_product_thumbnail($post_id, $item->Image , 0);

                            $term = get_term_by('name', $item->Category, 'product_cat');

                            wp_set_object_terms($post_id, $term->term_id, 'product_cat');


                        }



                        //if ($count <= 500) {

                        // if ((array_search($item->StockID, $updatedJson)) === false) {
                        //     // this element has not been imported
                        //     //$json_updated = json_encode($json);
                        //     $updatedJson[] = $item->StockID;

                        //     file_put_contents($uploads['basedir'] . "/StockJSON-Parts-Imported-" . $_POST["wpt_text_field"] . ".txt", "json_encode($updatedJson)");

                        // }

                        //}

                    }
                    $count++;

                }

                // $post_id = wp_insert_post(array(
                //     'post_title' => 'Test Product 23',
                //     'post_type' => 'product',
                //     'post_staus' => 'publish',
                //     'post_content' => "ecently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.",
                //     'post_excerpt' => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book",
                // ));

                // { ["StockCode"]=> string(6) "PA-CHI"
                //     ["StockHeaderID"]=> int(1)
                //  ["StockID"]=> int(15004)
                //     ["Description"]=> string(21) "Cotton Chino (PA-CHI)"
                //     ["Colour"]=> string(5) "Black"
                //     ["Size"]=> string(2) "28"
                //     ["ColorStatus"]=> string(7) "Regular"
                //     ["BasePrice"]=> float(229.99)
                //     ["DiscountBasePrice"]=> float(204.69)
                //     ["RoyaltyFactor"]=> float(1)
                //     ["Category"]=> string(7) "Apparel"
                //     ["Type"]=> string(7) "Bottoms"
                //     ["Brand"]=> string(6) "Barron"
                //     ["Image"]=> string(47) "https://wslive.kevro.co.za/images/1/1-Black.png"
                //     ["QtyAvailable"]=> int(64)
                //     ["WH2(LBO)"]=> int(0)
                //     ["WH3(BOND)"]=> int(0)
                //     ["WH4(BW)"]=> int(0) }

            }

            foreach ($this->settings as $section => $data) {

                if ($current_section && $current_section !== $section) {
                    continue;
                }

                // Add section to page.
                add_settings_section($section, $data['title'], array($this, 'settings_section'), $this->parent->_token . '_settings');

                foreach ($data['fields'] as $field) {

                    // Validation callback for field.
                    $validation = '';
                    if (isset($field['callback'])) {
                        $validation = $field['callback'];
                    }

                    // Register field.
                    $option_name = $this->base . $field['id'];
                    register_setting($this->parent->_token . '_settings', $option_name, $validation);

                    // Add field to page.
                    add_settings_field(
                        $field['id'],
                        $field['label'],
                        array($this->parent->admin, 'display_field'),
                        $this->parent->_token . '_settings',
                        $section,
                        array(
                            'field' => $field,
                            'prefix' => $this->base,
                        )
                    );
                }

                if (!$current_section) {
                    break;
                }
            }

        }
    }



    /**
     * Settings section.
     *
     * @param array $section Array of section ids.
     * @return void
     */
    public function settings_section($section)
    {
        $html = '<p> ' . $this->settings[$section['id']]['description'] . '</p>' . "\n";
        echo $html; //phpcs:ignore
    }

    /**
     * Load settings page content.
     *
     * @return void
     */
    public function settings_page()
    {

        // Build page HTML.
        $html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
        $html .= '<h2>' . __('Plugin Settings', 'import-products-from-kevro') . '</h2>' . "\n";

        $tab = '';
        //phpcs:disable
        if (isset($_GET['tab']) && $_GET['tab']) {
            $tab .= $_GET['tab'];
        }
        //phpcs:enable

        // Show page tabs.
        if (is_array($this->settings) && 1 < count($this->settings)) {

            $html .= '<h2 class="nav-tab-wrapper">' . "\n";

            $c = 0;
            foreach ($this->settings as $section => $data) {

                // Set tab class.
                $class = 'nav-tab';
                if (!isset($_GET['tab'])) { //phpcs:ignore
                    if (0 === $c) {
                        $class .= ' nav-tab-active';
                    }
                } else {
                    if (isset($_GET['tab']) && $section == $_GET['tab']) { //phpcs:ignore
                        $class .= ' nav-tab-active';
                    }
                }

                // Set tab link.
                $tab_link = add_query_arg(array('tab' => $section));
                if (isset($_GET['settings-updated'])) { //phpcs:ignore
                    $tab_link = remove_query_arg('settings-updated', $tab_link);
                }

                // Output tab.
                $html .= '<a href="' . $tab_link . '" class="' . esc_attr($class) . '">' . esc_html($data['title']) . '</a>' . "\n";

                ++$c;
            }

            $html .= '</h2>' . "\n";
        }

        $html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

        // Get settings fields.
        ob_start();
        settings_fields($this->parent->_token . '_settings');
        do_settings_sections($this->parent->_token . '_settings');
        $html .= ob_get_clean();

        $html .= '<p class="submit">' . "\n";
        $html .= '<input type="hidden" name="tab" value="' . esc_attr($tab) . '" />' . "\n";
        $html .= '<input type="hidden" name="submitted" value="submitted" />' . "\n";
        $html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr(__('Run Import', 'import-products-from-kevro')) . '" />' . "\n";
        $html .= '</p>' . "\n";
        $html .= '</form>' . "\n";
        $html .= '</div>' . "\n";

        echo $html; //phpcs:ignore
    }

    /**
     * Main Import_Products_from_Kevro_Settings Instance
     *
     * Ensures only one instance of Import_Products_from_Kevro_Settings is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see Import_Products_from_Kevro()
     * @param object $parent Object instance.
     * @return object Import_Products_from_Kevro_Settings instance
     */
    public static function instance($parent)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($parent);
        }
        return self::$_instance;
    } // End instance()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Cloning of Import_Products_from_Kevro_API is forbidden.')), esc_attr($this->parent->_version));
    } // End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Unserializing instances of Import_Products_from_Kevro_API is forbidden.')), esc_attr($this->parent->_version));
    } // End __wakeup()

}
