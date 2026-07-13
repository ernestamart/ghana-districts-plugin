<?php
/**
 * Plugin Name: Ghana Districts & Regions
 * Plugin URI: https://github.com/ernestamart/ghana-districts-plugin
 * Description: Add Ghana region and district dropdowns to any page, post, or Contact Form 7.
 * Version: 1.1.0
 * Author: Amart Plugin Co.
 * Author URI: https://github.com/ernestamart
 * License: GPL v2 or later
 * Text Domain: ghana-districts
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'GHANA_DISTRICTS_VERSION', '1.1.0' );
define( 'GHANA_DISTRICTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'GHANA_DISTRICTS_URL', plugin_dir_url( __FILE__ ) );

class Ghana_Districts {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_shortcode( 'ghana_regions', array( $this, 'render_regions_shortcode' ) );
        add_shortcode( 'ghana_districts', array( $this, 'render_districts_shortcode' ) );

        // Contact Form 7 Integration
        add_action( 'wpcf7_init', array( $this, 'cf7_init' ) );

        if ( is_admin() ) {
            $admin_file = GHANA_DISTRICTS_PATH . 'admin/settings.php';
            if ( file_exists( $admin_file ) ) {
                require_once $admin_file;
            }
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'ghana-districts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function enqueue_assets() {
        $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
        
        wp_enqueue_style(
            'ghana-districts',
            GHANA_DISTRICTS_URL . "assets/css/style{$suffix}.css",
            array(),
            GHANA_DISTRICTS_VERSION
        );
        
        wp_enqueue_script(
            'ghana-districts',
            GHANA_DISTRICTS_URL . "assets/js/script{$suffix}.js",
            array( 'jquery' ),
            GHANA_DISTRICTS_VERSION,
            true
        );
        
        wp_localize_script( 'ghana-districts', 'ghanaDistrictsData', array(
            'districts' => $this->get_districts(),
            'strings'   => array(
                'select_region'       => __( 'Select Region', 'ghana-districts' ),
                'select_district'     => __( 'Select District', 'ghana-districts' ),
                'select_region_first' => __( 'Select Region First', 'ghana-districts' ),
            ),
        ) );
    }

    /**
     * CF7 Integration Initialization
     */
    public function cf7_init() {
        if ( function_exists( 'wpcf7_add_form_tag' ) ) {
            wpcf7_add_form_tag( array( 'ghana_region', 'ghana_region*' ), array( $this, 'cf7_region_handler' ), array( 'name-attr' => true ) );
            wpcf7_add_form_tag( array( 'ghana_district', 'ghana_district*' ), array( $this, 'cf7_district_handler' ), array( 'name-attr' => true ) );
        }

        if ( is_admin() && function_exists( 'wpcf7_add_tag_generator' ) ) {
            wpcf7_add_tag_generator( 'ghana_region', __( 'Ghana Region', 'ghana-districts' ), 'wpcf7-tg-menu-ghana-region', array( $this, 'cf7_tag_generator_region' ) );
            wpcf7_add_tag_generator( 'ghana_district', __( 'Ghana District', 'ghana-districts' ), 'wpcf7-tg-menu-ghana-district', array( $this, 'cf7_tag_generator_district' ) );
        }

        // CF7 Validation Filters
        add_filter( 'wpcf7_validate_ghana_region', array( $this, 'cf7_validate_region' ), 10, 2 );
        add_filter( 'wpcf7_validate_ghana_region*', array( $this, 'cf7_validate_region' ), 10, 2 );
        add_filter( 'wpcf7_validate_ghana_district', array( $this, 'cf7_validate_district' ), 10, 2 );
        add_filter( 'wpcf7_validate_ghana_district*', array( $this, 'cf7_validate_district' ), 10, 2 );
    }

    public function cf7_region_handler( $tag ) {
        $tag = new WPCF7_FormTag( $tag );

        if ( empty( $tag->name ) ) {
            return '';
        }

        $class = wpcf7_form_controls_class( $tag->type, 'ghana-region-select' );
        $atts = array(
            'name' => $tag->name,
            'class' => $tag->get_class_option( $class ),
            'id' => $tag->get_id_option(),
            'data-group' => $tag->get_option( 'group', '', true ) ?: 'cf7-default',
        );

        $regions = $this->get_regions();
        $html = sprintf( '<span class="wpcf7-form-control-wrap %s">', sanitize_html_class( $tag->name ) );
        $html .= sprintf( '<select %s>', wpcf7_format_atts( $atts ) );
        $html .= sprintf( '<option value="">%s</option>', esc_html__( 'Select Region', 'ghana-districts' ) );
        
        foreach ( $regions as $slug => $name ) {
            $html .= sprintf( '<option value="%s">%s</option>', esc_attr( $slug ), esc_html( $name ) );
        }

        $html .= '</select></span>';
        return $html;
    }

    public function cf7_district_handler( $tag ) {
        $tag = new WPCF7_FormTag( $tag );

        if ( empty( $tag->name ) ) {
            return '';
        }

        $class = wpcf7_form_controls_class( $tag->type, 'ghana-district-select' );
        $atts = array(
            'name' => $tag->name,
            'class' => $tag->get_class_option( $class ),
            'id' => $tag->get_id_option(),
            'data-group' => $tag->get_option( 'group', '', true ) ?: 'cf7-default',
            'disabled' => 'disabled',
        );

        $html = sprintf( '<span class="wpcf7-form-control-wrap %s">', sanitize_html_class( $tag->name ) );
        $html .= sprintf( '<select %s>', wpcf7_format_atts( $atts ) );
        $html .= sprintf( '<option value="">%s</option>', esc_html__( 'Select Region First', 'ghana-districts' ) );
        $html .= '</select></span>';
        return $html;
    }

    public function cf7_validate_region( $result, $tag ) {
        $tag = new WPCF7_FormTag( $tag );
        $name = $tag->name;
        $value = isset( $_POST[$name] ) ? trim( $_POST[$name] ) : '';

        if ( $tag->is_required() && empty( $value ) ) {
            $result->invalidate( $tag, __( 'Please select a region.', 'ghana-districts' ) );
        }

        return $result;
    }

    public function cf7_validate_district( $result, $tag ) {
        $tag = new WPCF7_FormTag( $tag );
        $name = $tag->name;
        $value = isset( $_POST[$name] ) ? trim( $_POST[$name] ) : '';

        if ( $tag->is_required() && empty( $value ) ) {
            $result->invalidate( $tag, __( 'Please select a district.', 'ghana-districts' ) );
        }

        return $result;
    }

    public function cf7_tag_generator_region( $contact_form, $args = '' ) {
        $args = wp_parse_args( $args, array() );
        $type = 'ghana_region';
        ?>
        <div class="control-box">
            <fieldset>
                <legend><?php echo esc_html( __( 'Generate a form-tag for Ghana Region dropdown', 'ghana-districts' ) ); ?></legend>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo esc_html( __( 'Field type', 'ghana-districts' ) ); ?></th>
                            <td>
                                <label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'ghana-districts' ) ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'ghana-districts' ) ); ?></label></th>
                            <td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'ghana-districts' ) ); ?></label></th>
                            <td><input type="text" name="id" class="idvalue oneline" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'ghana-districts' ) ); ?></label></th>
                            <td><input type="text" name="class" class="classvalue oneline" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-group' ); ?>"><?php echo esc_html( __( 'Group (for linking)', 'ghana-districts' ) ); ?></label></th>
                            <td><input type="text" name="group" class="option oneline" id="<?php echo esc_attr( $args['content'] . '-group' ); ?>" placeholder="e.g. group1" /></td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </div>
        <div class="insert-box">
            <input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select();" />
            <div class="submitbox">
                <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'ghana-districts' ) ); ?>" />
            </div>
        </div>
        <?php
    }

    public function cf7_tag_generator_district( $contact_form, $args = '' ) {
        $args = wp_parse_args( $args, array() );
        $type = 'ghana_district';
        ?>
        <div class="control-box">
            <fieldset>
                <legend><?php echo esc_html( __( 'Generate a form-tag for Ghana District dropdown', 'ghana-districts' ) ); ?></legend>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo esc_html( __( 'Field type', 'ghana-districts' ) ); ?></th>
                            <td>
                                <label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'ghana-districts' ) ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'ghana-districts' ) ); ?></label></th>
                            <td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'ghana-districts' ) ); ?></label></th>
                            <td><input type="text" name="id" class="idvalue oneline" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'ghana-districts' ) ); ?></label></th>
                            <td><input type="text" name="class" class="classvalue oneline" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-group' ); ?>"><?php echo esc_html( __( 'Group (for linking)', 'ghana-districts' ) ); ?></label></th>
                            <td><input type="text" name="group" class="option oneline" id="<?php echo esc_attr( $args['content'] . '-group' ); ?>" placeholder="e.g. group1" /></td>
                        </tr>
                    </tbody>
                </table>
            </fieldset>
        </div>
        <div class="insert-box">
            <input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select();" />
            <div class="submitbox">
                <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'ghana-districts' ) ); ?>" />
            </div>
        </div>
        <?php
    }

    public function render_regions_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id'    => 'ghana-region-' . uniqid(),
            'class' => '',
            'group' => 'default'
        ), $atts, 'ghana_regions' );
        
        $regions = $this->get_regions();
        
        ob_start();
        ?>
        <div class="ghana-regions-wrapper" data-group="<?php echo esc_attr( $atts['group'] ); ?>">
            <select id="<?php echo esc_attr( $atts['id'] ); ?>" 
                    name="ghana_region"
                    class="ghana-region-select <?php echo esc_attr( $atts['class'] ); ?>" 
                    data-group="<?php echo esc_attr( $atts['group'] ); ?>">
                <option value=""><?php esc_html_e( 'Select Region', 'ghana-districts' ); ?></option>
                <?php foreach ( $regions as $slug => $name ) : ?>
                    <option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_districts_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id'    => 'ghana-district-' . uniqid(),
            'class' => '',
            'group' => 'default'
        ), $atts, 'ghana_districts' );
        
        ob_start();
        ?>
        <div class="ghana-districts-wrapper" data-group="<?php echo esc_attr( $atts['group'] ); ?>">
            <select id="<?php echo esc_attr( $atts['id'] ); ?>" 
                    name="ghana_district"
                    class="ghana-district-select <?php echo esc_attr( $atts['class'] ); ?>" 
                    data-group="<?php echo esc_attr( $atts['group'] ); ?>" 
                    disabled>
                <option value=""><?php esc_html_e( 'Select Region First', 'ghana-districts' ); ?></option>
            </select>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_regions() {
        return apply_filters( 'ghana_districts_regions', array(
            'greater-accra' => __( 'Greater Accra Region', 'ghana-districts' ),
            'ashanti'       => __( 'Ashanti Region', 'ghana-districts' ),
            'western'       => __( 'Western Region', 'ghana-districts' ),
            'eastern'       => __( 'Eastern Region', 'ghana-districts' ),
            'central'       => __( 'Central Region', 'ghana-districts' ),
            'volta'         => __( 'Volta Region', 'ghana-districts' ),
            'northern'      => __( 'Northern Region', 'ghana-districts' ),
            'upper-east'    => __( 'Upper East Region', 'ghana-districts' ),
            'upper-west'    => __( 'Upper West Region', 'ghana-districts' ),
            'bono'          => __( 'Bono Region', 'ghana-districts' ),
            'ahafo'         => __( 'Ahafo Region', 'ghana-districts' ),
            'bono-east'     => __( 'Bono East Region', 'ghana-districts' ),
            'oti'           => __( 'Oti Region', 'ghana-districts' ),
            'north-east'    => __( 'North East Region', 'ghana-districts' ),
            'savannah'      => __( 'Savannah Region', 'ghana-districts' ),
            'western-north' => __( 'Western North Region', 'ghana-districts' ),
        ) );
    }

    private function get_districts() {
        return array(
            'greater-accra' => array( 'Accra Metropolis', 'Ada East', 'Ada West', 'Adentan', 'Ashaiman', 'Ga Central', 'Ga East', 'Ga North', 'Ga South', 'Ga West', 'Kpone Katamanso', 'Krowor', 'La Dade-Kotopon', 'La Nkwantanang-Madina', 'Ledzokuku', 'Ningo-Prampram', 'Okaikwei North', 'Shai Osudoku', 'Tema Metropolis', 'Ayawaso Central', 'Ayawaso East', 'Ayawaso North', 'Ayawaso West', 'Korle Klottey', 'Ablekuma Central', 'Ablekuma North', 'Ablekuma West', 'Weija Gbawe' ),
            'ashanti' => array( 'Adansi Asokwa', 'Adansi North', 'Adansi South', 'Afigya-Kwabre North', 'Afigya-Kwabre South', 'Ahafo Ano North', 'Ahafo Ano South', 'Asante Akim Central', 'Asante Akim North', 'Asante Akim South', 'Asokore Mampong', 'Asokwa', 'Atwima Kwanwoma', 'Atwima Mponua', 'Atwima Nwabiagya', 'Bekwai', 'Bosome Freho', 'Bosomtwe', 'Ejisu', 'Ejura-Sekyedumase', 'Juaben', 'Kumasi Metropolis', 'Kwabre East', 'Kwadaso', 'Mampong', 'Obuasi East', 'Obuasi West', 'Offinso North', 'Offinso South', 'Old Tafo', 'Sekyere Afram Plains', 'Sekyere Central', 'Sekyere East', 'Sekyere Kumawu', 'Sekyere South', 'Suame' ),
            'western' => array( 'Ahanta West', 'Amenfi Central', 'Amenfi East', 'Amenfi West', 'Effia-Kwesimintsim', 'Jomoro', 'Mpohor', 'Nzema East', 'Prestea-Huni Valley', 'Sekondi-Takoradi Metropolis', 'Shama', 'Tarkwa-Nsuaem', 'Wassa East' ),
            'eastern' => array( 'Abuakwa North', 'Abuakwa South', 'Achiase', 'Akuapim North', 'Akuapim South', 'Akyem Manso', 'Asene Manso Akroso', 'Asuogyaman', 'Atiwa East', 'Atiwa West', 'Ayensuano', 'Birim Central', 'Birim North', 'Birim South', 'Denkyembour', 'Fanteakwa North', 'Fanteakwa South', 'Kwaebibirem', 'Kwahu Afram Plains North', 'Kwahu Afram Plains South', 'Kwahu East', 'Kwahu South', 'Kwahu West', 'Lower Manya Krobo', 'New Juaben North', 'New Juaben South', 'Nsawam Adoagyiri', 'Okere', 'Suhum', 'Upper Manya Krobo', 'Upper West Akim', 'West Akim', 'Yilo Krobo' ),
            'central' => array( 'Abura-Asebu-Kwamankese', 'Agona East', 'Agona West', 'Ajumako-Enyan-Essiam', 'Asikuma-Odoben-Brakwa', 'Assin Central', 'Assin North', 'Assin South', 'Awutu Senya East', 'Awutu Senya West', 'Cape Coast Metropolis', 'Effutu', 'Ekumfi', 'Gomoa Central', 'Gomoa East', 'Gomoa West', 'Komenda-Edina-Eguafo-Abirem', 'Mfantsiman', 'Twifo-Atti Morkwa', 'Twifo-Heman-Lower Denkyira', 'Upper Denkyira East', 'Upper Denkyira West' ),
            'volta' => array( 'Adaklu', 'Afadzato South', 'Agotime-Ziope', 'Akatsi North', 'Akatsi South', 'Anloga', 'Central Tongu', 'Ho Metropolis', 'Ho West', 'Hohoe', 'Keta', 'Ketu North', 'Ketu South', 'Kpando', 'North Dayi', 'North Tongu', 'South Dayi', 'South Tongu' ),
            'northern' => array( 'Gushiegu', 'Karaga', 'Kpandai', 'Kumbungu', 'Mion', 'Nanton', 'Nanumba North', 'Nanumba South', 'Saboba', 'Sagnarigu', 'Savelugu', 'Tamale Metropolis', 'Tatale-Sanguli', 'Tolon', 'Yendi' ),
            'upper-east' => array( 'Bawku Municipal', 'Bawku West', 'Binduri', 'Bolgatanga Municipal', 'Bolgatanga East', 'Bongo', 'Builsa North', 'Builsa South', 'Garun', 'Kassena-Nankana Municipal', 'Kassena-Nankana West', 'Nabdam', 'Pusiga', 'Talensi', 'Tempane' ),
            'upper-west' => array( 'Daffiama-Bussie-Issa', 'Jirapa', 'Lambussie-Karni', 'Lawra', 'Nadowli-Kaleo', 'Nandom', 'Sissala East', 'Sissala West', 'Wa East', 'Wa Municipal', 'Wa West' ),
            'bono' => array( 'Banda', 'Berekum East', 'Berekum West', 'Dormaa Central', 'Dormaa East', 'Dormaa West', 'Jaman North', 'Jaman South', 'Sunyani Municipal', 'Sunyani West', 'Tain', 'Wenchi' ),
            'ahafo' => array( 'Asunafo North', 'Asunafo South', 'Asutifi North', 'Asutifi South', 'Bechem', 'Tano North', 'Tano South' ),
            'bono-east' => array( 'Atebubu-Amantin', 'Kintampo North', 'Kintampo South', 'Nkoranza North', 'Nkoranza South', 'Pru East', 'Pru West', 'Sene East', 'Sene West', 'Techiman Municipal', 'Techiman North' ),
            'oti' => array( 'Biakoye', 'Jasikan', 'Kadjebi', 'Krachi East', 'Krachi Nchumuru', 'Krachi West', 'Nkwanta North', 'Nkwanta South' ),
            'north-east' => array( 'Bunkpurugu-Nyankpanduri', 'Chereponi', 'Mamprugu-Moagduri', 'East Mamprusi', 'West Mamprusi', 'Yunyoo-Nasuan' ),
            'savannah' => array( 'Bole', 'Central Gonja', 'East Gonja', 'North Gonja', 'North East Gonja', 'Sawla-Tuna-Kalba', 'West Gonja' ),
            'western-north' => array( 'Aowin', 'Bia East', 'Bia West', 'Bibiani-Anhwiaso-Bekwai', 'Bodi', 'Juaboso', 'Sefwi-Akontombra', 'Sefwi-Wiawso', 'Suaman' ),
        );
    }
}

Ghana_Districts::get_instance();