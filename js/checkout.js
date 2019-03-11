jQuery(document).ready(function($) {

	var pollTimer = setInterval(function(){
		jQuery.ajax({
			"url": ajax_object.ajax_url,
			"data": { "action": "xrp_checkout", "order_id": ajax_object.order_id },
			"method": "POST",
			"dataType": "json",
			"success": function(data) {
				$('#xrp_account').text(data.xrp_account);
				$('#destination_tag').text(data.tag);
				$('#xrp_total').text(data.xrp_total);
				$('#xrp_received').text(data.xrp_received);
				$('#xrp_remaining').text(data.xrp_remaining);
				$('#xrp_qr').attr('src', data.qr);
				$('#xrp_status').text(data.status);

				if (data.raw_status != 'wc-pending') {
					clearTimeout(pollTimer);
					$('#xrp_qr').remove();
				}
			}
		});
	}, 4000);


});
