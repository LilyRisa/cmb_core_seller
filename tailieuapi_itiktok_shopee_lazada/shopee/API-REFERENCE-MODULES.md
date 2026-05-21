# Shopee Open Platform — API Reference Module Index

> Source: https://open.shopee.com/documents/v2/
> Scraped: 2026-05-20T20:45:49.596Z

---

The Shopee Open Platform API Reference (v2) provides hundreds of API methods organized into modules.
Full documentation is available at: https://open.shopee.com/documents/v2/

Total modules found: 28
Total API methods visible: 406

## Modules

### AMS

- get_open_campaign_added_product
- get_open_campaign_not_added_product
- batch_add_products_to_open_campaign
- add_all_products_to_open_campaign
- get_auto_add_new_product_toggle_status
- update_auto_add_new_product_setting
- batch_edit_products_open_campaign_setting
- edit_all_products_open_campaign_setting
- batch_remove_products_open_campaign_setting
- remove_all_products_open_campaign_setting
- get_open_campaign_batch_task_result
- get_optimization_suggestion_product
- batch_get_products_suggested_rate
- get_shop_suggested_rate
- get_targeted_campaign_addable_product_list
- get_recommended_affiliate_list
- get_managed_affiliate_list
- query_affiliate_list
- create_new_targeted_campaign
- get_targeted_campaign_list
- get_targeted_campaign_settings
- update_basic_info_of_targeted_campaign
- edit_product_list_of_targeted_campaign
- edit_affiliate_list_of_targeted_campaign
- terminate_targeted_campaign
- get_performance_data_update_time
- get_shop_performance
- get_product_performance
- get_affiliate_performance
- get_content_performance
- get_campaign_key_metrics_performance
- get_open_campaign_performance
- get_targeted_campaign_performance
- get_conversion_report
- get_validation_list
- get_validation_report

### Video

- get_cover_list
- edit_video_info
- post_video
- get_video_list
- get_video_detail
- delete_video
- get_overview_performance
- get_metric_trend
- get_user_demographics
- get_video_performance_list
- get_prodcut_performance_list
- get_video_detail_performance
- get_video_detail_metric_trend
- get_video_detail_audience_distribution
- get_video_detail_product_performance

### Product

- get_category
- get_attribute_tree
- get_brand_list
- get_item_limit
- get_item_list
- get_item_base_info
- get_item_extra_info
- add_item
- update_item
- delete_item
- init_tier_variation
- update_tier_variation
- get_model_list
- add_model
- update_model
- delete_model
- unlist_item
- update_price
- update_stock
- boost_item
- get_boosted_list
- get_item_promotion
- update_sip_item_price
- search_item
- get_comment
- reply_comment
- category_recommend
- register_brand
- get_recommend_attribute
- get_weight_recommendation
- get_size_chart_list
- get_size_chart_detail
- get_item_violation_info
- get_variations
- get_all_vehicle_list
- get_vehicle_list_by_compatibility_detail
- get_item_content_diagnosis_result
- get_item_list_by_content_diagnosis
- get_kit_item_limit
- add_kit_item
- update_kit_item
- get_kit_item_info
- get_ssp_list
- get_ssp_info
- add_ssp_item
- link_ssp
- unlink_ssp
- get_aitem_by_pitem_id
- search_attribute_value_list
- get_main_item_list
- get_direct_item_list
- get_direct_shop_recommended_price
- get_product_certification_rule
- v2.product.publish_item_to_outlet_shop
- get_mart_item_mapping_by_id
- search_unpackaged_model_list
- generate_kit_image
- get_mart_item_by_outlet_item_id

### GlobalProduct

- get_global_item_limit
- get_global_item_list
- get_global_item_info
- add_global_item
- update_global_item
- delete_global_item
- add_global_model
- update_global_model
- delete_global_model
- get_global_model_list
- support_size_chart
- update_size_chart
- create_publish_task
- get_publishable_shop
- get_publish_task_result
- get_published_list
- set_sync_field
- get_global_item_id
- get_shop_publishable_status
- search_global_attribute_value_list
- get_local_adjustment_rate
- update_local_adjustment_rate

### MediaSpace

- init_video_upload
- upload_video_part
- complete_video_upload
- get_video_upload_result
- cancel_video_upload
- upload_image

### Media


### Shop

- get_shop_info
- get_profile
- update_profile
- get_warehouse_detail
- get_shop_notification
- get_authorised_reseller_brand
- get_br_shop_onboarding_info
- get_shop_holiday_mode
- set_shop_holiday_mode

### Merchant

- get_merchant_info
- get_shop_list_by_merchant
- get_merchant_warehouse_location_list
- get_merchant_warehouse_list
- get_warehouse_eligible_shop_list
- get_merchant_prepaid_account_list

### Order

- get_order_list
- get_order_detail
- get_shipment_list
- search_package_list
- get_package_detail
- split_order
- unsplit_order
- cancel_order
- handle_buyer_cancellation
- set_note
- get_pending_buyer_invoice_order_list
- get_buyer_invoice_info
- upload_invoice_doc
- download_invoice_doc
- handle_prescription_check
- get_warehouse_filter_config
- get_booking_list
- get_booking_detail
- generate_fbs_invoices
- get_fbs_invoices_result
- download_fbs_invoices

### Logistics

- get_shipping_parameter
- get_mass_shipping_parameter
- ship_order
- mass_ship_order
- update_shipping_order
- get_tracking_number
- get_mass_tracking_number
- get_shipping_document_parameter
- create_shipping_document
- get_shipping_document_result
- download_shipping_document
- get_shipping_document_data_info
- get_tracking_info
- get_address_list
- set_address_config
- update_address
- delete_address
- get_channel_list
- update_channel
- get_operating_hours
- get_operating_hour_restrictions
- update_operating_hours
- delete_special_operating_hour
- batch_update_tpf_warehouse_tracking_status
- batch_ship_order
- update_tracking_status
- get_booking_shipping_parameter
- ship_booking
- get_booking_tracking_number
- get_booking_shipping_document_parameter
- create_booking_shipping_document
- get_booking_shipping_document_result
- download_booking_shipping_document
- get_booking_shipping_document_data_info
- get_booking_tracking_info
- download_to_label
- create_shipping_document_job
- get_shipping_document_job_status
- download_shipping_document_job
- update_self_collection_order_logistics
- get_mart_packaging_info
- set_mart_packaging_info
- upload_serviceable_polygon
- check_polygon_update_status
- get_pause_status
- set_pause_status

### FirstMile

- get_unbind_order_list
- get_detail
- generate_first_mile_tracking_number
- bind_first_mile_tracking_number
- unbind_first_mile_tracking_number
- get_tracking_number_list
- get_waybill
- get_courier_delivery_channel_list
- get_transit_warehouse_list
- generate_and_bind_first_mile_tracking_number
- bind_courier_delivery_first_mile_tracking_number
- unbind_first_mile_tracking_number_all
- get_courier_delivery_detail
- get_courier_delivery_waybill
- get_courier_delivery_tracking_number_list

### Payment

- get_escrow_detail
- set_shop_installment_status
- get_shop_installment_status
- get_payout_detail
- set_item_installment_status
- get_item_installment_status
- get_payment_method_list
- get_wallet_transaction_list
- get_escrow_list
- get_payout_info
- get_billing_transaction_info
- get_escrow_detail_batch
- generate_income_statement
- get_income_statement
- generate_income_report
- get_income_report
- get_income_overview
- get_income_detail

### Discount

- add_discount
- add_discount_item
- delete_discount
- delete_discount_item
- get_discount
- get_discount_list
- update_discount
- update_discount_item
- end_discount
- get_sip_discounts
- set_sip_discount
- delete_sip_discount

### Bundle Deal

- add_bundle_deal
- add_bundle_deal_item
- get_bundle_deal_list
- get_bundle_deal
- get_bundle_deal_item
- update_bundle_deal
- update_bundle_deal_item
- end_bundle_deal
- delete_bundle_deal
- delete_bundle_deal_item

### Add-On Deal

- add_add_on_deal
- add_add_on_deal_main_item
- add_add_on_deal_sub_item
- delete_add_on_deal
- delete_add_on_deal_main_item
- delete_add_on_deal_sub_item
- get_add_on_deal_list
- get_add_on_deal
- get_add_on_deal_main_item
- get_add_on_deal_sub_item
- update_add_on_deal
- update_add_on_deal_main_item
- update_add_on_deal_sub_item
- end_add_on_deal

### Voucher

- add_voucher
- delete_voucher
- end_voucher
- update_voucher
- get_voucher
- get_voucher_list

### ShopFlashSale

- get_time_slot_id
- create_shop_flash_sale
- get_item_criteria
- add_shop_flash_sale_items
- get_shop_flash_sale_list
- get_shop_flash_sale
- get_shop_flash_sale_items
- update_shop_flash_sale
- update_shop_flash_sale_items
- delete_shop_flash_sale
- delete_shop_flash_sale_items

### Follow Prize

- add_follow_prize
- delete_follow_prize
- end_follow_prize
- update_follow_prize
- get_follow_prize_detail
- get_follow_prize_list

### TopPicks

- get_top_picks_list
- add_top_picks
- update_top_picks
- delete_top_picks

### ShopCategory

- add_shop_category
- get_shop_category_list
- delete_shop_category
- update_shop_category
- add_item_list
- delete_item_list

### Returns

- get_return_list
- get_return_detail
- confirm
- dispute
- get_available_solutions
- offer
- accept_offer
- convert_image
- upload_proof
- query_proof
- get_return_dispute_reason
- cancel_dispute
- get_shipping_carrier
- upload_shipping_proof
- get_reverse_tracking_info

### AccountHealth

- get_metric_source_detail
- get_penalty_point_history
- get_punishment_history
- get_listings_with_issues
- get_late_orders

### Ads

- get_total_balance
- get_shop_toggle_info
- get_recommended_keyword_list
- get_recommended_item_list
- get_all_cpc_ads_hourly_performance
- get_all_cpc_ads_daily_performance
- (coming offline soon) v2.ads.create_auto_product_ads
- (coming offline soon) v2.ads.edit_auto_product_ads
- get_product_campaign_daily_performance
- get_product_campaign_hourly_performance
- get_product_level_campaign_id_list
- get_product_level_campaign_setting_info
- create_manual_product_ads
- edit_manual_product_ad_keywords
- edit_manual_product_ads
- get_create_product_ad_budget_suggestion
- get_product_recommended_roi_target
- get_ads_fácil_shop_rate
- check_create_gms_product_campaign_eligibility
- create_gms_product_campaign
- edit_gms_product_campaign
- list_gms_user_deleted_item
- edit_gms_item_product_campaign
- get_gms_campaign_performance
- get_gms_item_performance

### Public

- get_shops_by_partner
- get_merchants_by_partner
- get_access_token
- refresh_access_token
- get_token_by_resend_code
- get_shopee_ip_ranges

### Push

- set_app_push_config
- get_app_push_config
- get_lost_push_message
- confirm_consumed_lost_push_message

### SBS

- get_bound_whs_info
- get_current_inventory
- get_expiry_report
- get_stock_aging
- get_stock_movement

### FBS

- query_br_shop_enrollment_status
- query_br_shop_invoice_error
- query_br_shop_block_status
- query_br_sku_block_status

### Livestream

- create_session
- update_session
- start_session
- end_session
- get_session_detail
- update_item_list
- get_item_count
- update_show_item
- delete_show_item
- get_show_item
- get_like_item_list
- get_recent_item_list
- get_item_set_list
- get_item_set_item_list
- apply_item_set
- get_session_metric
- get_session_item_metric
- get_latest_comment_list
- post_comment
- ban_user_comment
- unban_user_comment

## Table of Contents

What’s new

![](https://deo.shopeemobile.com/shopee/shopee-openplatform-live-sg/staticssr/static/img/ai-new-guide.8139d20.png)

Meet Your New AI Assistant

Need a quick answer? Let AI spark the solution for you!

Got it

---

_Note: This is a module index only. Individual API method pages are not fully crawled due to the large volume of methods (hundreds across dozens of modules)._