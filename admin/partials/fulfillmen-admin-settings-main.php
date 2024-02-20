<?php
$warehouse = array(
    'HZ'   => __( 'BestW - Huizhou', 'fulfillmen' ),
    'USCA'   => __( 'USCA - US&CA Virtual Warehouse', 'fulfillmen' ),
    'BlrW'   => __( 'BlrW - India BLR', 'fulfillmen' ),
    'DelW'   => __( 'Delw - India Delhi', 'fulfillmen' )
);

$orderType = array(
    '1' => __('Warehouse Mode','fulfillmen'),
    '2' =>  __('Dropship Mode','fulfillmen')
);
$settings = array(
        array(
            'name' => __( 'API Configuration', 'fulfillmen' ),
            'type' => 'title',
            'id'   => $prefix . 'general_config_settings'
        ),
        array(
            'id'        => $prefix . 'fulfillmen_userID',
            'name'      => __( 'User ID', 'fulfillmen' ), 
            'type'      => 'text',
            'desc_tip'  => __( ' The numeric account ID of your Fulfillmen Account.', 'fulfillmen')
        ),
        array(
            'id'        => $prefix . 'fulfillmen_apikey',
            'name'      => __( 'API Key', 'fulfillmen' ), 
            'type'      => 'text',
            'desc_tip'  => __( ' Fulfillmen Account API key. ', 'fulfillmen')
        ),
        array(
            'id'        => $prefix . 'fulfillmen_username',
            'name'      => __( 'Account Username/Email', 'fulfillmen' ), 
            'type'      => 'text',
            'desc_tip'  => __( ' Fulfillmen Account Username. ', 'fulfillmen')
        ),
        array(
            'id'        => $prefix . 'fulfillmen_password',
            'name'      => __( 'Account Password', 'fulfillmen' ), 
            'type'      => 'password',
            'class'     => 'pass',
            'desc_tip'  => __( ' Fulfillmen Account Password. ', 'fulfillmen')
        ),
        array(
            'id'        => $prefix . 'fulfillmen_store',
            'name'      => __( 'Store Name', 'fulfillmen' ), 
            'type'      => 'text',            
            'desc_tip'  => __( ' Store Name ', 'fulfillmen')
        ),
        array(
            'id'        => '',
            'name'      => __( 'API Configuration', 'fulfillmen' ),
            'type'      => 'sectionend',
            'desc'      => '',
            'id'        => $prefix . 'general_config_settings'
        ),
        array(
            'name' => __( 'Custom Configuration', 'fulfillmen' ),
            'type' => 'title',
            'id'   => $prefix . 'custom_settings',
        ),
        array(
            'id'        => $prefix . 'warehouse_ID',
            'name'      => __( 'Warehouse', 'fulfillmen' ), 
            'type'      => 'select',
            'class'     => 'wc-enhanced-select',
            'options'   => $warehouse,
            'desc_tip'  => __( ' Primary Warehouse ID', 'fulfillmen')
        ),
        array(
            'id'        => $prefix . 'order_mode',
            'name'      => __( 'Order Mode', 'fulfillmen' ), 
            'type'      => 'select',
            'class'     => 'wc-enhanced-select',
            'options'   => $orderType,
            'desc_tip'  => __( ' Select the Order Mode', 'fulfillmen'),
            'default'   => '1'
        ),
        array(
            'id'        => $prefix . 'customtrackingurl',
            'name'      => __( 'Custom Tracking URL', 'fulfillmen' ), 
            'type'      => 'text',
            'desc_tip'  => __( ' Please enter your custom tracking URL here', 'fulfillmen'),
            'default'   => 'https://track.safeline56.com/trackapi.php?tr='
        ),
        array(
            'id'        => $prefix . 'automation_fw',
            'name'      => __( 'Automatic Tracking Update Processing', 'fulfillmen' ),
            'type'      => 'checkbox',
            'desc'  => __( ' Enable this option if you want to enable automatic tracking number update', 'fulfillmen' ),
            'default'   => 'no'
        ), 
        array(
            'id'        => $prefix . 'automation_fw_orders',
            'name'      => __( 'Automatic Order Processing', 'fulfillmen' ),
            'type'      => 'checkbox',
            'desc'  => __( ' Enable automatic order collection, will run once every day and process orders placed at least 24 hours ago', 'fulfillmen' ),
            'default'   => 'no'
        ),   
        array(
            'id'        => $prefix . 'process_all_geos',
            'name'      => __( 'Process Orders From Unconfigured Geos', 'fulfillmen' ),
            'type'      => 'checkbox',
            'desc'  => __( ' Enable order processing for orders bound for geo locations which are not configured', 'fulfillmen' ),
            'default'   => 'yes'
        ),
        /*array(
            'id'        => $prefix . 'ff_ast_integration',
            'name'      => __( 'Zorem Advanced Shipment Tracking Integration', 'fulfillmen' ),
            'type'      => 'checkbox',
            'desc'  => __( ' Check this box if you want to enable Zorem AST plugin integration.', 'fulfillmen' ),
            'default'   => 'no'
        ), */      
        array(
            'id'        => '',
            'name'      => __( 'Custom Configuration', 'fulfillmen' ),
            'type'      => 'sectionend',
            'desc'      => '',
            'id'        => $prefix . 'custom_settings',
        ),  
        /*array(
            'id'        => $prefix . 'webhook_fw_orders',
            'name'      => __( 'Instant Order Processing', 'fulfillmen' ),
            'type'      => 'checkbox',
            'desc'  => __( ' Enable webhook based order processing to push the orders to fulfillmen as soon as the order is marked as paid. ', 'fulfillmen' ),
            'default'   => 'no'
        ),
        array(
            'id'        => $prefix . 'push_mailnotification',
            'name'      => __( 'Email Notifications for Fulfilled Ordes', 'fulfillmen' ),
            'type'      => 'checkbox',
            'desc'  => __( ' Only enable this option if you have disabled the order complete email notification in woocommerce and would like to shoot an email once the order is marked shipped.', 'fulfillmen' ),
            'default'   => 'no'
        ), */
                              
    );
?>