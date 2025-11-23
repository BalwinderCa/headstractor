<meta name="referrer" content="no-referrer-when-downgrade">
<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
      <?php if($licensed_md5 == 'd9a22d7a8178d5b42a8750123cbfe5b1'){ ?>
        <button id="save_and_stay" data-toggle="tooltip" title="<?php echo $button_stay; ?>" class="btn btn-success"><i class="fa fa-save"></i></button>
        <button type="submit" form="form-payment" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <?php } ?>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title_normal_pm; ?></h1>
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
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit_pm; ?></h3>
      </div>
      <div class="panel-body">
      <?php if($licensed_md5 == 'd9a22d7a8178d5b42a8750123cbfe5b1'){ ?>      
      <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-payment" class="form-horizontal">
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
            <label class="col-sm-2 control-label" for="input-name<?php echo $language['language_id']; ?>"><span data-toggle="tooltip" title="<?php echo $help_name; ?>"><?php echo $entry_name; ?></span></label>
            <div class="col-sm-10">             
                <input type="text" name="payment_custompm_name<?php echo $language['language_id']; ?>" value="<?php echo ${'payment_custompm_name' . $language['language_id']}; ?>" class="form-control" />                
                <?php if (${'error_name' . $language['language_id']}) { ?>
                <div class="text-danger"><?php echo ${'error_name' . $language['language_id']}; ?></div>
                <?php } ?>                
                </div>
            </div>                             
          <div class="form-group">                    
            <label class="col-sm-2 control-label" for="input-description"><span data-toggle="tooltip" title="<?php echo $help_description; ?>"><?php echo $entry_description; ?></span></label>
            <div class="col-sm-10">                        
            <textarea name="payment_custompm_description<?php echo $language['language_id']; ?>" id="input-description<?php echo $language['language_id']; ?>" data-toggle="summernote" data-lang="{{ summernote }}" class="form-control" title="<?php echo $entry_description; ?>"><?php echo  ${'payment_custompm_description' . $language['language_id']}; ?></textarea>    
                </div>
               </div>
              </div>  
              <?php } ?>          
             </div>
            </div>
           <div class="tab-pane" id="tab-settings">        
            <div class="form-group">
            <label class="col-sm-2 control-label" for="input-shipping"><span data-toggle="tooltip" title="<?php echo $help_shipping; ?>"><?php echo $entry_shipping; ?></span></label>
            <div class="col-sm-10">
            <div class="well well-sm" style="height: 150px; overflow: auto;">
            <?php foreach ($shipping_methods as $shipping_method) { ?>
            <div class="checkbox">
            <label>
            <?php if (!empty($payment_custompm_shipping) && in_array($shipping_method['code'],$payment_custompm_shipping)) { ?>
            <input type="checkbox" name="payment_custompm_shipping[]" value="<?php echo $shipping_method['code']; ?>" checked="checked" />
            <?php echo $shipping_method['title']; ?>
            <?php } else { ?>
            <input type="checkbox" name="payment_custompm_shipping[]" value="<?php echo $shipping_method['code']; ?>" />
            <?php echo $shipping_method['title']; ?>
                    <?php } ?>
                  </label>
                  </div>
                  <?php } ?>
                  </div>                    
                </div>
              </div>              
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-order-status"><?php echo $entry_order_status; ?></label>
            <div class="col-sm-10">
              <select name="payment_custompm_order_status_id" id="input-order-status" class="form-control">
                <?php foreach ($order_statuses as $order_status) { ?>
                <?php if ($order_status['order_status_id'] == $payment_custompm_order_status_id) { ?>
                <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                <?php } else { ?>
                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                <?php } ?>
                <?php } ?>
              </select>
            </div>
          </div>         
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-status"><?php echo $entry_status; ?></label>
            <div class="col-sm-10">
              <select name="payment_custompm_status" id="input-status" class="form-control">
                <?php if ($payment_custompm_status) { ?>
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
              <input type="text" name="payment_custompm_sort_order" value="<?php echo $payment_custompm_sort_order; ?>" placeholder="<?php echo $entry_sort_order; ?>" id="input-sort-order" class="form-control" />
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
  <script type="text/javascript"><!--
//save and stay
  $(document).on('click', '#save_and_stay', function(){
	$.ajax( {
	type: 'post',
	url: $('#form-payment').attr('action') + '&save',
	data: $('#form-payment').serialize(),
	beforeSend: function() {
		$('#form-payment').fadeTo('slow', 0.5);
	},
	complete: function() {
		$('#form-payment').fadeTo('slow', 1);
	},
	success: function( response ) {
		console.log( response );
	}
  });
});
//--></script>
  <script type="text/javascript" src="view/javascript/summernote/summernote.js"></script>
  <link href="view/javascript/summernote/summernote.css" rel="stylesheet" />
  <script type="text/javascript" src="view/javascript/summernote/opencart.js"></script>
<script type="text/javascript"><!--
$('#language a:first').tab('show');
//--></script></div>
<?php echo $footer; ?>