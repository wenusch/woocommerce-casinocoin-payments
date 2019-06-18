jQuery(document).ready(function($) {

	function wc_gateway_csc_qrcode(account, tag, amount) {
		$('#wc_csc_qrcode').empty();
		var qrcode = new QRCode(document.getElementById("wc_csc_qrcode"), {
			text: "https://w/send?to=" + account + "&dt=" + tag + "&amount=" + amount,
			width: 256,
			height: 256,
			colorDark : "#000000",
			colorLight : "#ffffff",
			correctLevel : QRCode.CorrectLevel.M
		});
	}

	var pollTimer = setInterval(function(){
		jQuery.ajax({
			"url": ajax_object.ajax_url,
			"data": { "action": "csc_checkout", "order_id": ajax_object.order_id },
			"method": "POST",
			"dataType": "json",
			"success": function(data) {
				var regenqr=false;
				if ($('#csc_account').text() != data.csc_account) {
					$('#csc_account').text(data.csc_account);
					regenqr=true;
				}
				if ($('#destination_tag').text() != data.tag) {
					$('#destination_tag').text(data.tag);
					regenqr=true;
				}
				if ($('#csc_total').text() != data.csc_total) {
					$('#csc_total').text(data.csc_total);
				}
				if ($('#csc_received').text() != data.csc_received) {
					$('#csc_received').text(data.csc_received);
				}
				if ($('#csc_remaining').text() != data.csc_remaining) {
					$('#csc_remaining').text(data.csc_remaining);
					regenqr=true;
				}
				if ($('#csc_status').text() != data.status) {
					$('#csc_status').text(data.status);
				}
				if (data.raw_status != 'pending') {
					clearTimeout(pollTimer);
					$('#wc_csc_qrcode').empty();
				} else if (regenqr) {
					wc_gateway_csc_qrcode(data.csc_account, data.tag, data.csc_remaining);
				}
			}
		});
	}, 4000);

	wc_gateway_csc_qrcode(
		$('#csc_account').text(),
		$('#destination_tag').text(),
		$('#csc_remaining').text()
	);
});
