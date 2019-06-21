<h2><?php _e('CSC payment details', 'wc-gateway-csc'); ?></h2>
<div id="wc_csc_qrcode" style="padding-top: 10px; padding-bottom: 20px;"></div>
<table class="woocommerce-table shop_table csc_info">
    <tbody>
    <tr>
        <th><?php _e('CSC Account', 'wc-gateway-csc'); ?></th>
        <td id="csc_account" style="text-transform: none !important"><?php echo esc_html(get_option('woocommerce_csc_settings')['csc_account']) ?></td>
    </tr>
    <tr>
        <th><?php _e('Destination tag', 'wc-gateway-csc'); ?></th>
        <td id="destination_tag"><?php echo esc_html($order->get_meta('destination_tag', true)) ?></td>
    </tr>
    <tr>
        <th><?php _e('CSC total', 'wc-gateway-csc'); ?></th>
        <td id="csc_total"><?php echo esc_html(round($order->get_meta('total_amount', true), 8)) ?></td>
    </tr>
    <tr>
        <th><?php _e('CSC received', 'wc-gateway-csc'); ?></th>
        <td id="csc_received"><?php echo esc_html(round($order->get_meta('delivered_amount', true), 8)) ?></td>
    </tr>
    <tr>
        <th><?php _e('CSC left to pay', 'wc-gateway-csc'); ?></th>
        <td id="csc_remaining"><?php echo esc_html($remaining) ?></td>
    </tr>
    <tr>
        <th><?php _e('Order status', 'wc-gateway-csc'); ?></th>
        <td id="csc_status"><?php echo esc_html(WC_Payment_CSC::get_instance()->helpers->wc_pretty_status($order->get_status())) ?></td>
    </tr>
    </tbody>
</table>
