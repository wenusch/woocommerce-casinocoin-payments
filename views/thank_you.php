<h2><?php _e('XRP payment details', 'wc-gateway-xrp'); ?></h2>
<div id="wc_xrp_qrcode"></div>
<table class="woocommerce-table shop_table xrp_info">
    <tbody>
    <tr>
        <th><?php _e('XRP Account', 'wc-gateway-xrp'); ?></th>
        <td id="xrp_account" style="text-transform: none !important"><?php echo esc_html(get_option('woocommerce_xrp_settings')['xrp_account']) ?></td>
    </tr>
    <tr>
        <th><?php _e('Destination tag', 'wc-gateway-xrp'); ?></th>
        <td id="destination_tag"><?php echo esc_html($order->get_meta('destination_tag', true)) ?></td>
    </tr>
    <tr>
        <th><?php _e('XRP total', 'wc-gateway-xrp'); ?></th>
        <td id="xrp_total"><?php echo esc_html(round($order->get_meta('total_amount', true), 6)) ?></td>
    </tr>
    <tr>
        <th><?php _e('XRP received', 'wc-gateway-xrp'); ?></th>
        <td id="xrp_received"><?php echo esc_html(round($order->get_meta('delivered_amount', true), 6)) ?></td>
    </tr>
    <tr>
        <th><?php _e('XRP left to pay', 'wc-gateway-xrp'); ?></th>
        <td id="xrp_remaining"><?php echo esc_html($remaining) ?></td>
    </tr>
    <tr>
        <th><?php _e('Order status', 'wc-gateway-xrp'); ?></th>
        <td id="xrp_status"><?php echo esc_html(WC_Payment_XRP::get_instance()->helpers->wc_pretty_status($order->get_status())) ?></td>
    </tr>
    </tbody>
</table>
