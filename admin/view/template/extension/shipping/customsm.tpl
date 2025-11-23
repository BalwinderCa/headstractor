<meta name="referrer" content="no-referrer-when-downgrade">
<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
      <?php if($licensed_md5 == 'd9a22d7a8178d5b42a8750123cbfe5b1'){ ?>
        <button id="save_and_stay" data-toggle="tooltip" title="<?php echo $button_stay; ?>" class="btn btn-success"><i class="fa fa-save"></i></button>
        <button type="submit" form="form-shipping" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <?php } ?>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title_normal; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>
  <div class="container-fluid">
    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
  <?php } ?>
  <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit; ?></h3>
      </div>
      <div class="panel-body">
      <?php if($licensed_md5 == 'd9a22d7a8178d5b42a8750123cbfe5b1'){ ?>      
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-shipping" class="form-horizontal">
      <ul class="nav nav-tabs">
            <li class="active"><a href="#tab-general" data-toggle="tab"><span class="fa fa-edit"></span> <?php echo $tab_general; ?></a></li>
            <li><a href="#tab-settings" data-toggle="tab"><span class="fa fa-cog"></span> <?php echo $tab_settings; ?></a></li>
            <li><a href="#tab-about" data-toggle="tab"><span class="fa fa-support"></span> <?php echo $tab_about; ?></a></li>            
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="tab-general">
              <ul class="nav nav-tabs" id="language">
                <?php foreach ($languages as $language) { ?>
                <li><a href="#language<?php echo $language['language_id']; ?>" data-toggle="tab"><img src="language/<?php echo $language['code']; ?>/<?php echo $language['code']; ?>.png" title="<?php echo $language['name']; ?>" /> <?php echo $language['name']; ?></a></li>
                <?php } ?>
              </ul>  
        <div class="tab-content">
                <?php foreach ($languages as $language) { ?>
                <div class="tab-pane" id="language<?php echo $language['language_id']; ?>">  
        <div class="form-group required ">              
            <label class="col-sm-2 control-label" for="input-name<?php echo $language['language_id']; ?>"><?php echo $entry_name; ?></label>
            <div class="col-sm-10">             
                <input type="text" name="shipping_customsm_name_<?php echo $language['language_id']; ?>" value="<?php echo ${'shipping_customsm_name_' . $language['language_id']}; ?>" class="form-control" />                
                <?php if (${'error_name_' . $language['language_id']}) { ?>
                <div class="text-danger"><?php echo ${'error_name_' . $language['language_id']}; ?></div>
                <?php } ?>                
                </div>
            </div>            
          <div class="form-group required">                    
            <label class="col-sm-2 control-label" for="input-description<?php echo $language['language_id']; ?>"><span data-toggle="tooltip" title="<?php echo $help_details; ?>"><?php echo $entry_details; ?></span></label>
            <div class="col-sm-10">                         
            <textarea name="shipping_customsm_details_<?php echo $language['language_id']; ?>" id="input-description<?php echo $language['language_id']; ?>" data-toggle="summernote"  class="form-control" title="<?php echo $entry_details; ?>"><?php echo  ${'shipping_customsm_details_' . $language['language_id']}; ?></textarea>                                            
                <?php if (${'error_details_' . $language['language_id']}) { ?>
                <div class="text-danger"><?php echo ${'error_details_' . $language['language_id']}; ?></div>
                <?php } ?>                
                </div>
              </div>           
           <div class="form-group">
            <label class="col-sm-2 control-label" for="input-frontend<?php echo $language['language_id']; ?>"><span data-toggle="tooltip" title="<?php echo $help_frontend; ?>"><?php echo $entry_frontend; ?></span></label>
            <div class="col-sm-10">               
                <input type="text" name="shipping_customsm_frontend_<?php echo $language['language_id']; ?>" value="<?php echo ${'shipping_customsm_frontend_' . $language['language_id']}; ?>" class="form-control" />                                
              </div>
            </div>
           </div>
          <?php } ?>          
         </div>
        </div>
           <div class="tab-pane" id="tab-settings">
           <div class="form-group">
            <label class="col-sm-2 control-label" for="input-total"><span data-toggle="tooltip" title="<?php echo $help_total; ?>"><?php echo $entry_total; ?></span></label>
            <div class="col-sm-10">
              <input type="text" name="shipping_customsm_total" value="<?php echo $shipping_customsm_total; ?>" placeholder="<?php echo $entry_total; ?>" id="input-total" class="form-control" />
            </div>
          </div>             
          <div class="form-group">
          <label class="col-sm-2 control-label" for="input-weight"><span data-toggle="tooltip" title="<?php echo $help_weight; ?>"><?php echo $entry_weight; ?></span></label>
            <div class="col-sm-10">           
           <input type="text" name="shipping_customsm_weight" value="<?php echo $shipping_customsm_weight; ?>" placeholder="<?php echo $entry_weight; ?>" id="input-weight" class="form-control" />         
          </div>
          </div>                             
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-geo-zone"><?php echo $entry_geo_zone; ?></label>
            <div class="col-sm-10">
            <select name="shipping_customsm_geo_zone_id" id="input-geo-zone" class="form-control">
                <option value="0"><?php echo $text_all_zones; ?></option>
                <?php foreach ($geo_zones as $geo_zone) { ?>
                <?php if ($geo_zone['geo_zone_id'] == $shipping_customsm_geo_zone_id) { ?>
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                <?php } else { ?>
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                <?php } ?>
                <?php } ?>
              </select>
                </div>
          </div>           
         <div class="form-group">          	
            <label class="col-sm-2 control-label" for="input-category"><span data-toggle="tooltip" title="<?php echo $help_category; ?>"><?php echo $entry_category; ?></span></label>
            <div class="col-sm-10">             
            <select name="shipping_customsm_category" id="input-category" class="form-control">
                <option value="0"><?php echo $text_all_categories; ?></option>
                <?php foreach ($categories as $category) : ?>
                <?php if ($category['category_id'] == $shipping_customsm_category) : ?>
                <option value="<?php echo $category['category_id']; ?>" selected="selected"><?php echo $category['name']; ?></option>
                <?php else : ?>
                <option value="<?php echo $category['category_id']; ?>"><?php echo $category['name']; ?></option>
                <?php endif; ?>
                <?php endforeach; ?>
               </select>
                </div>
              </div>              
          <div class="form-group">
          <label class="col-sm-2 control-label" for="input-shipping-customsm-all"><span data-toggle="tooltip" title="<?php echo $help_all_stores; ?>"><?php echo $entry_all_stores; ?></span></label>
            <div class="col-sm-10">
              <select name="shipping_customsm_all" id="input-shipping-customsm-all" class="form-control">
                <?php if ($shipping_customsm_all == 'all_stores') { ?>
                <option value="all_stores" selected="selected"><?php echo $text_all_stores; ?></option>
                <?php } else { ?>
                <option value="all_stores"><?php echo $text_all_stores; ?></option>
                <?php } ?>
                <?php if ($shipping_customsm_all == 'stores') { ?>
                <option value="stores" selected="selected"><?php echo $text_stores; ?></option>
                <?php } else { ?>
                <option value="stores"><?php echo $text_stores; ?></option>
                <?php } ?>               
              </select>
            </div>
          </div>            
          <div class="form-group shipping-customsm-all" id="shipping-customsm-all-stores">
            <label class="col-sm-2 control-label"><span data-toggle="tooltip" title="<?php echo $help_store; ?>"><?php echo $entry_store; ?></span></label>
            <div class="col-sm-10">
              <div class="well well-sm" style="height: 150px; overflow: auto;">
                <div class="checkbox">
                  <label>
                    <?php if (in_array(0, $shipping_customsm_store)) { ?>
                    <input type="checkbox" name="shipping_customsm_store[]" value="0" checked="checked" />
                    <?php echo $text_default; ?>
                    <?php } else { ?>
                    <input type="checkbox" name="shipping_customsm_store[]" value="0" />
                    <?php echo $text_default; ?>
                    <?php } ?>
                  </label>
                 </div>
                <?php foreach ($stores as $store) { ?>
                <div class="checkbox">
                  <label>
                    <?php if (in_array($store['store_id'], $shipping_customsm_store)) { ?>
                    <input type="checkbox" name="shipping_customsm_store[]" value="<?php echo $store['store_id']; ?>" checked="checked" />
                    <?php echo $store['name']; ?>
                    <?php } else { ?>
                    <input type="checkbox" name="shipping_customsm_store[]" value="<?php echo $store['store_id']; ?>" />
                    <?php echo $store['name']; ?>
                    <?php } ?>
                    </label>
                    </div>
                  <?php } ?>
                </div>
              </div>
            </div>                   
           <div class="form-group">
            <label class="col-sm-2 control-label" for="input-shipping-customsm-to"><span data-toggle="tooltip" title="<?php echo $help_customer_all; ?>"><?php echo $entry_enable_customers; ?></span></label>
            <div class="col-sm-10">
              <select name="shipping_customsm_to" id="input-shipping-customsm-to" class="form-control">
                <?php if ($shipping_customsm_to == 'customer_all') { ?>
                <option value="customer_all" selected="selected"><?php echo $text_customer_all; ?></option>
                <?php } else { ?>
                <option value="customer_all"><?php echo $text_customer_all; ?></option>
                <?php } ?>
                <?php if ($shipping_customsm_to == 'customer_group') { ?>
                <option value="customer_group" selected="selected"><?php echo $text_customer_group; ?></option>
                <?php } else { ?>
                <option value="customer_group"><?php echo $text_customer_group; ?></option>
                <?php } ?>
                <?php if ($shipping_customsm_to == 'customer') { ?>
                <option value="customer" selected="selected"><?php echo $text_customer; ?></option>
                <?php } else { ?>
                <option value="customer"><?php echo $text_customer; ?></option>
                <?php } ?>
              </select>
            </div>
          </div>          
          <div class="form-group shipping-customsm-to" id="shipping-customsm-to-customer-group">
                  <label class="col-sm-2 control-label"><span data-toggle="tooltip" title="<?php echo $help_customer_group; ?>"><?php echo $entry_customer_group; ?></span></label>
                  <div class="col-sm-10">
                  <div class="well well-sm" style="height: 150px; overflow: auto;">
                    <?php foreach ($customer_groups as $customer_group) { ?>
                    <div class="checkbox">
                      <label>
                        <?php if (in_array($customer_group['customer_group_id'], $shipping_customsm_customer_group_id)) { ?>
                        <input type="checkbox" name="shipping_customsm_customer_group_id[]" value="<?php echo $customer_group['customer_group_id']; ?>" checked="checked" />
                        <?php echo $customer_group['name']; ?>
                        <?php } else { ?>
                        <input type="checkbox" name="shipping_customsm_customer_group_id[]" value="<?php echo $customer_group['customer_group_id']; ?>" />
                        <?php echo $customer_group['name']; ?>
                        <?php } ?>
                      </label>
                    </div>
                    <?php } ?>                    
                  </div>
                </div>
              </div>               
          <div class="form-group shipping-customsm-to" id="shipping-customsm-to-customer">
            <label class="col-sm-2 control-label" for="input-shipping-customsm-customer"><span data-toggle="tooltip" title="<?php echo $help_customer; ?>"><?php echo $entry_customer; ?></span></label>
            <div class="col-sm-10">
              <input type="text" name="shipping_customsm_customers" value="" id="input-shipping-customsm-customer" class="form-control" />
              <div id="shipping_customsm-customer" class="well well-sm" style="height: 150px; overflow: auto;">
                <?php foreach ($customers as $customer) { ?>
                <div id="shipping_customsm-customer<?php echo $customer['customer_id']; ?>"><i class="fa fa-minus-circle"></i> <?php echo $customer['name']; ?> (<?php echo $customer['customer_group']; ?>)<input type="hidden" name="shipping_customsm_customer[]" value="<?php echo $customer['customer_id']; ?>" /></div>
                <?php } ?>
              </div>
            </div>
          </div>                          
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
            <div class="col-sm-10">
            <select name="shipping_customsm_status" id="input-status" class="form-control">
                <?php if ($shipping_customsm_status) { ?>
                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                <?php } else { ?>
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                <?php } ?>
              </select>
             </div>
          </div>                             
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-sort-order"><?php echo $entry_sort_order; ?></label>
            <div class="col-sm-10">
            <input type="text" name="shipping_customsm_sort_order" value="<?php echo $shipping_customsm_sort_order; ?>" id="input-sort-order" class="form-control" />          
         </div>
		</div> 
      </div>    
    <div class="tab-pane" id="tab-about">
        <div class="form-group">
            <label class="col-sm-2 control-label"><?php echo $entry_product_name; ?></label>
            <div class="col-sm-10" style="margin-top: 9px;">
              <?php echo $text_product; ?>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label"><?php echo $entry_doc; ?></label>
            <div class="col-sm-10" style="margin-top: 9px;">
              <a href="https://1drv.ms/w/s!Ap0ey4-_J5WJgaxnH8PrPSUaLcXOFw" target="_blank"><?php echo $text_doc; ?></a>
            </div>
          </div>                 
          <div class="form-group">
            <label class="col-sm-2 control-label"><?php echo $entry_market; ?></label>
            <div class="col-sm-10" style="margin-top: 9px;">
              <a href="https://www.opencart.com/index.php?route=marketplace/extension&filter_member=Mika%20Design" target="_blank"><?php echo $text_market; ?></a>
              </div>
            </div>
            <div class="form-group">
            <label class="col-sm-2 control-label" for="button_support_email"><?php echo $entry_support; ?></label>
            <div class="col-sm-2">
              <a href="<?php echo $support_url; ?>" class="btn btn-ticket btn-block" target="_blank"><i class="fa fa-support"></i> <?php echo $button_support; ?></a>
            </div>
          </div>
          <div class="form-group">
        <div class="col-sm-10">
         <iframe src="https://support.mikadesign.co.uk/information/information-2x.html" style="border:none; width:100%; min-height: 200px; overflow:hidden;"></iframe>           	           
            </div>
          </div>        
        </div>
      </div>
      <div class="col-sm-12 text-center copyright">			
      <p>Copyright &copy mikadesign.co.uk 2011 -
      <script type="text/javascript"><!-- 
      var curdate = new Date();
      var year = curdate.getFullYear();
      document.write(year + " ");
      // --></script>
      </p>
     </div>                              
      </form>
      <?php } ?>
        <?php if($licensed=='none'){ ?>
    <?php echo $license_purchase_thanks; ?>
    <?php if(isset($regerror)){ echo $regerror_quote_msg; } ?>
    <?php if(isset($regerror)){ ?><p style="color:red;">error msg: <?php echo $regerror; ?></p><?php } ?>
      <h2><?php echo $license_registration; ?></h2>
	    <form name="reg" method="post" action="<?php echo $oc_licensing_home; ?>register.php" id="reg" class="form-horizontal">
	        <div class="form-group">
	            <label class="col-sm-2 control-label" for="opencart_email"><?php echo $license_opencart_email; ?></label>
	            <div class="col-sm-10">
	          	  <input name="opencart_email" type="text" autofocus required id="opencart_email" form="reg" class="form-control"></div>
	          </div>
		<?php if(isset($emailmal)&&$regerror=='emailmal'){ ?><p style="color:red;"><?php echo $check_email; ?></p><?php } ?>
	        <div class="form-group">
	            <label class="col-sm-2 control-label" for="order_id"><?php echo $license_opencart_orderid; ?></label>
	            <div class="col-sm-10">
	          	  <input name="order_id" type="text" autofocus required id="order_id" form="reg" class="form-control"></div>
	          </div>
		<?php if(isset($regerror)&&$regerror=='orderid'){ ?><p style="color:red;"><?php echo $check_orderid; ?></p><?php } ?>
	        <div class="form-group">
	            <div class="col-sm-12">
	          	  <button type="submit" form="reg" data-toggle="tooltip" title="<?php echo $license_registration; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button><input name="extension_id" type="hidden" id="extension_id" form="reg" value="<?php echo $extension_id; ?>"></div>
	          </div>
    </form>
    <?php } ?>
    <?php if($licensed=='curl'){ ?>
    <?php echo $server_error_curl; ?>
    <?php } ?>     
      </div>
    </div>
  </div>	
  <script type="text/javascript" src="view/javascript/summernote/summernote.js"></script>
  <link href="view/javascript/summernote/summernote.css" rel="stylesheet" />
  <script type="text/javascript" src="view/javascript/summernote/opencart.js"></script>

<script type="text/javascript"><!--
//save and stay
  $(document).on('click', '#save_and_stay', function(){
	$.ajax( {
	type: 'post',
	url: $('#form-shipping').attr('action') + '&save',
	data: $('#form-shipping').serialize(),
	beforeSend: function() {
		$('#form-shipping').fadeTo('slow', 0.5);
	},
	complete: function() {
		$('#form-shipping').fadeTo('slow', 1);
	},
	success: function( response ) {
		console.log( response );
	}
  });
});

// Stores
	$('select[name=\'shipping_customsm_all\']').on('change', function() {
	$('.shipping-customsm-all').hide();

	$('#shipping-customsm-all-' + this.value.replace('_', '-')).show();
});

$('select[name=\'shipping_customsm_all\']').trigger('change');

// Customers
	$('select[name=\'shipping_customsm_to\']').on('change', function() {
	$('.shipping-customsm-to').hide();

	$('#shipping-customsm-to-' + this.value.replace('_', '-')).show();
});

$('select[name=\'shipping_customsm_to\']').trigger('change');

$('input[name=\'shipping_customsm_customers\']').autocomplete({
	'source': function(request, response) {
		$.ajax({
			url: 'index.php?route=customer/customer/autocomplete&user_token=<?php echo $user_token; ?>&filter_name=' +  encodeURIComponent(request),
			dataType: 'json',			
			success: function(json) {
				response($.map(json, function(item) {
					return {
						label: item['name'],
						value: item['customer_id']
					}
				}));
			}
		});
	},
	'select': function(item) {
		$('input[name=\'shipping_customsm_customers\']').val('');
		
		$('#shipping_customsm-customer' + item['value']).remove();
		
		$('#shipping_customsm-customer').append('<div id="shipping_customsm-customer' + item['value'] + '"><i class="fa fa-minus-circle"></i> ' + item['label'] + '<input type="hidden" name="shipping_customsm_customer[]" value="' + item['value'] + '" /></div>');	
	}	
});

$('#shipping_customsm-customer').delegate('.fa-minus-circle', 'click', function() {
	$(this).parent().remove();
});

$('#language a:first').tab('show');
//--></script></div>
<?php echo $footer; ?>