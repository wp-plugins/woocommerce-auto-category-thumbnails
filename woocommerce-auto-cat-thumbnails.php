<?php
/*
Plugin Name: WooCommerce Auto Category Thumbnails
Plugin URI: http://codebyshellbot.com/wordpress-plugins/woocommerce-auto-category-thumbnails/
Description: Automatically display a relevant product image if category thumbnails are not set.
Version: 1.1
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

  /**
   * Add our various hooks and filters as soon as possible.
   *
   * @since 1.0
   */
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'remove_default' ) );
        add_action( 'woocommerce_before_subcategory_title', array( $this, 'auto_category_thumbnail' ) );
        add_action( 'woocommerce_settings_tabs_sbo_wcact', array( $this, 'settings_tab' ) );
        add_action( 'woocommerce_update_options_sbo_wcact', array( $this, 'update_settings' ) );

        add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
    }

  /**
   * Remove WooCommerce default action to replace with our own.
   *
   * @since 1.0
   */
    public function remove_default() {
        remove_action( 'woocommerce_before_subcategory_title', 'woocommerce_subcategory_thumbnail' );
    }

    /**
     * Replace category placeholders with product images.
     *
     * @param object $cat
     * @since 1.0
     */
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

        $wcact_settings = get_option( 'wcact_settings' );

        //Random or latest image?
        $query_args['orderby'] = $wcact_settings['orderby'];

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

    /**
     * Add plugin settings tab to WooCommerce settings page.
     *
     * @param array $settings_tab
     * @return array $settings_tab
     * @since 1.1
     */
    function add_settings_tab( $settings_tabs ) {
        $settings_tabs['sbo_wcact'] = __( 'Auto Category Thumbnails', 'wc-auto-category-thumbnails' );
        return $settings_tabs;
    }

    /**
     * Define fields for plugin settings tab.
     *
     * @since 1.1
     */
    function get_settings() {

        $settings = array(
            'section_title' => array(
                'name'     => __( 'WC Auto Category Thumbnails', 'woocommerce-settings-tab-demo' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wcact_settings[title]',
            ),
            'orderby' => array(
                'name' => __( 'Image to use', 'woocommerce-settings-tab-demo' ),
                'type' => 'radio',
                'desc' => __( 'Which product image should be displayed for each category?', 'woocommerce-settings-tab-demo' ),
                'std' => 'rand',
                'id'   => 'wcact_settings[orderby]',
                'options' => array(
                    'rand' => 'Random',
                    'date' => 'Latest',
                ),
            ),
            'section_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wcact_settings[sectionend]',
            )
        );
        return apply_filters( 'wcact_settings', $settings );
    }

    /**
     * Add fields defined in get_settings() to plugin settings tab.
     *
     * @since 1.1
     */
    function settings_tab() {
        woocommerce_admin_fields( $this->get_settings() );
    }

    /**
     * Save settings from plugin tab.
     *
     * @since 1.1
     */
    function update_settings() {
        woocommerce_update_options( $this->get_settings() );
    }

}

new SB_WC_Auto_Category_Thumbnails();
