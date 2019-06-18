<?php

class WCCSC_Helpers
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
                return __('Pending payment', 'wc-gateway-csc');
            case 'processing':
                return __('Processing (Paid)', 'wc-gateway-csc');
            case 'on-hold':
                return __('On hold', 'wc-gateway-csc');
            case 'completed':
                return __('Completed', 'wc-gateway-csc');
            case 'cancelled':
                return __('Cancelled', 'wc-gateway-csc');
            case 'refunded':
                return __('Refunded', 'wc-gateway-csc');
            case 'failed':
                return __('Failed', 'wc-gateway-csc');
            case 'overpaid':
                return __('Overpaid', 'wc-gateway-csc');
            default:
                return __('Unknown', 'wc-gateway-csc');
        }
    }
}
