<h2><?php _e('XRP payment details', 'wc-gateway-xrp'); ?></h2>
<div id="wc_xrp_qrcode"></div>
<table class="woocommerce-table shop_table xrp_info">
    <tbody>
    <tr>
        <th><?php _e('XRP Account', 'wc-gateway-xrp'); ?></th>
        <td id="xrp_account"><?php _e( WC_Payment_XRP::get_instance()->gateway->settings['xrp_account'] , 'wc-gateway-xrp') ?></td>
    </tr>
    <tr>
        <th><?php _e('Destination tag', 'wc-gateway-xrp'); ?></th>
        <td id="destination_tag"><?php echo get_post_meta($order_id, 'destination_tag', true) ?></td>
    </tr>
    <tr>
        <th><?php _e('XRP total', 'wc-gateway-xrp'); ?></th>
        <td id="xrp_total"><?php echo round(get_post_meta($order_id, 'total_amount', true), 6) ?></td>
    </tr>
    <tr>
        <th><?php _e('XRP received', 'wc-gateway-xrp'); ?></th>
        <td id="xrp_received"><?php echo round(get_post_meta($order_id, 'delivered_amount', true), 6) ?></td>
    </tr>
    <tr>
        <th><?php _e('XRP left to pay', 'wc-gateway-xrp'); ?></th>
        <td id="xrp_remaining"><?php echo $remaining ?></td>
    </tr>
    <tr>
        <th><?php _e('Order status', 'wc-gateway-xrp'); ?></th>
        <td id="xrp_status"><?php echo WC_Payment_XRP::get_instance()->helpers->wc_pretty_status(get_post_status($order_id)) ?></td>
    </tr>
    </tbody>
</table>