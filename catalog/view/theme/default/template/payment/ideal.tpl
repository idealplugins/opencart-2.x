<?php

/**

	iDEALplugins.nl
    TargetPay plugin v1.1 for Opencart 1.5+

    (C) Copyright Yellow Melon 2013

 	@file 		TargetPay Catalog Template
	@author		Yellow Melon B.V. / www.idealplugins.nl

 */

require_once ("system/helper/targetpay.class.php");
$targetPay = new TargetPayCore ("IDE", 94103, "e59dbd219e068daade7139be42c5dfd5", "nl", false);
$bankList = $targetPay->getBankList();
?>

<h2><?php echo $text_credit_card; ?></h2>
<div class="content" id="payment">
  <table class="form">
  	<tr>
      <td height=10></td>
    </tr>
    <tr>
      <td><?php echo $entry_bank_id; ?></td>
      <td><select name="bank_id">
          <?php 
          foreach ($bankList as $id => $name) {
          	echo "<option value=\"".$id."\">".$name."</option>\r\n";
          	}
          ?>
        </select></td>
    </tr>
  </table>
</div>
<div class="buttons">
  <div class="right">
    <input type="hidden" name="custom" value="<?php echo $custom; ?>" />   
    <input type="button" value="<?php echo $button_confirm; ?>" id="button-confirm" class="button" />
  </div>
</div>
<script type="text/javascript"><!--
$('#button-confirm').bind('click', function() {
	$.ajax({
		url: 'index.php?route=payment/ideal/send',
		type: 'post',
		data: $('#payment :input'),
		dataType: 'json',		
		beforeSend: function() {
			$('#button-confirm').attr('disabled', true);
			$('#payment').before('<div class="attention"><img src="catalog/view/theme/default/image/loading.gif" alt="" /> <?php echo $text_wait; ?></div>');
		},
		complete: function() {
			$('#button-confirm').attr('disabled', false);
			$('.attention').remove();
		},				
		success: function(json) {
			if (json['error']) {
				alert(json['error']);
			}
			
			if (json['success']) {
				location = json['success'];
			}
		}
	});
});
//--></script> 
