<?php


class WCXRP_Helpers
{
    /**
     * Ugly helper to print pretty statuses.
     * @param $status
     * @return string
     */
    function wc_pretty_status($status)
    {
        switch ($status) {
            case 'wc-pending':
                return 'Pending payment';
            case 'wc-processing':
                return 'Processing (Paid)';
            case 'wc-on-hold':
                return 'On hold';
            case 'wc-completed':
                return 'Completed';
            case 'wc-cancelled':
                return 'Cancelled';
            case 'wc-refunded':
                return 'Refunded';
            case 'wc-failed':
                return 'Failed';
            default:
                return 'Unknown';
        }
    }
}
