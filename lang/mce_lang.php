<?php

$strings = 'tinyMCE.addI18n({' . _WP_Editors::$mce_locale . ':{
    wp_braintree:{
        title: "' . esc_js( __( 'WP BrainTree', 'wp_braintree_lang' ) ) . '",
        shortcode: "' . esc_js( __( 'WP BrainTree Shortcode', 'wp_braintree_lang' ) ) . '",
        both_required: "' . esc_js( __( 'Both input fields are required.', 'wp_braintree_lang' ) ) . '",
        item_name_label: "' . esc_js( __( 'Item Name', 'wp_braintree_lang' ) ) . '",
        item_name_text: "' . esc_js( __( 'Specify the Item Name.', 'wp_braintree_lang' ) ) . '",
        item_amount_label: "' . esc_js( __( 'Item Price', 'wp_braintree_lang' ) ) . '",
        item_amount_text: "' . esc_js( __( 'Specify the Item Price.', 'wp_braintree_lang' ) ) . '",
        insert_shortcode: "' . esc_js( __( 'Insert Button Shortcode', 'wp_braintree_lang' ) ) . '",
    }
	
}})';	
?>