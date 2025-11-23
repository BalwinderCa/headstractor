<?php
$_['heading_title']               = 'CustomPages';

$_['text_modules']                = 'Modules';
$_['text_settings']               = 'Settings';
$_['text_pages']                  = 'Pages';
$_['text_support']                = 'Support';
$_['text_information']            = 'Information';

//=== Tab Settings
$_['entry_global_status']         = 'Global Status';
$_['text_info_setting']           = array(
  '<b>Global Status</b>: Affecting all custom pages.',
);

//=== Tab Pages
$_['text_add']                    = 'Add';
$_['text_edit']                   = 'Edit';
$_['text_id']                     = 'ID';
$_['text_title']                  = 'Title';
$_['text_layouts']                = 'Layouts';
$_['text_status']                 = 'Status';
$_['text_action']                 = 'Action';
$_['text_no_record']              = 'No records found!';
$_['text_confirm_delete']         = 'Are you sure you want to delete custom-page setting?';

$_['text_info_custompage_list']   = array(
  'Each custom-page setting, extend a Layout (<code>Design > Layout</code>) route that produce 404 not found.',
  'Delete the custom-page setting will not delete the Layout.',
  'Additional How-to guide:
  <br>- <a href="https://www.youtube.com/watch?v=-kimLSsCU00" target="_blank">How to create a Featured Products page?</a>
  <br>- <a href="https://www.youtube.com/watch?v=4lxdN2u5DI8" target="_blank">How to create an FAQ page with CustomPages? </a>
  <br>- <a href="https://www.youtube.com/watch?v=oZ7PKE9XyOE" target="_blank">How to create a YouTube video tutorial page?</a>'
);

//=== Tab Form
$_['text_select']                 = '-- Select --';
$_['text_layout_id']              = 'Layout ID';
$_['text_store_id']               = 'Store ID';
$_['text_route']                  = 'Route';

$_['entry_layout_name']           = 'Layout Name';
$_['entry_status']                = 'Status';
$_['entry_page_header']           = 'Page Title';
$_['entry_seo_options']           = 'SEO Options';
$_['entry_meta_title']            = 'Meta Title';
$_['entry_meta_desc']             = 'Meta Description';
$_['entry_meta_keywords']         = 'Meta Keywords';
$_['entry_custom_code']           = 'Custom Code';

$_['text_info_form_layout']       = array(
  'Attach <code>Design > Layout</code> and extend it with custom page setting.',
);
$_['text_info_form_setting']      = array(
  'This setting will extend the Layout feature.',
  'If status disabled, the route will show the default OpenCart behaviour, show 404 not found.',
);
$_['text_info_form_custom_code']      = array(
  'Example change h1 color and show alert.<br>
<pre><code>&lt;style>
h1 { color: #a00; }
&lt;/style>
&lt;script>
alert(\'test\');
&lt;/script></code></pre>',
  'The module also use custom template according to the route if available.<br>
  Ex. for <code>route=custom/landing</code> the module automatically use <code>catalog/view/theme/default/<br>template/extension/module/<br>custompages/<b>custom_landing</b>.twig</code>',
);

//=== Tab Support

$_['text_your_license']           = 'Your license';
$_['text_please_enter_the_code']  = 'Please enter your product purchase license code';
$_['text_activate_license']       = 'Activate License';
$_['text_not_having_a_license']   = "Don't have a code? Get it from here.";
$_['text_license_holder']         = 'License Holder';
$_['text_registered_domains']     = 'Registered domains';
$_['text_expires_on']             = 'License Expires on';
$_['text_valid_license']          = 'VALID LICENSE';
$_['text_get_support']            = 'Get Support';
$_['text_community']              = 'Community';
$_['text_ask_our_community']      = 'Ask the community about your issue on the iSenseLabs forum.';
$_['text_tickets']                = 'Tickets';
$_['text_open_a_ticket']          = 'Want to communicate one-to-one with our tech people? Then open a support ticket.';
$_['text_pre_sale']               = 'Pre-sale';
$_['text_pre_sale_desc']          = 'Have a brilliant idea for your webstore? Our team of top-notch developers can make it real.';
$_['text_browse_forums']          = 'Browse forums';
$_['text_open_ticket_for_real']   = 'Open a ticket';
$_['text_bump_the_sales']         = 'Bump the sales';

//=== Notification
$_['text_loading']                = 'Loading..';
$_['text_processing']             = 'Processing..';
$_['text_success']                = 'Success: You have modified module CustomPages!';
$_['text_success_save']           = 'Successfully saved!';

$_['error_general']               = 'Error occured, please try again later!';
$_['error_permission']            = 'Warning: You do not have permission to modify module CustomPages!';
$_['error_form']                  = 'Error found, please check all required form!';
$_['error_title']                 = 'Title must be between 3-225 characters.';
