<?php
/*
Plugin Name: Churnly Update Existing Subscriptions
Description: Adds JS script for adding scheduled events for existing subscriptions
Version: 0.1
Author: The team at PIE
Author URI: http://pie.co.de
License:     GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

/* PIE\ChurnlyUpdateExistingSubs is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version.

PIE\ChurnlyUpdateExistingSubs is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with PIE\ChurnlyUpdateExistingSubs. If not, see https://www.gnu.org/licenses/gpl-3.0.en.html */

namespace PIE\ChurnlyUpdateExistingSubs;

if ( ! class_exists( 'Churnly_Update_Existing_Subs' ) ) {

  class Churnly_Update_Existing_Subs {

    /**
     * Product IDs to use in query when returning which subscriptions to update
     * @var array
     */
    $product_ids = array( '27371', '27370', '27369' );

    /**
     * Load in JS for admin screen
     */
    function enqueue_admin_scripts() {
    	global $current_screen;
    	if ( 'tools_page_action-scheduler' === $current_screen->id ) {
    		wp_enqueue_script( 'churnly-update-existing-subs', plugins_url( '/js/update-events.js', __FILE__ ), array( 'jquery' ), '0.1', true );
    		wp_localize_script( 'churnly-update-existing-subs', 'churnly_fix_data', array(
    			'import_button_text' => __( 'Update Churnly Events', 'churnly-update-existing-subs' ),
    			'importing_text'     => __( 'Updating...', 'churnly-update-existing-subs' ),
    		) );
    	}
    }
    add_action( 'admin_enqueue_scripts', array( 'PIE\ChurnlyUpdateExistingSubs\Churnly_Update_Existing_Subs', 'enqueue_admin_scripts' ) );

    /**
     * Hook in AJAX requests
     */
    function hook_up_ajax() {
    	add_action( 'wp_ajax_get_subscriptions', array( 'PIE\ChurnlyUpdateExistingSubs\Churnly_Update_Existing_Subs', 'get_subscriptions' ) );
    	add_action( 'wp_ajax_update_churnly_events', array( 'PIE\ChurnlyUpdateExistingSubs\Churnly_Update_Existing_Subs', 'update_events_for_churnly' ) );
    }
    add_action( 'init', array( 'PIE\ChurnlyUpdateExistingSubs\Churnly_Update_Existing_Subs', 'hook_up_ajax' ) );

    /**
     * Get all active subscriptions for the given product IDs
     */
    function get_subscriptions() {
    	global $wpdb;
      $product_ids   = implode( ',', $this->product_ids );
    	$subscriptions = $wpdb->get_col( "
    SELECT DISTINCT order_items.order_id FROM {$wpdb->prefix}woocommerce_order_items as order_items
    	LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS itemmeta ON order_items.order_item_id = itemmeta.order_item_id
    	LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        WHERE posts.post_type = 'shop_subscription'
    	AND itemmeta.meta_value IN ( {$product_ids} )
    	AND itemmeta.meta_key   IN ( '_variation_id', '_product_id' )
    	AND posts.post_status = 'wc-active'"
    	);

    	wp_send_json_success( $subscriptions );
    }

    /**
     * Add card expiration events for existing subscriptions
     */
    function update_events_for_churnly() {
    	$subs      = $_POST['subscriptions'];
    	$churnly   = new \Churnly\Integration\WooCommerce;
    	$completed = array( 'processed_subscriptions' => array() );
    	foreach ( $subs as $subscription ) {
    		$churnly->schedule_expiration_emails( $subscription );
    		$completed['processed_subscriptions'][] = $subscription;
    	}
    	wp_send_json_success( $completed );
    }
  }
  new Churnly_Update_Existing_Subs;
}
