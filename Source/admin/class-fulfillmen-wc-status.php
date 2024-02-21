<?php // 
/** 
 * Register new status
 *  
**/
// New order status AFTER woo 2.2
add_action( 'init', 'register_my_new_order_statuses' );

function register_my_new_order_statuses() {
    register_post_status( 'wc-fulfilled', array(
        'label'                     => _x( 'Fulfilled', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Fulfilled <span class="count">(%s)</span>', 'Fulfilled<span class="count">(%s)</span>', 'woocommerce' )
    ) );
}

add_filter( 'wc_order_statuses', 'my_new_wc_order_statuses' );

// Register in wc_order_statuses.
function my_new_wc_order_statuses( $order_statuses ) {
    $order_statuses['wc-fulfilled'] = _x( 'Fulfilled', 'Order status', 'woocommerce' );

    return $order_statuses;
}