---
title: "(To be deprecated) Create Legacy Smart+ Lead Generation Campaigns"
doc_id: 1827645480080385
path: "Use Cases / Campaign creation / (To be deprecated) Create a Legacy Smart+ Campaign / (To be deprecated) Create Legacy Smart+ Lead Generation Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To be deprecated) Create Legacy Smart+ Lead Generation Campaigns

This article walks you through the steps to create Smart+ Lead Generation Campaigns.

Based on your preference for the optimization location, you can create a Smart+ Lead Generation Campaign with optimization location as Instant Form or Website. For detailed instructions on creating each type, refer to their respective sections: "<a href="#item-link-Create a Smart+ Lead Generation Campaign with optimization location as Instant Form">Create a Smart+ Lead Generation Campaign with optimization location as Instant Form</a>" and "<a href="#item-link-Create a Smart+ Lead Generation Campaign with optimization location as Website">Create a Smart+ Lead Generation Campaign with optimization location as Website</a>".

# Create a Smart+ Lead Generation Campaign with optimization location as Instant Form
## Prerequisites
Ensure that you have an existing Instant Form within your ad account.
- To create an Instant Form, use [Instant Page Editor SDK](https://business-api.tiktok.com/portal/docs?id=1740307202170881) and set `businessType` to `1`. Then call [/page/get/](https://business-api.tiktok.com/portal/docs?id=1820826387779586) to get the page ID of the Instant Form through `page_id`.

## Steps

Note that the following requirements must be met. To find a complete list of parameters, see [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362).

> <span style="color:DodgerBlue">**Note**</span><br>
>  Smart+ Lead Generation ad groups cannot be used in [/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201) to create split test groups.

<table>
  <thead>
    <tr>
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Advertising objective</td>
      <td>Lead generation</td>
      <td><code>objective_type</code></td>
      <td><code>LEAD_GENERATION</code></td>
    </tr>
    <tr>
      <td>Campaign setup</td>
      <td>Smart+ Campaign</td>
      <td>N/A</td>
      <td>N/A <br>Campaigns created through <a href="https://business-api.tiktok.com/portal/docs?id=1767334204047362" target="_blank">/campaign/spc/create/</a> are all Smart+ Campaigns.</td>
    </tr>
    <tr>
      <td>Campaign name</td>
      <td>Specify a valid name</td>
      <td><code>campaign_name</code></td>
      <td>Pass a valid value</td>
    </tr>
    <tr>
      <td>Special ad categories <br> (Optional)</td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: This setting is only supported for advertisers who are registered in the US or Canada.</td>
      <td><code>special_industries</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Campaign Budget Optimization (CBO)</td>
      <td>N/A<br><br><p><span style="color:darkred"><b>Note</b></span>: CBO is enabled by default for Smart+ Campaigns.</td>
      <td>N/A</td>
      <td>N/A<br><br><p><span style="color:darkred"><b>Note</b></span>: If you retrieve Smart+ Campaigns via <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986" target="_blank">/campaign/get/</a>, the returned <code>budget_optimize_on</code> will be <code>true</code>.</td>
    </tr>
    <tr>
      <td  rowspan="2">Optimization location (Promotion type)</td>
      <td  rowspan="2">Instant Form</td>
      <td><code>promotion_type</code></td>
      <td><code>LEAD_GENERATION</code></td>
    </tr>
    <tr>
      <td><code>promotion_target_type</code></td>
      <td><code>INSTANT_PAGE</code></td>
    </tr>
	<tr>
    <td> Deep funnel optimization<br>(Optional)</td>
    <td>Enabled or disabled<br><br>With deep funnel optimization, you can select a secondary event alongside the primary optimization event, which can help improve campaign effectiveness.</td>
    <td><code>deep_funnel_optimization_status</code><br><code>deep_funnel_event_source</code><br><code>deep_funnel_event_source_id</code><br><code>deep_funnel_optimization_event</code></td>
    <td><ul><li>To enable deep funnel optimization, set <code>deep_funnel_optimization_status</code> to <code>ON</code> and pass <code>deep_funnel_event_source</code>, <code>deep_funnel_event_source_id</code>, and <code>deep_funnel_optimization_event</code>.</li><li>To disable deep funnel optimization, set <code>deep_funnel_optimization_status</code> to <code>OFF</code> or leave <code>deep_funnel_optimization_status</code> unspecified. Do not pass <code>deep_funnel_event_source</code>, <code>deep_funnel_event_source_id</code>, and <code>deep_funnel_optimization_event</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Deep funnel optimization with CRM events is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Deep funnel optimization with Pixel or Offline events is generally available.</li></ul></td>
  </tr>
    <tr>
      <td>Optimization goal</td>
      <td>Form submission</td>
      <td><code>optimization_goal</code></td>
      <td><code>LEAD_GENERATION</code></td>
    </tr>
    <tr>
      <td  rowspan="2">Placement</td>
      <td  rowspan="2">TikTok placement including search result</td>
      <td><code>placement_type</code></td>
      <td><code>PLACEMENT_TYPE_NORMAL</code></td>
    </tr>
    <tr>
      <td><code>placements</code></td>
      <td><code>["PLACEMENT_TIKTOK"]</code><br><br><p><span style="color:darkred"><b>Note</b></span>: The <a href="https://business-api.tiktok.com/portal/docs?id=1771747626094593" target="_blank">Automatic Search Placement</a> is enabled by default.</td>
    </tr>
    <tr>
      <td>User comment<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>comment_disabled</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Video download <br> (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>share_disabled</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Video sharing <br> (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>video_download_disabled</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Location</td>
      <td>Enabled</td>
      <td><code>location_ids</code></td>
      <td>Pass valid values</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Language (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>languages</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Gender (Optional)</td>
      <td>Supported with allowlist<br><br><p><span style="color:darkred"><b>Note</b></span>: Gender targeting in Smart+ Campaigns is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
      <td><code>gender</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Age</td>
      <td>Any of the following options:<ul><li>18+</li><li>25+</li></ul></td>
      <td><code>spc_audience_age</code></td>
      <td>Any of the following values:<ul><li><code>18+</code></li><li><code>25+</code></li></ul></td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Exclude audience<br> (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>excluded_audience_ids</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Budget mode</td>
      <td>Dynamic daily budget</td>
      <td><code>budget_mode</code></td>
      <td><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></td>
    </tr>
    <tr>
      <td>Schedule type</td>
      <td>Any of the following types:<ul><li>Run campaign continuously after the scheduled start time</li><li>Run campaign between the scheduled start time and end time</li></ul></td>
      <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
      <td><ul><li>To run the campaign continuously after the scheduled start time, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not pass <code>schedule_end_time</code>.</li><li>To run the campaign between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul></td>
    </tr>
    <tr>
      <td rowspan="2">Bid strategy</td>
      <td rowspan="2">Any of the following types:<ul><li>Maximum Delivery</li><li>Cost Cap (or Target CPA)</li></ul></td>
      <td><code>bid_type</code><br><code>conversion_bid_price</code></td>
      <td>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>.<ul><li> If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, pass <code>conversion_bid_price</code> at the same time.</li></ul></td>
    </tr>
	<tr>
      <td><code>bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
      <td>Not passed</td>
    </tr>
    <tr>
      <td>Billing event</td>
      <td>oCPM</td>
      <td><code>billing_event</code></td>
      <td><code>OCPM</code></td>
    </tr>
    <tr>
      <td  rowspan="3">Identity</td>
      <td  rowspan="3">Any of the following options:<ul><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
      <td><code>identity_type</code></td>
      <td>Any of the following values:<ul><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Pass values to <code>identity_type</code> and <code>identity_id</code> within the <code>media_info</code> object.</td>
    </tr>
    <tr>
      <td><code>identity_id</code></td>
      <td>Pass a valid value. <br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
    </tr>
    <tr>
      <td><code>identity_authorized_bc_id</code></td>
      <td>Pass a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
    </tr>
    <tr>
      <td rowspan="2">Destination</td>
      <td rowspan="2">Instant Form</td>
      <td><code>page_list</code></td>
      <td>Specify the page ID of an Instant Form.<br><ul><li>To obtain the page ID of an Instant Form, set <code>business_type</code> to <code>LEAD_GEN</code> in <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586" target="_blank">/page/get/</a>.</li><li>To create an Instant Form, use <a href="https://business-api.tiktok.com/portal/docs?id=1740307202170881" target="_blank">Instant Page Editor SDK</a> and set <code>businessType</code> to <code>1</code>.</li></ul></td>
    </tr>
	<tr>
      <td><code>landing_page_urls</code></td>
      <td>Not passed</td>
    </tr>
    <tr>
      <td>Ad creative</td>
      <td><ul><li>For Authorized User identity, or Spark Ads PULL with TikTok User or TikTok Account User in Business Center identity, specify one to 30 TikTok posts.</li></ul></td>
      <td><code>video_info</code><br><code>image_info</code><br><code>title_list</code><br><code>tiktok_item_id</code></td>
      <td><ul><li> If <code>identity_type</code> within the <code>media_info</code> object is <code>TT_USER</code> or <code>BC_AUTH_TT</code>, you can create the campaign through Spark Ads PULL:<ul><li>Spark Ads PULL: Specify one to 30 TikTok posts through <code>tiktok_item_id</code>. Do not pass <code>video_info</code>, <code>image_info</code>, and <code>title_list</code>.</li></ul>To learn more about Spark Ads, see <a href="https://business-api.tiktok.com/portal/docs?id=1739470744631298" target="_blank">Create Spark Ads</a>.</li><li>If <code>identity_type</code> within the <code>media_info</code> object is <code>AUTH_CODE</code>, specify one to 30 TikTok posts through <code>tiktok_item_id</code>. Do not pass <code>video_info</code>, <code>image_info</code>, and <code>title_list</code>.</li></ul></td>
    </tr>
    <tr>
      <td>Interactive add-ons <br> (Optional)</td>
      <td>Disabled or enabled with the following type:<ul><li>Display Card</li></ul></td>
      <td><code>card_list</code></td>
      <td>Not passed or specify the ID of the following creative portfolio type:<ul><li>Display Card</li></ul><br>To create a creative portfolio, use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to get the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card" target="_blank">Cards</a>.</li></ul></td>
    </tr>
    <tr>
      <td  rowspan="2">Call to action</td>
      <td  rowspan="2">Dynamic</td>
      <td><code>call_to_action_id</code></td>
      <td>Specify a valid value.<br><br>To learn about how to create Dynamic CTAs, see <a href="https://business-api.tiktok.com/portal/docs?id=1740307296329730#item-link-Dynamic%20CTAs" target="_blank">Dynamic CTAs</a>.</td>
    </tr>
	<tr>
      <td><code>call_to_action_list</code></td>
      <td>Not passed</td>
    </tr>
  </tbody>
</table>


### Example


Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "LEAD_GENERATION",
    "campaign_name": "{{campaign_name}}",
    "promotion_type": "LEAD_GENERATION",
    "promotion_target_type": "INSTANT_PAGE",
    "optimization_goal": "LEAD_GENERATION",
    "spc_audience_age": "25+",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "6252001"
    ],
    "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "page_list": [
        {
            "page_id": "{{page_id}}"
        }
    ],
    "media_info_list": [
        {
            "media_info": {
                "identity_type": "AUTH_CODE",
                "identity_id": "{{identity_id}}",
                "tiktok_item_id": "{{tiktok_item_id}}"
            }
        }
    ],
    "call_to_action_id": "{{call_to_action_id}}"
}'
(/code)
```

Response

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "click_tracking_url": null,
        "carousel_image_index": null,
        "optimization_event": "FORM",
        "catalog_id": null,
        "bid_type": "BID_TYPE_CUSTOM",
        "comment_disabled": false,
        "app_id": null,
        "optimization_goal": "LEAD_GENERATION",
        "bid_price": null,
        "is_advanced_dedicated_campaign": false,
        "create_time": "{{create_time}}",
        "exclude_age_under_eighteen": true,
        "engaged_view_attribution_window": null,
        "blocked_pangle_app_ids": null,
        "deeplink_type": null,
        "scheduled_budget": 0,
        "conversion_bid_price": {{conversion_bid_price}},
        "advertiser_id": "{{advertiser_id}}",
        "impression_tracking_url": null,
        "deep_bid_type": null,
        "video_download_disabled": false,
        "catalog_creative_toggle": null,
        "promotion_type": "LEAD_GENERATION",
        "schedule_type": "SCHEDULE_START_END",
        "share_disabled": false,
        "schedule_start_time": "{{schedule_start_time}}",
        "campaign_app_profile_page_state": null,
        "promotion_target_type": "INSTANT_PAGE",
        "gender": "GENDER_UNLIMITED",
        "app_download_url": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "special_industries": null,
        "web_all_in_one_catalog_status": null,
        "billing_event": "OCPM",
        "call_to_action_list": [],
        "click_attribution_window": "SEVEN_DAYS",
        "product_source": null,
        "spc_audience_age": "25+",
        "identity_type": null,
        "location_ids": [
            "6252001"
        ],
        "campaign_secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "is_smart_performance_campaign": true,
        "campaign_type": null,
        "roas_bid": null,
        "operation_status": "ENABLE",
        "app_promotion_type": null,
        "bid_align_type": null,
        "campaign_name": "{{campaign_name}}",
        "attribution_event_count": "ONCE",
        "skip_learning_phase": true,
        "campaign_id": "{{campaign_id}}",
        "identity_id": null,
        "catalog_authorized_bc_id": null,
        "media_info_list": [
            {
                "media_info": {
                    "aigc_disclosure_type": null,
                    "identity_type": "AUTH_CODE",
                    "tiktok_item_id": "{{tiktok_item_id}}",
                    "image_info": null,
                    "identity_id": "{{identity_id}}",
                    "music_info": null,
                    "identity_authorized_bc_id": "0",
                    "video_info": null
                }
            }
        ],
        "modify_time": "{{modify_time}}",
        "spc_type": "UNSET",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "card_list": null,
        "vbo_window": null,
        "budget": {{budget}},
        "title_list": [
            {
                "title": ""
            }
        ],
        "automatic_add_on_enabled": null,
        "app_name": null,
        "disable_skan_campaign": null,
        "excluded_audience_ids": null,
        "product_specific_type": null,
        "objective_type": "LEAD_GENERATION",
        "promotion_website_type": null,
        "app_type": null,
        "languages": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "view_attribution_window": "ONE_DAY",
        "page_list": [
            {
                "page_id": "{{page_id}}"
            }
        ],
        "adgroup_secondary_status": "ADGROUP_STATUS_AUDIT",
        "call_to_action_id": "{{call_to_action_id}}",
        "landing_page_urls": null,
        "product_ids": null,
        "pixel_id": "{{pixel_id}}",
        "shopping_ads_fallback_type": null,
        "product_set_id": null,
        "carousel_image_labels": null,
        "deeplink": null
    }
}
(/code)
```


# Create a Smart+ Lead Generation Campaign with optimization location as Website

## Steps

Note that the following requirements must be met. To find a complete list of parameters, see [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362).

> <span style="color:DodgerBlue">**Note**</span><br>
>  Smart+ Lead Generation ad groups cannot be used in [/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201) to create split test groups.

<table>
  <thead>
    <tr>
      <th style="width:17%">Setting</th>
      <th style="width:26%">Requirement</th>
      <th style="width:27%">Parameter</th>
      <th style="width:30%">How to configure the parameter</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Advertising objective</td>
      <td>Lead generation</td>
      <td><code>objective_type</code></td>
      <td><code>LEAD_GENERATION</code></td>
    </tr>
    <tr>
      <td>Campaign setup</td>
      <td>Smart+ Campaign</td>
      <td>N/A</td>
      <td>N/A <br>Campaigns created through <a href="https://business-api.tiktok.com/portal/docs?id=1767334204047362" target="_blank">/campaign/spc/create/</a> are all Smart+ Campaigns.</td>
    </tr>
    <tr>
      <td>Campaign name</td>
      <td>Specify a valid name</td>
      <td><code>campaign_name</code></td>
      <td>Pass a valid value</td>
    </tr>
    <tr>
      <td>Special ad categories <br> (Optional)</td>
      <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: This setting is only supported for advertisers who are registered in the US or Canada.</td>
      <td><code>special_industries</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Campaign Budget Optimization (CBO)</td>
      <td>N/A<br><br><p><span style="color:darkred"><b>Note</b></span>: CBO is enabled by default for Smart+ Campaigns.</td>
      <td>N/A</td>
      <td>N/A<br><br><p><span style="color:darkred"><b>Note</b></span>: If you retrieve Smart+ Campaigns via <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986" target="_blank">/campaign/get/</a>, the returned <code>budget_optimize_on</code> will be <code>true</code>.</td>
    </tr>
    <tr>
      <td  rowspan="2">Optimization location (Promotion type)</td>
      <td  rowspan="2">Website</td>
      <td><code>promotion_type</code></td>
      <td><code>LEAD_GENERATION</code></td>
    </tr>
    <tr>
      <td><code>promotion_target_type</code></td>
      <td><code>EXTERNAL_WEBSITE</code></td>
    </tr>
	<tr>
    <td> Deep funnel optimization<br>(Optional)</td>
    <td>Enabled or disabled<br><br>With deep funnel optimization, you can select a secondary event alongside the primary optimization event, which can help improve campaign effectiveness.</td>
    <td><code>deep_funnel_optimization_status</code><br><code>deep_funnel_event_source</code><br><code>deep_funnel_event_source_id</code><br><code>deep_funnel_optimization_event</code></td>
    <td><ul><li>To enable deep funnel optimization, set <code>deep_funnel_optimization_status</code> to <code>ON</code> and pass <code>deep_funnel_event_source</code>, <code>deep_funnel_event_source_id</code>, and <code>deep_funnel_optimization_event</code>.</li><li>To disable deep funnel optimization, set <code>deep_funnel_optimization_status</code> to <code>OFF</code> or leave <code>deep_funnel_optimization_status</code> unspecified. Do not pass <code>deep_funnel_event_source</code>, <code>deep_funnel_event_source_id</code>, and <code>deep_funnel_optimization_event</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>:<ul><li>Deep funnel optimization with CRM events is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</li><li>Deep funnel optimization with Pixel or Offline events is generally available.</li></ul></td>
  </tr>
    <tr>
      <td>Optimization goal</td>
      <td>Conversion</td>
      <td><code>optimization_goal</code></td>
      <td><code>CONVERT</code></td>
    </tr>
	<tr>
      <td>Pixel</td>
      <td>Enabled</td>
      <td><code>pixel_id</code></td>
      <td>Specify a valid value.<br>To obtain the list of Pixels within your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.</td>
    </tr>
    <tr>
      <td>Optimization event</td>
      <td>Enabled</td>
      <td><code>optimization_event</code></td>
      <td>Specify a valid value.<br>To obtain the optimization events that have been configured for a Pixel, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>.<br>See <a href="https://business-api.tiktok.com/portal/docs?id=1739361474981889#item-link-Pixel%20events" target="_blank">Conversion events - Pixel events</a> for enum values.</td>
    </tr>
    <tr>
      <td  rowspan="2">Placement</td>
      <td  rowspan="2">TikTok placement including search result</td>
      <td><code>placement_type</code></td>
      <td><code>PLACEMENT_TYPE_NORMAL</code></td>
    </tr>
    <tr>
      <td><code>placements</code></td>
      <td><code>["PLACEMENT_TIKTOK"]</code><br><br><p><span style="color:darkred"><b>Note</b></span>: The <a href="https://business-api.tiktok.com/portal/docs?id=1771747626094593" target="_blank">Automatic Search Placement</a> is enabled by default.</td>
    </tr>
    <tr>
      <td>User comment<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>comment_disabled</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Video download <br> (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>share_disabled</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Video sharing <br> (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>video_download_disabled</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Location</td>
      <td>Enabled</td>
      <td><code>location_ids</code></td>
      <td>Pass valid values</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Language (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>languages</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Gender (Optional)</td>
      <td>Supported with allowlist<br><br><p><span style="color:darkred"><b>Note</b></span>: Gender targeting in Smart+ Campaigns is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
      <td><code>gender</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Age</td>
      <td>Any of the following options:<ul><li>18+</li><li>25+</li></ul></td>
      <td><code>spc_audience_age</code></td>
      <td>Any of the following values:<ul><li><code>18+</code></li><li><code>25+</code></li></ul></td>
    </tr>
    <tr>
      <td>Audience targeting<br>&#183; Exclude audience<br> (Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>excluded_audience_ids</code></td>
      <td>Pass valid values or not passed</td>
    </tr>
    <tr>
      <td>Budget mode</td>
      <td>Dynamic daily budget</td>
      <td><code>budget_mode</code></td>
      <td><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></td>
    </tr>
    <tr>
      <td>Schedule type</td>
      <td>Any of the following types:<ul><li>Run campaign continuously after the scheduled start time</li><li>Run campaign between the scheduled start time and end time</li></ul></td>
      <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
      <td><ul><li>To run the campaign continuously after the scheduled start time, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not pass <code>schedule_end_time</code>.</li><li>To run the campaign between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul></td>
    </tr>
    <tr>
      <td rowspan="2">Bid strategy</td>
      <td rowspan="2">Any of the following types:<ul><li>Maximum Delivery</li><li>Cost Cap (or Target CPA)</li></ul></td>
      <td><code>bid_type</code><br><code>conversion_bid_price</code></td>
      <td>Set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>.<ul><li> If <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, pass <code>conversion_bid_price</code> at the same time.</li></ul></td>
    </tr>
	<tr>
      <td><code>bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
      <td>Not passed</td>
    </tr>
    <tr>
      <td>Billing event</td>
      <td>oCPM</td>
      <td><code>billing_event</code></td>
      <td><code>OCPM</code></td>
    </tr>
    <tr>
      <td  rowspan="3">Identity</td>
      <td  rowspan="3">Any of the following options:<ul><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
      <td><code>identity_type</code></td>
      <td>Any of the following values:<ul><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Pass values to <code>identity_type</code> and <code>identity_id</code> within the <code>media_info</code> object.</td>
    </tr>
    <tr>
      <td><code>identity_id</code></td>
      <td>Pass a valid value. <br>To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
    </tr>
    <tr>
      <td><code>identity_authorized_bc_id</code></td>
      <td>Pass a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
    </tr>
	<tr>
      <td>Destination</td>
      <td>Any of the following options:<ul><li>URL</li><li>Custom Page</li></ul></td>
      <td><code>landing_page_urls</code><br><code>page_list</code></td>
      <td><ul><li>To specify Destination as URL, specify the landing page URL of the website that has the Website Form via <code>landing_page_urls</code>.</li><li>To specify Destination as Custom Page, specify the TikTok Instant Page that redirects to the website that has the Website Form via <code>page_list</code>.<ul><li> To create a TikTok Instant Page, you can use <a href="https://business-api.tiktok.com/portal/docs?id=1740307202170881" target="_blank">Instant Page Editor SDK</a> and set <code>businessType</code> to <code>6</code>.</li><li>After you create a TikTok Instant Page, call <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586" target="_blank">/page/get/</a> to get the page ID.</li></ul></li></ul></td>
    </tr>
    <tr>
	  <td>Ad creative</td>
      <td><ul><li>For Authorized User identity, or Spark Ads PULL with TikTok User or TikTok Account User in Business Center identity, specify one to 30 TikTok posts.</li></ul></td>
      <td><code>video_info</code><br><code>image_info</code><br><code>title_list</code><br><code>tiktok_item_id</code></td>
      <td><ul><li> If <code>identity_type</code> within the <code>media_info</code> object is <code>TT_USER</code> or <code>BC_AUTH_TT</code>, you can create the campaign through Spark Ads PULL:<ul><li>Spark Ads PULL: Specify one to 30 TikTok posts through <code>tiktok_item_id</code>. Do not pass <code>video_info</code>, <code>image_info</code>, and <code>title_list</code>.</li></ul>To learn more about Spark Ads, see <a href="https://business-api.tiktok.com/portal/docs?id=1739470744631298" target="_blank">Create Spark Ads</a>.</li><li>If <code>identity_type</code> within the <code>media_info</code> object is <code>AUTH_CODE</code>, specify one to 30 TikTok posts through <code>tiktok_item_id</code>. Do not pass <code>video_info</code>, <code>image_info</code>, and <code>title_list</code>.</li></ul></td>
	</tr> 
    <tr>
      <td>Interactive add-ons <br> (Optional)</td>
      <td>Disabled or enabled with the following type:<ul><li>Display Card</li></ul></td>
      <td><code>card_list</code></td>
      <td>Not passed or specify the ID of the following creative portfolio type:<ul><li>Display Card</li></ul><br>To create a creative portfolio, use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to get the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card" target="_blank">Cards</a>.</li></ul></td>
    </tr>
    <tr>
      <td  rowspan="2">Call to action</td>
      <td  rowspan="2">Dynamic</td>
      <td><code>call_to_action_id</code></td>
      <td>Specify a valid value.<br><br>To learn about how to create Dynamic CTAs, see <a href="https://business-api.tiktok.com/portal/docs?id=1740307296329730#item-link-Dynamic%20CTAs" target="_blank">Dynamic CTAs</a>.</td>
    </tr>
	<tr>
      <td><code>call_to_action_list</code></td>
      <td>Not passed</td>
    </tr>
  </tbody>
</table>


### Example
#### Destination as URL

Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "LEAD_GENERATION",
    "campaign_name": "{{campaign_name}}",
    "promotion_type": "LEAD_GENERATION",
    "promotion_target_type": "EXTERNAL_WEBSITE",
    "optimization_goal": "CONVERT",
    "pixel_id": "{{pixel_id}}",
    "optimization_event": "ON_WEB_DETAIL",
    "spc_audience_age": "25+",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "6252001"
    ],
    "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price":{{conversion_bid_price}},
    "billing_event": "OCPM",
    "landing_page_urls": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ],
    "media_info_list": [
        {
            "media_info": {
                "identity_type": "AUTH_CODE",
                "identity_id": "{{identity_id}}",
                "tiktok_item_id": "{{tiktok_item_id}}"
            }
        }
    ],
    "call_to_action_id": "{{call_to_action_id}}"
}'
(/code)
```

Response

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "click_tracking_url": null,
        "carousel_image_index": null,
        "optimization_event": "ON_WEB_DETAIL",
        "catalog_id": null,
        "bid_type": "BID_TYPE_CUSTOM",
        "comment_disabled": false,
        "app_id": null,
        "optimization_goal": "CONVERT",
        "bid_price": null,
        "is_advanced_dedicated_campaign": false,
        "create_time": "{{create_time}}",
        "exclude_age_under_eighteen": true,
        "engaged_view_attribution_window": "OFF",
        "blocked_pangle_app_ids": null,
        "deeplink_type": null,
        "scheduled_budget": 0,
        "conversion_bid_price": {{conversion_bid_price}},
        "advertiser_id": "75960823018",
        "impression_tracking_url": null,
        "deep_bid_type": null,
        "video_download_disabled": false,
        "catalog_creative_toggle": null,
        "promotion_type": "LEAD_GENERATION",
        "schedule_type": "SCHEDULE_START_END",
        "share_disabled": false,
        "schedule_start_time": "{{schedule_start_time}}",
        "campaign_app_profile_page_state": null,
        "promotion_target_type": "EXTERNAL_WEBSITE",
        "gender": "GENDER_UNLIMITED",
        "app_download_url": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "special_industries": null,
        "web_all_in_one_catalog_status": null,
        "billing_event": "OCPM",
        "call_to_action_list": [],
        "click_attribution_window": "SEVEN_DAYS",
        "product_source": null,
        "spc_audience_age": "25+",
        "identity_type": null,
        "location_ids": [
            "6252001"
        ],
        "campaign_secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "is_smart_performance_campaign": true,
        "campaign_type": null,
        "roas_bid": null,
        "operation_status": "ENABLE",
        "app_promotion_type": null,
        "bid_align_type": null,
        "campaign_name": "{{campaign_name}}",
        "attribution_event_count": "EVERY",
        "skip_learning_phase": true,
        "campaign_id": "{{campaign_id}}",
        "identity_id": null,
        "catalog_authorized_bc_id": null,
        "media_info_list": [
            {
                "media_info": {
                    "aigc_disclosure_type": null,
                    "identity_type": "AUTH_CODE",
                    "tiktok_item_id": "{{tiktok_item_id}}",
                    "image_info": null,
                    "identity_id": "{{identity_id}}",
                    "music_info": null,
                    "identity_authorized_bc_id": "0",
                    "video_info": null
                }
            }
        ],
        "modify_time": "{{modify_time}}",
        "spc_type": "UNSET",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "card_list": null,
        "vbo_window": null,
        "budget": {{budget}},
        "title_list": [
            {
                "title": ""
            }
        ],
        "automatic_add_on_enabled": null,
        "app_name": null,
        "disable_skan_campaign": null,
        "excluded_audience_ids": null,
        "product_specific_type": null,
        "objective_type": "LEAD_GENERATION",
        "promotion_website_type": null,
        "app_type": null,
        "languages": [],
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "view_attribution_window": "ONE_DAY",
        "page_list": null,
        "adgroup_secondary_status": "ADGROUP_STATUS_AUDIT",
        "call_to_action_id": "{{call_to_action_id}}",
        "landing_page_urls": [
            {
                "tiktok_item_id": null,
                "video_id": null,
                "landing_page_url": "{{landing_page_url}}"
            }
        ],
        "product_ids": null,
        "pixel_id": "{{pixel_id}}",
        "shopping_ads_fallback_type": null,
        "product_set_id": null,
        "carousel_image_labels": null,
        "deeplink": null
    }
}
(/code)
```

#### Destination as Custom Page

Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "LEAD_GENERATION",
    "campaign_name": "{{campaign_name}}",
    "promotion_type": "LEAD_GENERATION",
    "promotion_target_type": "EXTERNAL_WEBSITE",
    "optimization_goal": "CONVERT",
    "pixel_id": "{{pixel_id}}",
    "optimization_event": "ON_WEB_DETAIL",
    "spc_audience_age": "25+",
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": [
        "PLACEMENT_TIKTOK"
    ],
    "location_ids": [
        "6252001"
    ],
    "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM",
    "page_list": [
        {
            "page_id": "{{page_id}}"
        }
    ],
    "media_info_list": [
        {
            "media_info": {
                "identity_type": "AUTH_CODE",
                "identity_id": "{{identity_id}}",
                "tiktok_item_id": "{{tiktok_item_id}}"
            }
        }
    ],
    "call_to_action_id": "{{call_to_action_id}}"
}'
(/code)
```

Response

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "skip_learning_phase": true,
        "product_specific_type": null,
        "disable_skan_campaign": null,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "app_id": null,
        "exclude_age_under_eighteen": true,
        "objective_type": "LEAD_GENERATION",
        "pixel_id": "{{pixel_id}}",
        "video_download_disabled": false,
        "spc_type": "UNSET",
        "deeplink_type": null,
        "identity_id": null,
        "optimization_event": "ON_WEB_DETAIL",
        "shopping_ads_fallback_type": null,
        "bid_align_type": null,
        "schedule_type": "SCHEDULE_START_END",
        "automatic_add_on_enabled": null,
        "roas_bid": null,
        "call_to_action_list": [],
        "modify_time": "{{modify_time}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "location_ids": [
            "6252001"
        ],
        "optimization_goal": "CONVERT",
        "promotion_type": "LEAD_GENERATION",
        "scheduled_budget": 0,
        "promotion_target_type": "EXTERNAL_WEBSITE",
        "promotion_website_type": null,
        "product_source": null,
        "impression_tracking_url": null,
        "catalog_authorized_bc_id": null,
        "app_name": null,
        "engaged_view_attribution_window": "OFF",
        "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
        "deeplink": null,
        "app_download_url": null,
        "view_attribution_window": "ONE_DAY",
        "vbo_window": null,
        "gender": "GENDER_UNLIMITED",
        "bid_type": "BID_TYPE_CUSTOM",
        "campaign_type": null,
        "bid_price": null,
        "landing_page_urls": null,
        "operation_status": "ENABLE",
        "excluded_audience_ids": null,
        "advertiser_id": "{{advertiser_id}}",
        "app_type": null,
        "carousel_image_index": null,
        "spc_audience_age": "25+",
        "share_disabled": false,
        "click_attribution_window": "SEVEN_DAYS",
        "blocked_pangle_app_ids": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "page_list": [
            {
                "page_id": "{{page_id}}"
            }
        ],
        "catalog_creative_toggle": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "card_list": null,
        "special_industries": null,
        "campaign_app_profile_page_state": null,
        "billing_event": "OCPM",
        "languages": [],
        "click_tracking_url": null,
        "product_ids": null,
        "web_all_in_one_catalog_status": null,
        "app_promotion_type": null,
        "product_set_id": null,
        "comment_disabled": false,
        "campaign_secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "is_advanced_dedicated_campaign": false,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "budget": {{budget}},
        "identity_type": null,
        "deep_bid_type": null,
        "carousel_image_labels": null,
        "media_info_list": [
            {
                "media_info": {
                    "identity_type": "AUTH_CODE",
                    "identity_id": "{{identity_id}}",
                    "tiktok_item_id": "{{tiktok_item_id}}",
                    "identity_authorized_bc_id": "0",
                    "image_info": null,
                    "aigc_disclosure_type": null,
                    "music_info": null,
                    "video_info": null
                }
            }
        ],
        "call_to_action_id": "{{call_to_action_id}}",
        "adgroup_secondary_status": "ADGROUP_STATUS_AUDIT",
        "title_list": [
            {
                "title": ""
            }
        ],
        "attribution_event_count": "EVERY",
        "conversion_bid_price": {{conversion_bid_price}},
        "campaign_name": "{{campaign_name}}",
        "campaign_id": "{{campaign_id}}",
        "is_smart_performance_campaign": true,
        "create_time": "{{create_time}}",
        "catalog_id": null
    }
}
(/code)
```

