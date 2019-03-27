<?php

class WCXRP_Helpers
{
    /**
     * Helper to print pretty statuses.
     * @param $status
     * @return string
     */
    function wc_pretty_status($status)
    {
        switch ($status) {
            case 'pending':
                return __('Pending payment', 'wc-gateway-xrp');
            case 'processing':
                return __('Processing (Paid)', 'wc-gateway-xrp');
            case 'on-hold':
                return __('On hold', 'wc-gateway-xrp');
            case 'completed':
                return __('Completed', 'wc-gateway-xrp');
            case 'cancelled':
                return __('Cancelled', 'wc-gateway-xrp');
            case 'refunded':
                return __('Refunded', 'wc-gateway-xrp');
            case 'failed':
                return __('Failed', 'wc-gateway-xrp');
            case 'overpaid':
                return __('Overpaid', 'wc-gateway-xrp');
            default:
                return __('Unknown', 'wc-gateway-xrp');
        }
    }
}
