jQuery(document).ready(function() {
	/* Add currency selector */
	jQuery('#add_currency_selector_btn').click(function() {
		var last_id = jQuery("#currency_selectors select").last().attr('id');
		var pattern = /wp_uci_currency_/i;
		last_id = last_id.replace(pattern,"");
		last_id = parseInt(last_id) + 1;
		jQuery("#currency_selectors").append('<label>Currency '+last_id+': </label><select id="wp_uci_currency_'+last_id+'" name="wp_uci_currency_'+last_id+'"> <option value="">Currency not selected...</option> <option value="978">EUR</option> <option value="840">USD</option> <option value="643">RUB</option> <option value="946">RON</option> <option value="980">UAH</option> <option value="498">MDL</option> <option value="784">AED</option> <option value="008">ALL</option> <option value="051">AMD</option> <option value="036">AUD</option> <option value="944">AZN</option> <option value="975">BGN</option> <option value="974">BYR</option> <option value="124">CAD</option> <option value="756">CHF</option> <option value="156">CNY</option> <option value="203">CZK</option> <option value="208">DKK</option> <option value="826">GBP</option> <option value="981">GEL</option> <option value="344">HKD</option> <option value="191">HRK</option> <option value="348">HUF</option> <option value="376">ILS</option> <option value="356">INR</option> <option value="352">ISK</option> <option value="392">JPY</option> <option value="417">KGS</option> <option value="410">KRW</option> <option value="414">KWD</option> <option value="398">KZT</option> <option value="440">LTL</option> <option value="428">LVL</option> <option value="807">MKD</option> <option value="458">MYR</option> <option value="578">NOK</option> <option value="554">NZD</option> <option value="985">PLN</option> <option value="941">RSD</option> <option value="752">SEK</option> <option value="972">TJS</option> <option value="934">TMT</option> <option value="949">TRY</option> <option value="860">UZS</option> <option value="960">XDR</option> </select> <br>');
	});
	/* Save informer settings */
	jQuery('#uci_informer_save').click(function() {
		var cur = [];
		var cur_text = [];
		jQuery('#currency_selectors :selected').each(function(i,selected){
			if(jQuery(selected).val()!="") {
				cur[i] = jQuery(selected).val();
				cur_text[i] = jQuery(selected).text();
			}
		});
		var title = jQuery('#uci_informer_title').val();
		var bank = jQuery('#wp_uci_bank').val();
		var bank_text = jQuery('#wp_uci_bank :selected').text();
		var plurl = jQuery('#uci-plurl').val();
		jQuery.ajax({
			type: "POST",
			data: "currency=" + cur + "&title=" + title + "&bank=" + bank + "&act=add",
			url: plurl + "/uci-handler.php",
			dataType: "html",
			success: function(data) {
				jQuery("#uci_noinf").remove();
				jQuery("#the-list").append('<tr id="uci_informer_'+data+'" class="type-post format-standard"><td class="column-title">'+title+'</td><td class="column-title">'+bank_text+'</td><td class="column-title">'+cur_text+'</td><td class="column-title">[excange-informer informer="'+data+'"]</td><td class="column-title"><input id="uci_delete_'+data+'" class="button button-primary" type="button" value="[X]" name="delete"></td></tr>');
				tb_remove();
			},
			error: function(req,answer,err) {
				jQuery("#info_message").html('<div class="error settings-error notice is-dismissible"><p><strong>Error!</strong></p><button class="notice-dismiss" type="button"></button></div>');
			}
		})
	});
	/* Delete informer */
	jQuery('[id^=uci_delete_]').click(function() {
		var id = jQuery(this).attr('id');
		var pattern = /uci_delete_/i;
		id = id.replace(pattern,"");
		var plurl = jQuery('#uci-plurl').val();
		jQuery.ajax({
			type: "POST",
			data: "id=" + id + "&act=del",
			url: plurl + "/uci-handler.php",
			dataType: "html",
			success: function(data) {
				jQuery("#uci_informer_"+id).remove();
			},
			error: function(req,answer,err) {
				jQuery("#info_message_plug").html('<div class="error settings-error notice is-dismissible"><p><strong>Error!</strong></p><button class="notice-dismiss" type="button"></button></div>');
			}
		})
	});
});