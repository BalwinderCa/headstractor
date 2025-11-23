<?php
/*****************************************************************************
 *
 * ---------------------------------------------------------------
 * Australia Post: Postage Assesment Calculator for OpenCart 3.0
 *    (c) 2019 WWWShop, unauthorized reproduction is prohibited
 * ---------------------------------------------------------------
 *
 * Developer: WWWShop (opencart@wwwshop.com.au)
 * Date: 2019-04-10
 * Website: http://wwwshop.com.au/
 *
 *****************************************************************************/
// Heading
$_['heading_title']                          = 'AusPost (Postage Assesment Calculator)';

// Button
$_['button_service_add']                     = 'Add Service';

// Text
$_['text_extension']                         = 'Extensions';
$_['tab_general']                            = 'General';
$_['tab_services']                           = 'Services';

// Text
$_['text_shipping']                          = 'Shipping';
$_['text_success']                           = 'Success: You have modified AusPost (PAC) shipping!';
$_['text_edit']                              = 'Edit AusPost (PAC) Shipping';
$_['text_select_service']                    = '-- Please Select a Service --';

// Entry
$_['entry_api_key']                          = 'API Key';
$_['entry_origin_postcode']                  = 'Origin Postcode';
$_['entry_service']                          = 'Service';
$_['entry_region']                           = 'Region';
$_['entry_options']                          = 'Options';
$_['entry_selected_services']                = 'Selected Services';
$_['entry_show_delivery_time']               = 'Display Delivery Time';
$_['entry_multiple_packages']                = 'Multiple Packages';
$_['entry_handling_fee']                     = 'Handling Fee';
$_['entry_min_weight']                       = 'Minimum Weight';
$_['entry_max_weight']                       = 'Maximum Weight';
$_['entry_tax_class']                        = 'Tax Class';
$_['entry_geo_zone']                         = 'Geo Zone';
$_['entry_status']                           = 'Status';
$_['entry_sort_order']                       = 'Sort Order';
$_['entry_remove_gst_from_price']            = 'Remove GST from AusPost Pricing';
$_['entry_one_item_per_parcel']              = 'One Product Per Parcel';

// Help
$_['help_origin_postcode']                   = 'The location you\'re shipping from';
$_['help_show_delivery_time']                = 'Show/Hide the display of delivery times for Domestic/International Services';
$_['help_multiple_packages']                 = 'Enables splitting large orders over multiple packages';
$_['help_handling_fee']                      = 'Additional handling charge.<br />(Leave blank to disable)';
$_['help_min_weight']                        = 'Up to 3 decimal places.<br />(Leave blank to disable)';
$_['help_max_weight']                        = 'Up to 3 decimal places.<br />(Leave blank to disable)';
$_['help_remove_gst_from_price']             = 'Remove the GST component from pricing returned for domestic AusPost<br><br><em>Useful when Shipping is sorted before the Tax order total, and used in conjunction with the Tax Class above to re-calculate and correctly display the total GST included.</em>';

// Service
$_['text_service_aus_parcel_regular']                = 'Regular Parcel';
$_['text_service_aus_parcel_express']                = 'Express Post Parcel';
$_['text_service_aus_parcel_regular_satchel_500g']   = 'Prepaid Parcel Post Small (500g) Satchel (Deprecated)';
$_['text_service_aus_parcel_regular_satchel_1kg']    = 'Prepaid Parcel Post Small-Medium (1Kg) Satchel (Deprecated)';
$_['text_service_aus_parcel_regular_satchel_3kg']    = 'Prepaid Parcel Post Medium (3Kg) Satchel (Deprecated)';
$_['text_service_aus_parcel_regular_satchel_5kg']    = 'Prepaid Parcel Post Large (5Kg) Satchel (Deprecated)';
$_['text_service_aus_parcel_express_satchel_500g']   = 'Express Post Small (500g) Satchel (Deprecated)';
$_['text_service_aus_parcel_express_satchel_1kg']    = 'Express Post Small-Medium (1Kg) Satchel (Deprecated)';
$_['text_service_aus_parcel_express_satchel_3kg']    = 'Express Post Medium (3Kg) Satchel (Deprecated)';
$_['text_service_aus_parcel_express_satchel_5kg']    = 'Express Post Large (5Kg) Satchel (Deprecated)';
$_['text_service_aus_parcel_regular_satchel_small']       = 'Regular Post Small Satchel';
$_['text_service_aus_parcel_regular_satchel_medium']      = 'Regular Post Medium Satchel';
$_['text_service_aus_parcel_regular_satchel_large']       = 'Regular Post large Satchel';
$_['text_service_aus_parcel_regular_satchel_extra_large'] = 'Regular Post Extra Large Satchel';
$_['text_service_aus_parcel_express_satchel_small']       = 'Express Post Small Satchel';
$_['text_service_aus_parcel_express_satchel_medium']      = 'Express Post Medium Satchel';
$_['text_service_aus_parcel_express_satchel_large']       = 'Express Post Large Satchel';
$_['text_service_aus_parcel_express_satchel_extra_large'] = 'Express Post Extra Large Satchel';
$_['text_service_aus_parcel_courier']                = 'Courier Post';
$_['text_service_aus_parcel_courier_satchel_medium'] = 'Courier Post Assessed Medium Satchel';
$_['text_service_int_parcel_air_own_packaging']      = 'Economy Air';
$_['text_service_int_parcel_sea_own_packaging']      = 'Economy Sea';
$_['text_service_int_parcel_std_own_packaging']      = 'Standard';
$_['text_service_int_parcel_exp_own_packaging']      = 'Express';
$_['text_service_int_parcel_cor_own_packaging']      = 'Courier';

// Options
$_['text_option_aus_service_option_standard']                     = 'Standard Service';
$_['text_option_aus_service_option_signature_on_delivery']        = 'Signature on Delivery';
$_['text_option_aus_service_option_cod_postage_fees']             = 'C.O.D - Postage & Fees';
$_['text_option_aus_service_option_cod_money_collection']         = 'C.O.D - Money Collection, Postage & Fees';
$_['text_option_aus_service_option_track_over_500g']              = 'Tracking (when lodged in a Retail outlet for parcels over 500gms)';
$_['text_option_aus_service_option_courier_extra_cover_service']  = 'Standard Cover';
$_['text_option_int_extra_cover']                                 = 'Extra Cover';
$_['text_option_int_tracking']                                    = 'Tracking';
$_['text_option_int_sms_track_advice']                            = 'SMS Tracking Advice';
$_['text_option_int_signature_on_delivery']                       = 'Signature on Delivery';

// Sub-options
$_['text_suboption_aus_service_option_delivery_confirmation'] = 'Delivery Confirmation';
$_['text_suboption_aus_service_option_person_to_person']      = 'Person to Person';
$_['text_suboption_aus_service_option_extra_cover']           = 'Extra Cover';

// Misc
$_['text_misc_extra_cover_up_to_5000'] = 'up to $5,000';

// Error
$_['error_permission']                       = 'Warning: You do not have permission to modify AusPost (PAC) shipping!';
$_['error_api_key']                          = 'API Key Required!';
$_['error_origin_postcode']                  = 'Origin Postcode Required!';
$_['error_currency']                         = 'Currency with the code <strong>AUD</strong> has not been configured. This must be setup prior to Enabling this extension. To add the currency please goto: System > Localisation > Currencies';