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
            case 'wc-pending':
                return __('Pending payment', 'wc-gateway-xrp');
            case 'wc-processing':
                return __('Processing (Paid)', 'wc-gateway-xrp');
            case 'wc-on-hold':
                return __('On hold', 'wc-gateway-xrp');
            case 'wc-completed':
                return __('Completed', 'wc-gateway-xrp');
            case 'wc-cancelled':
                return __('Cancelled', 'wc-gateway-xrp');
            case 'wc-refunded':
                return __('Refunded', 'wc-gateway-xrp');
            case 'wc-failed':
                return __('Failed', 'wc-gateway-xrp');
            default:
                return __('Unknown', 'wc-gateway-xrp');
        }
    }
}
