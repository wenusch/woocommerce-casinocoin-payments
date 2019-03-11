jQuery(document).ready(function($) {

	var pollTimer = setInterval(function(){
		jQuery.ajax({
			"url": ajax_object.ajax_url,
			"data": { "action": "xrp_checkout", "order_id": ajax_object.order_id },
			"method": "POST",
			"dataType": "json",
			"success": function(data) {
				if ($('#xrp_account').text() != data.xrp_account) {
					$('#xrp_account').text(data.xrp_account);
				}
				if ($('#destination_tag').text() != data.tag) {
					$('#destination_tag').text(data.tag);
				}
				if ($('#xrp_total').text() != data.xrp_total) {
					$('#xrp_total').text(data.xrp_total);
				}
				if ($('#xrp_received').text() != data.xrp_received) {
					$('#xrp_received').text(data.xrp_received);
				}
				if ($('#xrp_remaining').text() != data.xrp_remaining) {
					$('#xrp_remaining').text(data.xrp_remaining);
				}
				if ($('#xrp_qr').attr('src') != data.qr) {
					$('#xrp_qr').attr('src', data.qr);
				}
				if ($('#xrp_status').text() != data.status) {
					$('#xrp_status').text(data.status);
				}
				if (data.raw_status != 'wc-pending') {
					clearTimeout(pollTimer);
					$('#xrp_qr').remove();
				}
			}
		});
	}, 4000);


});
