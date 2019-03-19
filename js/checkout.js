jQuery(document).ready(function($) {

	function wc_gateway_xrp_qrcode(account, tag, amount) {
		$('#wc_xrp_qrcode').empty();
		var qrcode = new QRCode(document.getElementById("wc_xrp_qrcode"), {
			text: "https://ripple.com/send?to=" + account + "&dt=" + tag + "&amount=" + amount,
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
			"data": { "action": "xrp_checkout", "order_id": ajax_object.order_id },
			"method": "POST",
			"dataType": "json",
			"success": function(data) {
				var regenqr=false;
				if ($('#xrp_account').text() != data.xrp_account) {
					$('#xrp_account').text(data.xrp_account);
					regenqr=true;
				}
				if ($('#destination_tag').text() != data.tag) {
					$('#destination_tag').text(data.tag);
					regenqr=true;
				}
				if ($('#xrp_total').text() != data.xrp_total) {
					$('#xrp_total').text(data.xrp_total);
				}
				if ($('#xrp_received').text() != data.xrp_received) {
					$('#xrp_received').text(data.xrp_received);
				}
				if ($('#xrp_remaining').text() != data.xrp_remaining) {
					$('#xrp_remaining').text(data.xrp_remaining);
					regenqr=true;
				}
				if ($('#xrp_status').text() != data.status) {
					$('#xrp_status').text(data.status);
				}
				if (data.raw_status != 'wc-pending') {
					clearTimeout(pollTimer);
					$('#wc_xrp_qrcode').empty();
				}
				if (regenqr) {
					wc_gateway_xrp_qrcode(data.xrp_account, data.tag, data.xrp_remaining);
				}
			}
		});
	}, 4000);

	wc_gateway_xrp_qrcode(
		$('#xrp_account').text(),
		$('#destination_tag').text(),
		$('#xrp_remaining').text()
	);
});
