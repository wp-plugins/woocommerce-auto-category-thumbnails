<?php
/*
Plugin Name: WooCommerce Auto Category Thumbnails
Plugin URI: http://codebyshellbot.com/wordpress-plugins/woocommerce-auto-category-thumbnails/
Description: Automatically display a relevant product image if category thumbnails are not set.
Version: 1.0
Author: Shellbot
Author URI: http://codebyshellbot.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

class SB_WC_Auto_Category_Thumbnails {

    public function __construct() {
        add_action( 'init', array( $this, 'load_settings' ) );
        add_action( 'plugins_loaded', array( $this, 'remove_default' ) );
        add_action( 'woocommerce_before_subcategory_title', array( $this, 'auto_category_thumbnail' ) );
        
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
    }

    public function remove_default() {
        remove_action( 'woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail' );
    }

    public function auto_category_thumbnail( $cat ) {
        
        //If a thumbnail is explicitly set for this category, we don't need to do anything else
        if ( get_woocommerce_term_meta( $cat->term_id, 'thumbnail_id', true ) ) {
                woocommerce_subcategory_thumbnail( $cat );
                return;
        }

        //Otherwise build our query
        $query_args = array(
            'meta_query' => array(
                array(
                    'key' => '_visibility',
                    'value' => array( 'catalog', 'visible' ),
                    'compare' => 'IN'
                )
            ),
            'post_status' => 'publish',
            'post_type' => 'product',
            'posts_per_page' => 1,
            'tax_query' => array(
                array(
                    'field' => 'id',
                    'taxonomy' => 'product_cat',
                    'terms' => $cat->term_id
                )
            )
        );
        
        //Random or latest image?
        $query_args['orderby'] = $this->settings['orderby'];

        //Query DB
        $product = get_posts( $query_args );
        
        //If matching product found, check for a thumbnail, otherwise fall back
        if( $product && has_post_thumbnail( $product[0]->ID ) ) {
            echo get_the_post_thumbnail( $product[0]->ID, 'shop_thumbnail' );
        } else {
            woocommerce_subcategory_thumbnail( $cat );
            return;
        }
    }
    
    function add_settings_page() {
	add_options_page( 'WC Auto Cat Thumbs', 'WC Auto Cat Thumbs', 'manage_options', 'wcact_settings', array( $this, 'show_settings_page' ) );
    }

    function show_settings_page() {
        include( 'wcact-settings-page.php' );
    }
    
    function register_settings() {
        register_setting( 'wcact_settings', 'wcact_settings' );
        
        add_settings_section( 'wcact_global', 'Settings', '', 'wcact_settings' );
        add_settings_field( 'orderby', 'Which product image should be displayed for each category?', array( &$this, 'field_orderby' ), 'wcact_settings', 'wcact_global' );
    }
    
    function load_settings() {
        //Set defaults
        $defaults = array(
            'orderby' => 'rand',
        );
        
        //Get existing settings, if any
        $this->settings = (array) get_option( 'wcact_settings' );

        // Merge with defaults
        $this->settings = array_merge( $defaults, $this->settings );
    }
    
    function field_orderby() {
        $value = esc_attr( $this->settings['orderby'] );
        ?>
            <label for="orderbyrand">Random</label>
            <input type="radio" name="wcact_settings[orderby]" id="orderbyrand" value="rand" <?php echo ($value == 'rand' ? 'checked="checked"' : ''); ?>>
            <label for="orderbydate">Latest</label>
            <input type="radio" name="wcact_settings[orderby]" id="orderbydate" value="date" <?php echo ($value == 'date' ? 'checked="checked"' : ''); ?>>
        <?php
    }
    
}

new SB_WC_Auto_Category_Thumbnails();