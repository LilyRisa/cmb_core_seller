---
title: "(To be deprecated) Create Legacy Smart+ Web Campaigns"
doc_id: 1814122471133186
path: "Use Cases / Campaign creation / (To be deprecated) Create a Legacy Smart+ Campaign / (To be deprecated) Create Legacy Smart+ Web Campaigns"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To be deprecated) Create Legacy Smart+ Web Campaigns

This article introduces how to create Smart+ Web Campaigns.

Based on your preference for the specific type of web campaign, you can create regular Smart+ Web Campaigns, Smart+ Catalog Ads, or (<span style="color:red">to-be-deprecated</span>) Smart Performance Web campaigns. For detailed instructions on creating each type, refer to their respective sections: "<a href="#item-link-Create a regular Smart+ Web Campaign">Create a regular Smart+ Web Campaign</a>",  "<a href="#item-link-Create Smart+ Catalog Ads">Create Smart+ Catalog Ads</a>", and "<a href="#item-link-(to-be-deprecated) Create a Smart Performance Web Campaign">Create a Smart Performance Web Campaign</a>".

> <span style="color:DodgerBlue">**Note**</span><br>
> Smart Performance Web Campaigns will be deprecated in the next API version. To ensure a smooth API integration, we recommend that you upgrade to Smart+ Web Campaigns as soon as possible. Smart+ Web Campaigns are a new AI-powered automation solution that leverages machine learning to drive business outcomes for advertisers.

# Create a regular Smart+ Web Campaign
A regular Smart+ Web Campaign is a Smart+ Web Campaign that doesn't use catalog products to deliver ads. For such campaigns, you can set `web_all_in_one_catalog_status` to `UNSET` or leave the `web_all_in_one_catalog_status` parameter unspecified.

## Steps
Note that the following requirements must be met. To find a complete list of parameters, see [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362).

> <span style="color:DodgerBlue">**Note**</span><br>
> Regular Smart+ Web ad groups cannot be used in [/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201) to create split test groups.


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
    <td>Website conversions</td>
    <td><code>objective_type</code></td>
    <td><code>WEB_CONVERSIONS</code></td>
  </tr>
  <tr>
    <td>Web campaign type</td>
    <td><b>Smart+ Web Campaign</b></td>
    <td><code>spc_type</code></td>
    <td><code>WEB_ALL_IN_ONE</code></td>
  </tr>
  <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3">Disabled</td>
    <td><code>campaign_type</code></td>
    <td><code>REGULAR_CAMPAIGN</code> or not passed.</td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Special ad categories (Optional)</td>
    <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: This setting is only supported for advertisers who are registered in the US or Canada.</td>
    <td><code>special_industries</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Campaign Budget Optimization (CBO)</td>
    <td>N/A<br><br>CBO is enabled by default for Smart+ Web Campaigns.</td>
    <td>N/A</td>
    <td>N/A<br><br><p><span style="color:darkred"><b>Note</b></span>: If you retrieve Smart+ Web Campaigns via <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986"  target="_blank">/campaign/get/</a>, the returned <code>budget_optimize_on</code> will be <code>true</code>.</td>
  </tr>
  <tr>
    <td rowspan="2">Optimization location (Promotion type)</td>
    <td rowspan="2">Website</td>
    <td><code>promotion_type</code></td>
    <td><code>WEBSITE</code></td>
  </tr>
  <tr>
    <td><code>promotion_website_type</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Product source details<br>&#183; Use Catalog</td>
    <td><b>Disabled</b></td>
    <td><code>web_all_in_one_catalog_status</code></td>
    <td><code>UNSET</code> or not passed</td>
  </tr>
  <tr>
    <td>Optimization goal</td>
    <td>Any of the following types:<ul><li>Click</li><li>Conversion</li><li>Landing page view</li><li>Value</li></ul></td>
    <td><code>optimization_goal</code></td>
    <td>Any of the following values:<ul><li><code>CLICK</code></li><li><code>CONVERT</code></li><li><code>TRAFFIC_LANDING_PAGE_VIEW</code></li><li><code>VALUE</code></li></ul></td>
  </tr>
  <tr>
    <td>Pixel</td>
    <td><ul><li>Required when optimization goal is Conversion or Value.</li><li>Not supported when optimization goal is Click or Landing page view.</li></ul></td>
    <td><code>pixel_id</code></td>
    <td><ul><li>Required when <code>optimization_goal</code> is set to <code>CONVERT</code> or <code>VALUE</code>.</li><li>Not supported when <code>optimization_goal</code> is set to <code>CLICK</code> or <code>TRAFFIC_LANDING_PAGE_VIEW</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Optimization event</td>
    <td><ul><li>Required when optimization goal is Conversion or Value.<ul><li>When optimization goal is Value, specify the optimization event as Complete Payment.</li></ul></li><li>Not supported when optimization goal is Click or Landing page view.</li></ul></td>
    <td><code>optimization_event</code></td>
    <td><ul><li>Required when <code>optimization_goal</code> is set to <code>CONVERT</code> or <code>VALUE</code>.<ul><li>To obtain the optimization events that have been configured for a Pixel, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>. <br>See <a href="https://business-api.tiktok.com/portal/docs?id=1739361474981889#item-link-Pixel%20events" target="_blank">Conversion events - Pixel events</a> for enum values.</li> <li>When <code>optimization_goal</code> is set to <code>VALUE</code>, set this field to <code>SHOPPING</code>.</li></ul></li><li>Not supported when <code>optimization_goal</code> is set to <code>CLICK</code>.</li><li>Can be omitted when <code>optimization_goal</code> is set to <code>TRAFFIC_LANDING_PAGE_VIEW</code>. Then <code>optimization_event</code> will default to <code>LANDING_PAGE_VIEW</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Bid Strategy</td>
    <td><ul><li>If optimization goal is Click, Conversion, or Landing page view, the strategy can be Maximum Delivery or Cost Cap  (or Target CPA).</li><li>If optimization goal is Value, the strategy can be Highest value or Target ROAS.</li></ul></td>
    <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, <code>CONVERT</code>, or <code>TRAFFIC_LANDING_PAGE_VIEW</code>, set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>. Do not pass <code>deep_bid_type</code>.<ul><li>If <code>optimization_goal</code> is <code>CONVERT</code> or <code>TRAFFIC_LANDING_PAGE_VIEW</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, pass <code>conversion_bid_price</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CLICK</code>, and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, pass <code>bid_price</code> at the same time.</li></ul></li><li>If <code>optimization_goal</code> is <code>VALUE</code>, set <code>deep_bid_type</code> to <code>VO_HIGHEST_VALUE</code> or <code>VO_MIN_ROAS</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not pass <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li>If <code>deep_bid_type</code> is <code>VO_MIN_ROAS</code>, pass <code>roas_bid</code> at the same time.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Attribution settings</td>
    <td>Select from the supported attribution setting options for the specific scenario.</td>
    <td><ul><li><code>click_attribution_window</code></li><li><code>view_attribution_window</code></li><li><code>attribution_event_count</code></li></ul></td>
    <td>To learn about the supported attribution setting options for different scenarios, see <a href="https://business-api.tiktok.com/portal/docs?id=1777694366654465#item-link-Website%20conversions" target="_blank">Attribution window and event count - Website conversions</a>.</td>
  </tr>
  <tr>
    <td>Billing event</td>
    <td><ul><li>If optimization goal is Click, the billing event should be CPC.</li><li>If optimization goal is Conversion, Landing page view, or Value, the billing event should be oCPM.</li></ul></td>
    <td><code>billing_event</code></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>CONVERT</code>, <code>TRAFFIC_LANDING_PAGE_VIEW</code>, or <code>VALUE</code>, set this field to <code>OCPM</code>.</li></ul></td>
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
    <td>Audience targeting <br>&#183; Age </td>
    <td>18+</td>
    <td><code>exclude_age_under_eighteen</code></td>
    <td><code>true</code></td>
  </tr>
  <tr>
    <td>Audience targeting<br>&#183; Exclude audience (Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>excluded_audience_ids</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Placement</td>
    <td>TikTok placement including search result or Automatic placement</td>
    <td><code>placement_type</code> <br><code>placements</code></td>
    <td>Not passed<br><br><p><span style="color:darkred"><b>Note</b></span>: Automated placement selection is a key feature of Smart+ Web Campaigns. The system will automatically configure placements based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters <code>placement_type</code> and <code>placements</code> will be ignored. To retrieve the exact placement setting of a Smart+ Web Campaign, use <a href="https://business-api.tiktok.com/portal/docs?id=1767334299811842" target="_blank">/campaign/spc/get/</a>.<ul><li>If the targeted locations (<code>location_ids</code>) of the campaign are all not supported for Pangle placement, the returned <code>placement_type</code> will be <code>PLACEMENT_TYPE_NORMAL</code> and <code>placements</code> will be <code>["PLACEMENT_TIKTOK"]</code>.<ul><li>In such cases, the <a href="https://business-api.tiktok.com/portal/docs?id=1771747626094593" target="_blank">Automatic Search Placement</a> is enabled by default.</li></ul></li><li>If one or more targeted locations (<code>location_ids</code>) of the campaign are supported for Pangle placement, the returned <code>placement_type</code> will be <code>PLACEMENT_TYPE_AUTOMATIC</code> and <code>placements</code> will be <code>["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"]</code>.</li></ul></td>
  </tr>
  <tr>
    <td>User comment (Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>comment_disabled</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Video download (Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>share_disabled</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Video sharing (Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>video_download_disabled</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Budget mode</td>
    <td>Any of the following types:<ul><li>Daily budget</li><li>Dynamic daily budget</li></ul></td>
    <td><code>budget_mode</code></td>
    <td>Any of the following values:<ul><li><code>BUDGET_MODE_DAY</code></li><li><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></td>
  </tr>
   <tr>
      <td>Schedule type</td>
      <td>Any of the following types:<ul><li>Run campaign continuously after the scheduled start time</li><li>Run campaign between the scheduled start time and end time</li></ul></td>
      <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
      <td><ul><li>To run the campaign continuously after the scheduled start time, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not pass <code>schedule_end_time</code>.</li><li>To run the campaign between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul></td>
  </tr>
  <tr>
  	<td rowspan="3">Identity</td>
    <td rowspan="3">Any of the following options:<ul><li>Custom User (custom identity)</li><li>Authorized User</li><li>TikTok User</li><li>TikTok Account User in Business Center</li></ul></td>
    <td><code>identity_type</code></td>
    <td>Any of the following values:<ul><li><code>CUSTOMIZED_USER</code></li><li><code>AUTH_CODE</code></li><li><code>TT_USER</code></li><li><code>BC_AUTH_TT</code></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), pass values to <code>identity_type</code> and <code>identity_id</code> outside the <code>media_info</code> object.</li><li>
        For Spark Ads identities, pass values to <code>identity_type</code> and <code>identity_id</code> within the <code>media_info</code> object.</li></ul></td>
  </tr>
  <tr>
    <td><code>identity_id</code></td>
    <td>Pass a valid value<br> To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
  </tr>
  <tr>
    <td><code>identity_authorized_bc_id</code></td>
    <td>Pass a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code></td>
  </tr>
  <tr>
    <td>Destination</td>
    <td>Website URL</td>
    <td><code>landing_page_urls</code><br><code>page_list</code></td>
    <td><ul><li>Specify one website URL through <code>landing_page_urls</code>.</li><li>Do not pass <code>page_list</code>.</li></ul></td>
  </tr>
 <tr>
      <td>UTM parameters<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>utm_params</code></td>
      <td>Pass valid values or not passed<br>If you set <code>landing_page_url</code> to a URL that already includes URL parameters, you can optionally pass <code>utm_params</code> at the same time to store the URL parameters used in the URL.<br>In such cases, you need to ensure that <code>utm_params</code> exactly matches the used URL parameters. The URL parameters will not be automatically appended to the <code>landing_page_url</code> upon ad delivery.
      </td>
    </tr>
    <tr>
      <td>Ad creative</td>
      <td><ul><li>
        For Custom User identity or Spark Ads PUSH with TikTok User identity or TikTok Account User in Business Center identity, specify any of the following:<ul>
          <li>one to 30 videos with cover images and one to five ad texts.</li>
          <li>one to 35 images, one piece of music, and one to five ad texts.</li>
        </ul></li><li>
        For Authorized User identity, or Spark Ads PULL with TikTok User or TikTok Account User in Business Center identity, specify one to 30 TikTok video or photo posts.</li></ul>
	  </td>
      <td>
        <code>video_info</code><br>
        <code>image_info</code><br>
        <code>music_info</code><br>
        <code>title_list</code><br>
        <code>tiktok_item_id</code>
      </td>
      <td>
      <ul><li>If <code>identity_type</code> outside of the <code>media_info</code> object is <code>CUSTOMIZED_USER</code>, specify any of the following:<ul>
          <li>one to 30 videos (<code>video_info</code>) with cover images (<code>image_info</code>) and one to five ad texts (<code>title_list</code>). Do not pass <code>tiktok_item_id</code>.</li>
          <li>one to 35 images (<code>image_info</code>), one piece of music (<code>music_info</code>), and one to five ad texts (<code>title_list</code>). Do not pass <code>video_info</code> and <code>tiktok_item_id</code>.</li>
        </ul></li>
       <li>If <code>identity_type</code> within the <code>media_info</code> object is <code>TT_USER</code> or <code>BC_AUTH_TT</code>, you can create the campaign through Spark Ads PULL or Spark Ads PUSH:<ul>
          <li><b>Spark Ads PULL</b>: Specify one to 30 TikTok posts through <code>tiktok_item_id</code>. Do not pass <code>video_info</code>, <code>image_info</code>, and <code>title_list</code>.</li>
          <li><b>Spark Ads PUSH</b>: specify any of the following:<ul>
            <li>one to 30 videos (<code>video_info</code>) with cover images (<code>image_info</code>) and one to five ad texts (<code>title_list</code>). Do not pass <code>tiktok_item_id</code>.</li>
            <li>one to 35 images (<code>image_info</code>), one piece of music (<code>music_info</code>), and one to five ad texts (<code>title_list</code>). Do not pass <code>video_info</code> and <code>tiktok_item_id</code>.</li>
          </ul></li>
        </ul>
        To learn more about Spark Ads, see <a href="https://business-api.tiktok.com/portal/docs?id=1739470744631298" target="_blank">Create Spark Ads</a>.<br><br>
        <li>If <code>identity_type</code> within the <code>media_info</code> object is <code>AUTH_CODE</code>, specify one to 30 TikTok video or photo posts through <code>tiktok_item_id</code>. Do not pass <code>video_info</code>, <code>image_info</code>, and <code>title_list</code>.
      </td>
    </tr>
  <tr>
    <td>Add product information <br>&#183; Add promo code or offer<br>(Optional)</td>
    <td>Disabled or enabled with up to 10 promo codes or offers</td>
    <td><code>promo_info_list</code></td>
    <td>Not specified or specify valid values</td>
  </tr>
  <tr>
    <td>Interactive add-ons (Optional)</td>
    <td>Disabled or enabled with any of the following types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul></td>
    <td><code>card_list</code></td>
    <td>Not passed or specify the ID of one of the following creative portfolio types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><br>To create a creative portfolio, use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to get the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card"  target="_blank">Cards</a>.</li><li>To learn about how to get the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177"  target="_blank">Stickers</a>.</li></ul></td>
  </tr>
  <tr>
    <td>Call to action</td>
    <td>Any of the following options:<ul><li>Dynamic</li><li>Standard</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you use a Spark Ads identity, you can only use dynamic call to action.</td>
    <td><code>call_to_action_id</code><br><code>call_to_action_list</code></td>
    <td><ul><li>To use dynamic call to action, pass <code>call_to_action_id</code>.</li><li>To use standard call to action, pass <code>call_to_action_list</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you set <code>identity_type</code> within the <code>media_info</code> object to <code>TT_USER</code> or <code>AUTH_CODE</code>, <code>call_to_action_id</code> is required.</td>
  </tr>
 <tr>
      <td>Tracking<br>&#183; TikTok events tracking<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; App events (Optional)</td>
      <td >Enabled or disabled</td>
      <td><code>tracking_app_id</code></td>
      <td>
        Pass the App ID of the app that you want to measure or not passed<br><br>
        To obtain a list of App IDs, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786" target="_blank">/app/list/</a>.
      </td>
  </tr>
</tbody>
</table>

### Example
#### Optimization goal as Conversion 

**Bid strategy as Target CPA  & non-Spark Ads identity**

Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "spc_type": "WEB_ALL_IN_ONE",
    "campaign_name": "{{campaign_name}}",
    "operation_status": "DISABLE",
    "promotion_type": "WEBSITE",
    "exclude_age_under_eighteen": true,
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    <span style="background-color: green;">"optimization_goal": "CONVERT",
    "pixel_id": "{{pixel_id}}",
    "optimization_event": "DOWNLOAD_START",
    "bid_type":"BID_TYPE_CUSTOM",
    "conversion_bid_price":{{conversion_bid_price}},
    "billing_event": "OCPM", </span>
    "location_ids": [
        "2077456",
        "6252001"
    ],
    "budget_mode": "BUDGET_MODE_DAY",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_FROM_NOW",
    "schedule_start_time": "{{schedule_start_time}}",
    <span style="background-color: green;">"identity_id": "{{identity_id}}",
    "identity_type": "CUSTOMIZED_USER",</span>
    "media_info_list": [
        {
            "media_info": {
                <span style="background-color: green;">"image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ],
                "video_info": {
                    "video_id": "{{video_id}}"
                }</span>
            }
        }
    ],
    "title_list": [
        {
            "title": "{{title}}"
        }
    ],
    "call_to_action_list": [
        {
            "call_to_action": "ORDER_NOW"
        }
    ],
  "landing_page_urls": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ]
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
        "landing_page_urls": [
            {
                "tiktok_item_id": null,
                "video_id": null,
                "landing_page_url": "{{landing_page_url}}"
            }
        ],
        "deep_bid_type": null,
        "impression_tracking_url": null,
        "card_list": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "page_list": null,
        "promotion_type": "WEBSITE",
        "campaign_type": "REGULAR_CAMPAIGN",
        "excluded_audience_ids": null,
        "advertiser_id": "{{advertiser_id}}",
        "engaged_view_attribution_window": null,
        "is_smart_performance_campaign": true,
        "is_advanced_dedicated_campaign": false,
        "app_download_url": null,
        "campaign_id": "{{campaign_id}}",
        "campaign_app_profile_page_state": null,
        "comment_disabled": false,
        "identity_id": "{{identity_id}}",
        "modify_time": "{{modify_time}}",
        "optimization_event": "DOWNLOAD_START",
        "optimization_goal": "CONVERT",
        "call_to_action_id": null,
        "deeplink": "",
        "spc_type": "WEB_ALL_IN_ONE",
        "app_name": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "billing_event": "OCPM",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "pixel_id": "{{pixel_id}}",
        "schedule_type": "SCHEDULE_FROM_NOW",
        "title_list": [
            {
                "title": "{{title}}"
            }
        ],
        "app_promotion_type": null,
        "exclude_age_under_eighteen": true,
        "budget": {{budget}},
        "promotion_website_type": "UNSET",
        "campaign_name": "{{campaign_name}}",
        "special_industries": null,
        "operation_status": "DISABLE",
        "app_id": null,
        "attribution_event_count": "ONCE",
        "gender": "GENDER_UNLIMITED",
        "call_to_action_list": [
            {
                "call_to_action": "ORDER_NOW"
            }
        ],
        "bid_price": null,
        "create_time": "{{create_time}}",
        "objective_type": "WEB_CONVERSIONS",
        "click_tracking_url": null,
        "location_ids": [
            "2077456",
            "6252001"
        ],
        "schedule_start_time": "{{schedule_start_time}}",
        "conversion_bid_price": {{conversion_bid_price}},
        "app_type": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "blocked_pangle_app_ids": null,
        "video_download_disabled": false,
        "media_info_list": [
            {
                "media_info": {
                    "video_info": {
                        "video_id": "{{video_id}}",
                        "file_name": ""
                    },
                    "aigc_disclosure_type": null,
                    "identity_id": null,
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "identity_type": null,
                    "tiktok_item_id": null
                }
            }
        ],
        "scheduled_budget": 0,
        "deeplink_type": null,
        "skip_learning_phase": true,
        "adgroup_secondary_status": "ADGROUP_STATUS_CAMPAIGN_DISABLE",
        "budget_mode": "BUDGET_MODE_DAY",
        "languages": [],
        "share_disabled": false,
        "campaign_secondary_status": "CAMPAIGN_STATUS_DISABLE",
        "view_attribution_window": "ONE_DAY",
        "bid_type": "BID_TYPE_CUSTOM",
        "identity_type": "CUSTOMIZED_USER",
        "click_attribution_window": "SEVEN_DAYS"
    }
}
(/code)
```
**Bid strategy as Target CPA & Spark Ads identity using organic posts from authorization codes**

Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "spc_type": "WEB_ALL_IN_ONE",
    "campaign_name": "{{campaign_name}}",
    "operation_status": "DISABLE",
    "promotion_type": "WEBSITE",
    "exclude_age_under_eighteen": true,
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    <span style="background-color: green;">"optimization_goal": "CONVERT",
    "pixel_id": "{{pixel_id}}",
    "optimization_event": "DOWNLOAD_START",
    "bid_type":"BID_TYPE_CUSTOM",
    "conversion_bid_price":{{conversion_bid_price}},
    "billing_event": "OCPM",</span>
    "location_ids": [
        "2077456",
        "6252001"
    ],
    "budget_mode": "BUDGET_MODE_DAY",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_FROM_NOW",
    "schedule_start_time": "{{schedule_start_time}}",
    "media_info_list": [
        {
            "media_info": {
                <span style="background-color: green;">"identity_type": "AUTH_CODE",
                "identity_id": "{{identity_id}}",
                "tiktok_item_id": "{{tiktok_item_id}}"</span>
            }
        }
    ],
    <span style="background-color: green;">"call_to_action_id": "{{call_to_action_id}}",</span>
    "landing_page_urls": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ]
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
        "create_time": "{{create_time}}",
        "title_list": [
            {
                "title": ""
            }
        ],
        "share_disabled": false,
        "media_info_list": [
            {
                "media_info": {
                    "image_info": null,
                    "aigc_disclosure_type": null,
                    "video_info": null,
                    "tiktok_item_id": "{{tiktok_item_id}}",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "AUTH_CODE"
                }
            }
        ],
        "budget_mode": "BUDGET_MODE_DAY",
        "comment_disabled": false,
        "engaged_view_attribution_window": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "identity_id": null,
        "click_attribution_window": "SEVEN_DAYS",
        "bid_price": null,
        "promotion_website_type": "UNSET",
        "app_type": null,
        "adgroup_secondary_status": "ADGROUP_STATUS_CAMPAIGN_DISABLE",
        "gender": "GENDER_UNLIMITED",
        "campaign_app_profile_page_state": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "languages": [],
        "schedule_type": "SCHEDULE_FROM_NOW",
        "call_to_action_id": "{{call_to_action_id}}",
        "skip_learning_phase": true,
        "budget": {{budget}},
        "location_ids": [
            "2077456",
            "6252001"
        ],
        "deeplink": "",
        "objective_type": "WEB_CONVERSIONS",
        "modify_time": "{{modify_time}}",
        "identity_type": null,
        "excluded_audience_ids": null,
        "video_download_disabled": false,
        "deeplink_type": null,
        "app_promotion_type": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "call_to_action_list": [],
        "spc_type": "WEB_ALL_IN_ONE",
        "app_download_url": null,
        "optimization_goal": "CONVERT",
        "is_smart_performance_campaign": true,
        "card_list": null,
        "landing_page_urls": [
            {
                "landing_page_url": "{{landing_page_url}}",
                "tiktok_item_id": null,
                "video_id": null
            }
        ],
        "campaign_secondary_status": "CAMPAIGN_STATUS_DISABLE",
        "deep_bid_type": null,
        "view_attribution_window": "ONE_DAY",
        "app_id": null,
        "promotion_type": "WEBSITE",
        "schedule_start_time": "{{schedule_start_time}}",
        "app_name": null,
        "campaign_name": "{{campaign_name}}",
        "click_tracking_url": null,
        "conversion_bid_price": {{conversion_bid_price}},
        "bid_type": "BID_TYPE_CUSTOM",
        "scheduled_budget": 0,
        "special_industries": null,
        "advertiser_id": "{{advertiser_id}}",
        "is_advanced_dedicated_campaign": false,
        "operation_status": "DISABLE",
        "blocked_pangle_app_ids": null,
        "exclude_age_under_eighteen": true,
        "impression_tracking_url": null,
        "optimization_event": "DOWNLOAD_START",
        "attribution_event_count": "ONCE",
        "page_list": null,
        "campaign_type": "REGULAR_CAMPAIGN",
        "pixel_id": "{{pixel_id}}",
        "billing_event": "OCPM",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "campaign_id": "{{campaign_id}}"
    }
}
(/code)
```
**Bid strategy as Target CPA & Spark Ads identity using organic posts from linked Business Accounts**

Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "spc_type": "WEB_ALL_IN_ONE",
    "campaign_name": "{{campaign_name}}",
    "operation_status": "DISABLE",
    "promotion_type": "WEBSITE",
    "exclude_age_under_eighteen": true,
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    <span style="background-color: green;">"optimization_goal": "CONVERT",
    "pixel_id": "{{pixel_id}}",
    "optimization_event": "DOWNLOAD_START",
    "bid_type":"BID_TYPE_CUSTOM",
    "conversion_bid_price":{{conversion_bid_price}},
    "billing_event": "OCPM",</span>
    "location_ids": [
        "2077456",
        "6252001"
    ],
    "budget_mode": "BUDGET_MODE_DAY",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_FROM_NOW",
    "schedule_start_time": "{{schedule_start_time}}",
    "media_info_list": [
        {
            "media_info": {
                 <span style="background-color: green;">"identity_type": "TT_USER",
                "identity_id": "{{identity_id}}",
                "tiktok_item_id": "{{tiktok_item_id}}"</span>
            }
        }
    ],
     <span style="background-color: green;">"call_to_action_id": "{{call_to_action_id}}",</span>
    "landing_page_urls": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ]
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
        "comment_disabled": false,
        "page_list": null,
        "campaign_app_profile_page_state": null,
        "call_to_action_id": "{{call_to_action_id}}",
        "schedule_type": "SCHEDULE_FROM_NOW",
        "attribution_event_count": "ONCE",
        "optimization_event": "DOWNLOAD_START",
        "dayparting": "1111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "budget": {{budget}},
        "pixel_id": "{{pixel_id}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "objective_type": "WEB_CONVERSIONS",
        "blocked_pangle_app_ids": null,
        "skip_learning_phase": true,
        "click_tracking_url": null,
        "identity_id": "{{identity_id}}",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "campaign_secondary_status": "CAMPAIGN_STATUS_DISABLE",
        "gender": "GENDER_UNLIMITED",
        "conversion_bid_price": {{conversion_bid_price}},
        "campaign_type": "REGULAR_CAMPAIGN",
        "app_type": null,
        "deep_bid_type": null,
        "promotion_website_type": "UNSET",
        "location_ids": [
            "2077456",
            "6252001"
        ],
        "is_smart_performance_campaign": true,
        "create_time": "{{create_time}}",
        "special_industries": null,
        "bid_price": null,
        "media_info_list": [
            {
                "media_info": {
                    "video_info": null,
                    "image_info": null,
                    "aigc_disclosure_type": null,
                    "tiktok_item_id": "{{tiktok_item_id}}",
                    "identity_id": "{{identity_id}}",
                    "identity_type": "TT_USER"
                }
            }
        ],
        "budget_mode": "BUDGET_MODE_DAY",
        "adgroup_secondary_status": "ADGROUP_STATUS_CAMPAIGN_DISABLE",
        "engaged_view_attribution_window": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "optimization_goal": "CONVERT",
        "app_name": null,
        "languages": [],
        "title_list": [
            {
                "title": "{{title}}"
            }
        ],
        "deeplink": "{{deeplink}}",
        "deeplink_type": null,
        "identity_type": "{{identity_type}}",
        "schedule_end_time": "{{schedule_end_time}}",
        "modify_time": "{{modify_time}}",
        "campaign_name": "{{campaign_name}}",
        "impression_tracking_url": null,
        "share_disabled": false,
        "landing_page_urls": [
            {
                "video_id": null,
                "landing_page_url": "{{landing_page_url}}",
                "tiktok_item_id": null
            }
        ],
        "billing_event": "OCPM",
        "excluded_audience_ids": null,
        "scheduled_budget": 0,
        "promotion_type": "WEBSITE",
        "spc_type": "WEB_ALL_IN_ONE",
        "operation_status": "DISABLE",
        "app_id": "{{app_id}}",
        "exclude_age_under_eighteen": true,
        "click_attribution_window": "SEVEN_DAYS",
        "app_download_url": "{{app_download_url}}",
        "campaign_id": "{{campaign_id}}",
        "bid_type": "BID_TYPE_CUSTOM",
        "video_download_disabled": false,
        "view_attribution_window": "ONE_DAY",
        "is_advanced_dedicated_campaign": false,
        "card_list": null,
        "app_promotion_type": null,
        "advertiser_id": "{{advertiser_id}}",
        "call_to_action_list": []
    }
}
(/code)
```
#### Optimization goal as Value 
**Bid strategy as Highest value & non-Spark Ads identity**

Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "spc_type": "WEB_ALL_IN_ONE",
    "campaign_name": "{{campaign_name}}",
    "operation_status": "DISABLE",
    "promotion_type": "WEBSITE",
    "exclude_age_under_eighteen": true,
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    <span style="background-color: green;">"optimization_goal": "VALUE",
    "pixel_id": "{{pixel_id}}",
    "optimization_event": "SHOPPING",
    "deep_bid_type": "VO_HIGHEST_VALUE",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",</span>
    "location_ids": [
        "2077456",
        "6252001"
    ],
    "budget_mode": "BUDGET_MODE_DAY",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_FROM_NOW",
    "schedule_start_time": "{{schedule_start_time}}",
    <span style="background-color: green;">"identity_id": "{{identity_id}}",
    "identity_type": "CUSTOMIZED_USER", </span>
    "media_info_list": [
        {
            "media_info": {
                <span style="background-color: green;">"image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ],
                "video_info": {
                    "video_id": "{{video_id}}" </span>
                }
            }
        }
    ],
    "title_list": [
        {
            "title": "{{title}}"
        }
    ],
    "call_to_action_list": [
        {
            "call_to_action": "ORDER_NOW"
        }
    ],
    "landing_page_urls": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ]
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
        "click_attribution_window": "SEVEN_DAYS",
        "title_list": [
            {
                "title": "{{title}}"
            }
        ],
        "app_id": null,
        "impression_tracking_url": null,
        "campaign_secondary_status": "CAMPAIGN_STATUS_DISABLE",
        "campaign_name": "{{campaign_name}}",
        "video_download_disabled": false,
        "card_list": null,
        "promotion_website_type": "UNSET",
        "identity_type": "CUSTOMIZED_USER",
        "share_disabled": false,
        "objective_type": "WEB_CONVERSIONS",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "campaign_id": "{{campaign_id}}",
        "gender": "GENDER_UNLIMITED",
        "location_ids": [
            "2077456",
            "6252001"
        ],
        "app_promotion_type": null,
        "languages": [],
        "adgroup_secondary_status": "ADGROUP_STATUS_CAMPAIGN_DISABLE",
        "conversion_bid_price": null,
        "operation_status": "DISABLE",
        "schedule_end_time": "{{schedule_end_time}}",
        "is_smart_performance_campaign": true,
        "bid_type": "BID_TYPE_NO_BID",
        "scheduled_budget": 0,
        "campaign_type": "REGULAR_CAMPAIGN",
        "optimization_goal": "VALUE",
        "call_to_action_id": null,
        "view_attribution_window": "ONE_DAY",
        "excluded_audience_ids": null,
        "skip_learning_phase": true,
        "budget_mode": "BUDGET_MODE_DAY",
        "optimization_event": "SHOPPING",
        "blocked_pangle_app_ids": null,
        "is_advanced_dedicated_campaign": false,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "bid_price": null,
        "pixel_id": "{{pixel_id}}",
        "budget": "{{budget}}",
        "modify_time": "{{modify_time}}",
        "promotion_type": "WEBSITE",
        "deeplink_type": null,
        "billing_event": "OCPM",
        "deeplink": "",
        "special_industries": null,
        "comment_disabled": false,
        "identity_id": "{{identity_id}}",
        "click_tracking_url": null,
        "exclude_age_under_eighteen": true,
        "spc_type": "WEB_ALL_IN_ONE",
        "app_type": null,
        "media_info_list": [
            {
                "media_info": {
                    "video_info": {
                        "video_id": "{{video_id}}",
                        "file_name": ""
                    },
                    "aigc_disclosure_type": null,
                    "identity_type": null,
                    "tiktok_item_id": null,
                    "identity_id": null,
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ]
                }
            }
        ],
        "schedule_type": "SCHEDULE_FROM_NOW",
        "deep_bid_type": "VO_HIGHEST_VALUE",
        "attribution_event_count": "EVERY",
        "advertiser_id": "{{advertiser_id}}",
        "engaged_view_attribution_window": null,
        "create_time": "{{create_time}}",
        "campaign_app_profile_page_state": null,
        "app_name": null,
        "landing_page_urls": [
            {
                "landing_page_url": "{{landing_page_url}}",
                "tiktok_item_id": null,
                "video_id": null
            }
        ],
        "call_to_action_list": [
            {
                "call_to_action": "ORDER_NOW"
            }
        ],
        "page_list": null,
        "app_download_url": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "schedule_start_time": "{{schedule_start_time}}"
    }
}
(/code)
```
#### Optimization goal as Click 
**Bid strategy as Cost cap per click & non-Spark Ads identity**

Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "spc_type": "WEB_ALL_IN_ONE",
    "campaign_name": "{{campaign_name}}",
    "operation_status": "DISABLE",
    "promotion_type": "WEBSITE",
    "exclude_age_under_eighteen": true,
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    <span style="background-color: green;">"optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_CUSTOM",
    "bid_price": {{bid_price}},
    "billing_event": "CPC",</span>
    "location_ids": [
        "2077456",
        "6252001"
    ],
    "budget_mode": "BUDGET_MODE_DAY",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_FROM_NOW",
    "schedule_start_time": "{{schedule_start_time}}",
    <span style="background-color: green;"> "identity_id": "{{identity_id}}",
    "identity_type": "CUSTOMIZED_USER",</span>
    "media_info_list": [
        {
            "media_info": {
                <span style="background-color: green;">"image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ],
                "video_info": {
                    "video_id": "{{video_id}}" </span>
                }
            }
        }
    ],
    "title_list": [
        {
            "title": "{{title}}"
        }
    ],
    "call_to_action_list": [
        {
            "call_to_action": "ORDER_NOW"
        }
    ],
  "landing_page_urls": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ]
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
        "billing_event": "CPC",
        "bid_price": {{bid_price}},
        "objective_type": "WEB_CONVERSIONS",
        "is_smart_performance_campaign": true,
        "scheduled_budget": 0,
        "budget": {{budget}},
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "deeplink": "",
        "pixel_id": null,
        "title_list": [
            {
                "title": "{{title}}"
            }
        ],
        "app_name": null,
        "schedule_type": "SCHEDULE_FROM_NOW",
        "skip_learning_phase": null,
        "languages": [],
        "excluded_audience_ids": null,
        "create_time": "{{create_time}}",
        "operation_status": "DISABLE",
        "optimization_event": null,
        "bid_type": "BID_TYPE_CUSTOM",
        "app_id": null,
        "app_download_url": null,
        "identity_type": "CUSTOMIZED_USER",
        "is_advanced_dedicated_campaign": false,
        "campaign_name": "{{campaign_name}}",
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "campaign_secondary_status": "CAMPAIGN_STATUS_DISABLE",
        "advertiser_id": "{{advertiser_id}}",
        "special_industries": null,
        "click_attribution_window": null,
        "landing_page_urls": [
            {
                "tiktok_item_id": null,
                "landing_page_url": "{{landing_page_url}}",
                "video_id": null
            }
        ],
        "app_type": null,
        "attribution_event_count": null,
        "spc_type": "WEB_ALL_IN_ONE",
        "share_disabled": false,
        "impression_tracking_url": null,
        "call_to_action_list": [
            {
                "call_to_action": "ORDER_NOW"
            }
        ],
        "exclude_age_under_eighteen": true,
        "card_list": null,
        "campaign_type": "REGULAR_CAMPAIGN",
        "click_tracking_url": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "promotion_website_type": "UNSET",
        "media_info_list": [
            {
                "media_info": {
                    "tiktok_item_id": null,
                    "aigc_disclosure_type": null,
                    "identity_type": null,
                    "video_info": {
                        "video_id": "{{video_id}}",
                        "file_name": ""
                    },
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "identity_id": null
                }
            }
        ],
        "video_download_disabled": false,
        "page_list": null,
        "deeplink_type": null,
        "promotion_type": "WEBSITE",
        "schedule_end_time": "{{schedule_end_time}}",
        "view_attribution_window": null,
        "comment_disabled": false,
        "engaged_view_attribution_window": null,
        "budget_mode": "BUDGET_MODE_DAY",
        "campaign_id": "{{campaign_id}}",
        "conversion_bid_price": null,
        "call_to_action_id": null,
        "location_ids": [
            "2077456",
            "6252001"
        ],
        "app_promotion_type": null,
        "gender": "GENDER_UNLIMITED",
        "optimization_goal": "CLICK",
        "deep_bid_type": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "identity_id": "{{identity_id}}",
        "modify_time": "{{modify_time}}",
        "adgroup_secondary_status": "ADGROUP_STATUS_CAMPAIGN_DISABLE",
        "campaign_app_profile_page_state": null,
        "blocked_pangle_app_ids": null
    }
}
(/code)
```
#### Optimization goal as Landing page view  
**Bid strategy as Target CPA & non-Spark Ads identity**

Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "spc_type": "WEB_ALL_IN_ONE",
    "campaign_name": "{{campaign_name}}",
    "operation_status": "DISABLE",
    "promotion_type": "WEBSITE",
    "exclude_age_under_eighteen": true,
    "placement_type": "PLACEMENT_TYPE_NORMAL",
    "placements": ["PLACEMENT_TIKTOK"],
    <span style="background-color: green;">"optimization_goal": "TRAFFIC_LANDING_PAGE_VIEW",
    "bid_type": "BID_TYPE_CUSTOM",
    "conversion_bid_price": {{conversion_bid_price}},
    "billing_event": "OCPM", </span>
    "location_ids": [
        "2077456",
        "6252001"
    ],
    "budget_mode": "BUDGET_MODE_DAY",
    "budget": {{budget}},
    "schedule_type": "SCHEDULE_FROM_NOW",
    "schedule_start_time": "{{schedule_start_time}}",
    <span style="background-color: green;">"identity_id": "{{identity_id}}",
    "identity_type": "CUSTOMIZED_USER", </span>
    "media_info_list": [
        {
            "media_info": {
                <span style="background-color: green;"> "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ],
                "video_info": {
                    "video_id": "{{video_id}}"</span>
                }
            }
        }
    ],
    "title_list": [
        {
            "title": "{{title}}"
        }
    ],
    "call_to_action_list": [
        {
            "call_to_action": "ORDER_NOW"
        }
    ],
  "landing_page_urls": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ]
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
        "blocked_pangle_app_ids": null,
        "promotion_type": "WEBSITE",
        "is_smart_performance_campaign": true,
        "identity_type": "CUSTOMIZED_USER",
        "advertiser_id": "{{advertiser_id}}",
        "scheduled_budget": 0,
        "campaign_secondary_status": "CAMPAIGN_STATUS_DISABLE",
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "operation_status": "DISABLE",
        "title_list": [
            {
                "title": "{{title}}"
            }
        ],
        "objective_type": "WEB_CONVERSIONS",
        "campaign_type": "REGULAR_CAMPAIGN",
        "pixel_id": null,
        "app_id": null,
        "card_list": null,
        "location_ids": [
            "2077456",
            "6252001"
        ],
        "identity_id": "{{identity_id}}",
        "budget": {{budget}},
        "deeplink": "",
        "schedule_start_time": "{{schedule_start_time}}",
        "app_name": null,
        "billing_event": "OCPM",
        "app_promotion_type": null,
        "gender": "GENDER_UNLIMITED",
        "page_list": null,
        "optimization_event": "LANDING_PAGE_VIEW",
        "call_to_action_id": null,
        "optimization_goal": "TRAFFIC_LANDING_PAGE_VIEW",
        "attribution_event_count": "ONCE",
        "landing_page_urls": [
            {
                "video_id": null,
                "tiktok_item_id": null,
                "landing_page_url": "{{landing_page_url}}"
            }
        ],
        "share_disabled": false,
        "app_type": null,
        "is_advanced_dedicated_campaign": false,
        "campaign_app_profile_page_state": null,
        "spc_type": "WEB_ALL_IN_ONE",
        "excluded_audience_ids": null,
        "skip_learning_phase": true,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "deep_bid_type": null,
        "click_tracking_url": null,
        "schedule_type": "SCHEDULE_FROM_NOW",
        "conversion_bid_price": {{conversion_bid_price}},
        "bid_price": null,
        "campaign_id": "{{campaign_id}}",
        "bid_type": "BID_TYPE_CUSTOM",
        "promotion_website_type": "UNSET",
        "exclude_age_under_eighteen": true,
        "schedule_end_time": "{{schedule_end_time}}",
        "app_download_url": null,
        "view_attribution_window": "ONE_DAY",
        "impression_tracking_url": null,
        "budget_mode": "BUDGET_MODE_DAY",
        "video_download_disabled": false,
        "deeplink_type": null,
        "special_industries": null,
        "languages": [],
        "media_info_list": [
            {
                "media_info": {
                    "identity_type": null,
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "video_info": {
                        "video_id": "{{video_id}}",
                        "file_name": ""
                    },
                    "aigc_disclosure_type": null,
                    "identity_id": null,
                    "tiktok_item_id": null
                }
            }
        ],
        "comment_disabled": false,
        "engaged_view_attribution_window": null,
        "modify_time": "{{modify_time}}",
        "call_to_action_list": [
            {
                "call_to_action": "ORDER_NOW"
            }
        ],
        "click_attribution_window": "SEVEN_DAYS",
        "campaign_name": "{{campaign_name}}",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "create_time": "{{create_time}}",
        "adgroup_secondary_status": "ADGROUP_STATUS_CAMPAIGN_DISABLE"
    }
}
(/code)
```

# Create Smart+ Catalog Ads
Smart+ Catalog Ads are Smart+ Web Campaigns that use catalog products to deliver ads. For such campaigns, you need to set `web_all_in_one_catalog_status` to `OPEN`. By utilizing Smart+ Catalog Ads, you can automatically generate ads in diverse formats such as catalog video, catalog carousel, and single video.

## Prerequisite
Creating Smart+ Catalog Ads requires setting up an E-commerce catalog in your Business Center first.

To set up the catalog:
1. Create an **E-commerce** catalog using [/catalog/create/](https://business-api.tiktok.com/portal/docs?id=1740306481704961). 
2. Upload products to the catalog using [/catalog/product/upload/](https://business-api.tiktok.com/portal/docs?id=1740497429681153) (JSON schema), [/catalog/product/file/](https://business-api.tiktok.com/portal/docs?id=1740496787164161) (CSV feed template), or [/catalog/feed/create/](https://business-api.tiktok.com/portal/docs?id=1740665161957377)(online data feed schedule).
3. Check the product handling results using [/catalog/product/log/](https://business-api.tiktok.com/portal/docs?id=1740570027173889). 
<br>Pass in the `feed_log_id` obtained from Step 2. If the field `warn_affected_products` in the response is not null, examine the issue details and return to Step 2 to reupload the product.
4. (Optional) Create an E-commerce product set using [/catalog/set/create/](https://business-api.tiktok.com/portal/docs?id=1740572891104257).
<br>If you want to have products selected from a product set, creating a product set is necessary. Otherwise, you can skip this step.
5. (Optional) Invite members to Business Center and grant the admin permission using [/bc/member/invite/](https://business-api.tiktok.com/portal/docs?id=1739939455765505). 
<br> You can also choose `advertiser_role` that you want to assign to the members invited.
6. (Optional) Share a catalog with members and grant catalog management access using [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121). 
<br> Make sure to specify `CATALOG` in the `asset_type` field and `ADMIN` in the `catalog_role` field. 

For all catalog APIs, see [here](https://business-api.tiktok.com/portal/docs?id=1739578477445121).

## Steps

Note that the following requirements must be met. To find a complete list of parameters, see [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362).

> <span style="color:DodgerBlue">**Note**</span><br>
> Smart+ Catalog ad groups cannot be used in [/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201) to create split test groups.


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
    <td>Website conversions</td>
    <td><code>objective_type</code></td>
    <td><code>WEB_CONVERSIONS</code></td>
  </tr>
  <tr>
    <td>Web campaign type</td>
    <td><b>Smart+ Web Campaign</b></td>
    <td><code>spc_type</code></td>
    <td><code>WEB_ALL_IN_ONE</code></td>
  </tr>
  <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3">Disabled</td>
    <td><code>campaign_type</code></td>
    <td><code>REGULAR_CAMPAIGN</code> or not passed.</td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Special ad categories (Optional)</td>
    <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: This setting is only supported for advertisers who are registered in the US or Canada.</td>
    <td><code>special_industries</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Campaign Budget Optimization (CBO)</td>
    <td>N/A<br><br>CBO is enabled by default for Smart+ Web Campaigns.</td>
    <td>N/A</td>
    <td>N/A<br><br><p><span style="color:darkred"><b>Note</b></span>: If you retrieve Smart+ Web Campaigns via <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986"  target="_blank">/campaign/get/</a>, the returned <code>budget_optimize_on</code> will be <code>true</code>.</td>
  </tr>
  <tr>
    <td rowspan="2">Optimization location (Promotion type)</td>
    <td rowspan="2">Website</td>
    <td><code>promotion_type</code></td>
    <td><code>WEBSITE</code></td>
  </tr>
  <tr>
    <td><code>promotion_website_type</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td rowspan="3">Product source details<br>&#183; Use Catalog</td>
    <td rowspan="3"><b>Enabled with an E-commerce catalog with at least one approved product</b> </td>
    <td><code>web_all_in_one_catalog_status</code></td>
    <td><code>OPEN</code></td>
  </tr>
  <tr>
    <td><code>product_source</code></td>
    <td><code>CATALOG</code></td>
  </tr>
  <tr>
    <td><code>catalog_id</code><br><code>catalog_authorized_bc_id</code></td>
    <td>Pass valid values.<br>The catalog (<code>catalog_id</code>) needs to have at least one approved product. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least 1.<br><br>To learn about how to create an E-commerce catalog, see <a href="#item-link-Prerequisite">Prerequisite</a>.</td>
  </tr>
  <tr>
    <td>Products</td>
    <td>Any of the following options:<ul><li>All products</li><li>Product set</li><li>Specific products with a maximum of 20 products</li></ul></td>
    <td><code>product_specific_type</code><br><code>product_set_id</code><br><code>product_ids</code></td>
    <td>Set <code>product_specific_type</code> to <code>ALL</code>, <code>PRODUCT_SET</code> or <code>CUSTOMIZED_PRODUCTS</code>.<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, you don't need to pass <code>product_set_id</code> and <code>product_ids</code>.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, you need to pass <code>product_set_id</code>. <code>product_ids</code> is not needed.</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, you need to pass a maximum of 20 available E-commerce catalog products through <code>product_ids</code>. <code>product_set_id</code> is not needed.<ul><li>To retrieve the product ID (<code>product_id</code>) of each E-commerce catalog product, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>.</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: To determine whether an E-commerce catalog product is available for use in ads, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402" target="_blank">/catalog/product/get/</a>. If the returned <code>ad_creation_eligible</code> for the product is <code>AVAILABLE</code>, you can use the product in ads.</td>
  </tr>
  <tr>
    <td>Optimization goal</td>
    <td>Any of the following types:<ul><li>Click</li><li>Conversion</li><li>Landing page view</li><li>Value</li></ul></td>
    <td><code>optimization_goal</code></td>
    <td>Any of the following values:<ul><li><code>CLICK</code></li><li><code>CONVERT</code></li><li><code>TRAFFIC_LANDING_PAGE_VIEW</code></li><li><code>VALUE</code></li></ul></td>
  </tr>
  <tr>
    <td>Pixel</td>
    <td><ul><li>Required when optimization goal is Conversion or Value.</li><li>Not supported when optimization goal is Click or Landing page view.</li></ul></td>
    <td><code>pixel_id</code></td>
    <td><ul><li>Required when <code>optimization_goal</code> is set to <code>CONVERT</code> or <code>VALUE</code>.</li><li>Not supported when <code>optimization_goal</code> is set to <code>CLICK</code> or <code>TRAFFIC_LANDING_PAGE_VIEW</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Optimization event</td>
    <td><ul><li>Required when optimization goal is Conversion or Value.<ul><li>When optimization goal is Value, specify the optimization event as Complete Payment.</li></ul></li><li>Not supported when optimization goal is Click or Landing page view.</li></ul></td>
    <td><code>optimization_event</code></td>
    <td><ul><li>Required when <code>optimization_goal</code> is set to <code>CONVERT</code> or <code>VALUE</code>.<ul><li>To obtain the optimization events that have been configured for a Pixel, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978" target="_blank">/pixel/list/</a>. <br>See <a href="https://business-api.tiktok.com/portal/docs?id=1739361474981889#item-link-Pixel%20events" target="_blank">Conversion events - Pixel events</a> for enum values.</li> <li>When <code>optimization_goal</code> is set to <code>VALUE</code>, set this field to <code>SHOPPING</code>.</li></ul></li><li>Not supported when <code>optimization_goal</code> is set to <code>CLICK</code>.</li><li>Can be omitted when <code>optimization_goal</code> is set to <code>TRAFFIC_LANDING_PAGE_VIEW</code>. Then <code>optimization_event</code> will default to <code>LANDING_PAGE_VIEW</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Bid Strategy</td>
    <td><ul><li>If optimization goal is Click, or Conversion, the strategy can be Maximum Delivery or Cost Cap  (or Target CPA).</li><li>If optimization goal is Landing page view, the strategy can only be Maximum Delivery.</li><li>If optimization goal is Value, the strategy can be Highest value or Target ROAS.</li></ul></td>
    <td><code>bid_type</code><br><code>bid_price</code><br><code>conversion_bid_price</code><br><code>deep_bid_type</code><br><code>roas_bid</code></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code> or <code>CONVERT</code>, set <code>bid_type</code> to <code>BID_TYPE_CUSTOM</code> or <code>BID_TYPE_NO_BID</code>. Do not pass <code>deep_bid_type</code>.<ul><li>If <code>optimization_goal</code> is <code>CONVERT</code> and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, pass <code>conversion_bid_price</code> at the same time.</li><li>If <code>optimization_goal</code> is <code>CLICK</code>, and <code>bid_type</code> is <code>BID_TYPE_CUSTOM</code>, pass <code>bid_price</code> at the same time.</li></ul></li><li>If <code>optimization_goal</code> is <code>TRAFFIC_LANDING_PAGE_VIEW</code>, set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not pass <code>deep_bid_type</code>.</li><li>If <code>optimization_goal</code> is <code>VALUE</code>, set <code>deep_bid_type</code> to <code>VO_HIGHEST_VALUE</code> or <code>VO_MIN_ROAS</code>, and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not pass <code>conversion_bid_price</code> and <code>bid_price</code>.<ul><li>If <code>deep_bid_type</code> is <code>VO_MIN_ROAS</code>, pass <code>roas_bid</code> at the same time.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Attribution settings</td>
    <td>Select from the supported attribution setting options for the specific scenario.</td>
    <td><ul><li><code>click_attribution_window</code></li><li><code>view_attribution_window</code></li><li><code>attribution_event_count</code></li></ul></td>
    <td>To learn about the supported attribution setting options for different scenarios, see <a href="https://business-api.tiktok.com/portal/docs?id=1777694366654465#item-link-Website%20conversions" target="_blank">Attribution window and event count - Website conversions</a>.</td>
  </tr>
  <tr>
    <td>Billing event</td>
    <td><ul><li>If optimization goal is Click, the billing event should be CPC.</li><li>If optimization goal is Conversion, Landing page view, or Value, the billing event should be oCPM.</li></ul></td>
    <td><code>billing_event</code></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code>, set this field to <code>CPC</code>.</li><li>If <code>optimization_goal</code> is <code>CONVERT</code>, <code>TRAFFIC_LANDING_PAGE_VIEW</code>, or <code>VALUE</code>, set this field to <code>OCPM</code>.</li></ul></td>
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
    <td>Audience targeting <br>&#183; Age </td>
    <td>18+</td>
    <td><code>exclude_age_under_eighteen</code></td>
    <td><code>true</code></td>
  </tr>
  <tr>
    <td>Audience targeting<br>&#183; Exclude audience (Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>excluded_audience_ids</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Placement</td>
    <td>TikTok placement including search result or Automatic placement</td>
    <td><code>placement_type</code> <br><code>placements</code></td>
    <td>Not passed<br><br><p><span style="color:darkred"><b>Note</b></span>: Automated placement selection is a key feature of Smart+ Catalog Ads. The system will automatically configure placements based on your other settings and select high-quality traffic from various placements to deliver better results. If provided, the parameters <code>placement_type</code> and <code>placements</code> will be ignored. To retrieve the exact placement setting of Smart+ Catalog Ads, use <a href="https://business-api.tiktok.com/portal/docs?id=1767334299811842" target="_blank">/campaign/spc/get/</a>.<ul><li>If the targeted locations (<code>location_ids</code>) of the campaign are all not supported for Pangle placement, the returned <code>placement_type</code> will be <code>PLACEMENT_TYPE_NORMAL</code> and <code>placements</code> will be <code>["PLACEMENT_TIKTOK"]</code>.<ul><li>In such cases, the <a href="https://business-api.tiktok.com/portal/docs?id=1771747626094593" target="_blank">Automatic Search Placement</a> is enabled by default.</li></ul></li><li>If one or more targeted locations (<code>location_ids</code>) of the campaign are supported for Pangle placement, the returned <code>placement_type</code> will be <code>PLACEMENT_TYPE_AUTOMATIC</code> and <code>placements</code> will be <code>["PLACEMENT_TIKTOK","PLACEMENT_PANGLE"]</code>.</li></ul></td>
  </tr>
  <tr>
    <td>User comment (Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>comment_disabled</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Video download (Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>share_disabled</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Video sharing (Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>video_download_disabled</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Budget mode</td>
    <td>Any of the following types:<ul><li>Daily budget</li><li>Dynamic daily budget</li></ul></td>
    <td><code>budget_mode</code></td>
    <td>Any of the following values:<ul><li><code>BUDGET_MODE_DAY</code></li><li><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></td>
  </tr>
  <tr>
      <td>Schedule type</td>
      <td>Any of the following types:<ul><li>Run campaign continuously after the scheduled start time</li><li>Run campaign between the scheduled start time and end time</li></ul></td>
      <td><code>schedule_type</code><br><code>schedule_start_time</code><br><code>schedule_end_time</code></td>
      <td><ul><li>To run the campaign continuously after the scheduled start time, set <code>schedule_type</code> to <code>SCHEDULE_FROM_NOW</code> and specify <code>schedule_start_time</code>. Do not pass <code>schedule_end_time</code>.</li><li>To run the campaign between the scheduled start time and end time, set <code>schedule_type</code> to <code>SCHEDULE_START_END</code> and specify both <code>schedule_start_time</code> and <code>schedule_end_time</code>.</li></ul></td>
  </tr>
      <tr>
      <td  rowspan="3">Identity</td>
      <td  rowspan="3">Any of the following options:<ul>
        <li>Custom User (custom identity)</li>
        <li>Authorized User</li>
        <li>TikTok User</li>
        <li>TikTok Account User in Business Center</li>
      </ul></td>
      <td><code>identity_type</code></td>
      <td>
        Any of the following values:<ul>
          <li><code>CUSTOMIZED_USER</code></li>
          <li><code>AUTH_CODE</code></li>
          <li><code>TT_USER</code></li>
          <li><code>BC_AUTH_TT</code></li>
        </ul>
        <br><p><span style="color:darkred"><b>Note</b></span>:
        <ul><li>For non-Spark Ads identities (<code>CUSTOMIZED_USER</code>), pass values to <code>identity_type</code> and <code>identity_id</code> outside the <code>media_info</code> object.</li><li>
        For Spark Ads identities, pass values to <code>identity_type</code> and <code>identity_id</code> within the <code>media_info</code> object.</li></ul>
      </td>
    </tr>
    <tr>
      <td><code>identity_id</code></td>
      <td>
        Pass a valid value<br><br>
        To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.
      </td>
    </tr>
    <tr>
      <td><code>identity_authorized_bc_id</code></td>
      <td>
        Pass a valid value when <code>identity_type</code> is <code>BC_AUTH_TT</code>
      </td>
    </tr>
  <tr>
    <td>Destination</td>
    <td>Website URL</td>
    <td><code>landing_page_urls</code><br><code>page_list</code></td>
    <td><ul><li>Specify one website URL through <code>landing_page_urls</code>.</li><li>Do not pass <code>page_list</code>.</li></ul></td>
  </tr>
   <tr>
      <td>UTM parameters<br>(Optional)</td>
      <td>Enabled or disabled</td>
      <td><code>utm_params</code></td>
      <td>Pass valid values or not passed<br>If you set <code>landing_page_url</code> to a URL that already includes URL parameters, you can optionally pass <code>utm_params</code> at the same time to store the URL parameters used in the URL.<br>In such cases, you need to ensure that <code>utm_params</code> exactly matches the used URL parameters. The URL parameters will not be automatically appended to the <code>landing_page_url</code> upon ad delivery.
      </td>
    </tr>
  <tr>
    <td>Ad creative <br>&#183; Auto-selection of creatives from your catalog</td>
    <td>Any of the following options:<ul><li> Enabled (Recommended)<ul><li>Catalog creatives will be used to generate ads.</li><li>Multiple ad format combinations are supported. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in Smart+ Catalog Ads">Customize the ad format combinations in Smart+ Catalog Ads</a>.</li></ul></li><li>Disabled<ul><li>Catalog creatives will not be used to generate ads. The system will automatically create single video ads using your specified content.</li><li>Requirements: <ul><li> When using a non-Spark Ad identity:<ul><li>Specify 1-30 videos with cover images </li><li>Specify 1-5 ad texts</li></ul></li><li> When using a Spark Ad identity:<ul><li> 1-30 TikTok video posts</li></ul></li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Not using a catalog for creatives might restrict your ad performance potential.</li></ul></li></ul></td>
    <td><code>catalog_creative_toggle</code><br><code>video_info</code><br><code>image_info</code><br><code>music_info</code><br><code>title_list</code> <br><code>tiktok_item_id</code></td>
    <td><ul><li>To enable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>true</code> and specify a piece of music via <code>music_info</code>. For detailed configuration options and requirements, see <a href="#item-link-Customize the ad format combinations in Smart+ Catalog Ads">Customize the ad format combinations in Smart+ Catalog Ads</a><br> <br><p><span style="color:darkred"><b>Note</b></span>: <code>music_info</code> is required when <code>catalog_creative_toggle</code> is <code>true</code>.</li><li>To disable auto-selection of catalog creatives, set <code>catalog_creative_toggle</code> to <code>false</code>, and specify any of the following: <ul><li>If <code>identity_type</code> outside of the <code>media_info</code> object is <code>CUSTOMIZED_USER</code>:<ul><li>one to 30 videos (<code>video_info</code>) with cover images (<code>image_info</code>) and one to five ad texts (<code>title_list</code>).</li></ul></li><li>If <code>identity_type</code> within the <code>media_info</code> object is <code>AUTH_CODE</code>, <code>TT_USER</code> or <code>BC_AUTH_TT</code>:<ul><li>one to 30 TikTok video posts through <code>tiktok_item_id</code>. Do not pass <code>video_info</code>, <code>image_info</code>, and <code>title_list</code>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Interactive add-ons <br>&#183; Auto-selection of interactive add-ons (Optional)</td>
    <td>Any of the following options:<ul><li>Enabled (Recommended)<ul><li>The most suitable interactive add-ons will be automatically applied.</li></ul></li><li>Disabled</li></ul></td>
    <td><code>automatic_add_on_enabled</code><br><code>card_list</code></td>
    <td>If the single video or catalog video ad format is available, you can either enable (recommended) or disable auto-selection of interactive add-ons in the generated ads.<br><br><ul><li>To enable auto-selection of interactive add-ons, set <code>automatic_add_on_enabled</code> to <code>true</code>, and do not pass <code>card_list</code>. The system will automatically generate a Product Card portfolio and a Product Tiles portfolio via the returned <code>card_list</code>.</li><li> To disable auto-selection of interactive add-ons, set <code>automatic_add_on_enabled</code> to <code>false</code>, and do not pass <code>card_list</code>. The returned <code>card_list</code> will be null.</li></ul><br>To learn about Product Card and Product Tiles portfolios, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058"  target="_blank">Cards</a>.</td>
  </tr>
  <tr>
    <td>Call to action</td>
    <td>Any of the following options:<ul><li>Dynamic</li><li>Standard</li></ul></td>
    <td><code>call_to_action_id</code><br><code>call_to_action_list</code></td>
    <td><ul><li>To use dynamic call to action, pass <code>call_to_action_id</code>.</li><li>To use standard call to action, pass <code>call_to_action_list</code>.</li></ul></td>
  </tr>
   <tr>
      <td>Tracking<br>&#183; TikTok events tracking<br>&nbsp;&nbsp;&nbsp;&nbsp;&#183; App events (Optional)</td>
      <td >Enabled or disabled</td>
      <td><code>tracking_app_id</code></td>
      <td>
        Pass the App ID of the app that you want to measure or not passed<br><br>
        To obtain a list of App IDs, use <a href="https://business-api.tiktok.com/portal/docs?id=1740859313270786" target="_blank">/app/list/</a>.
      </td>
  </tr>
</tbody>
</table>

### Customize the ad format combinations in Smart+ Catalog Ads
If you enable auto-selection of catalog creatives (`catalog_creative_toggle` is `true`), the system will automatically create ads using combinations of three possible formats: catalog carousel, catalog video, and single video.

The possible ad format combinations for your Smart+ Catalog Ads are illustrated in the following table.

```xtable
| Is the catalog carousel format available? {20%}| Is the catalog video format available? {20%}| Is the single video format available? {20%}| Ad format combination for your Smart+ Catalog Ads{40%} |
|---|---|---|---|
| ❌ | ❌ | ✅ | Single video only |
| ❌ | ✅ | ❌ | Catalog video only |
| ❌ | ✅ | ✅ | Catalog video and single video |
| ✅ | ❌ | ❌ | Catalog carousel only |
| ✅ | ❌ | ✅ | Catalog carousel and single video |
| ✅ | ✅ | ✅ | Catalog video, catalog carousel, and single video |
```

To ensure certain ad formats are available in Smart+ Catalog Ads, follow the instructions in the following table.
<table>
  <thead>
  <tr>
    <th style="width:15%">Ad format</th>
    <th style="width:20%">Ad format description</th>
    <th style="width:25%">How to ensure the ad format is available</th>
    <th style="width:40%">How to configure the parameters</th>
  </tr>
  </thead>
<tbody>
  <tr>
    <td rowspan="2">catalog carousel</td>
    <td rowspan="2">Display 2–20 products in a slideshow format. This format will showcase product images of up to 20 products from your catalog at a time.</td>
    <td>The catalog needs to contain at least two images supported in Catalog Carousel Ads.</td>
    <td>Ensure that the catalog (<code>catalog_id</code>) contains at least two approved products. You can verify this by checking the value of <code>approved</code> returned from <a href="https://business-api.tiktok.com/portal/docs?id=1740492470201345" target="_blank">/catalog/overview/</a>, which should be at least two.<ul><li>Carousel image selection scope: The product images for products in a catalog (<code>catalog_id</code>) will be dynamically chosen as images in the delivering Catalog Carousel Ads. The scope of product images to choose from is determined by the specified <code>product_specific_type</code> parameter:<ul><li>If <code>product_specific_type</code> is set to <code>ALL</code>, the system will dynamically choose Carousel images from all products in the catalog.</li><li>If <code>product_specific_type</code> is set to <code>PRODUCT_SET</code>, the system will dynamically choose Carousel images from products in the specified product set (<code>product_set_id</code>).</li><li>If <code>product_specific_type</code> is set to <code>CUSTOMIZED_PRODUCTS</code>, the system will dynamically choose Carousel images from a customized product range consisting of up to 20 products (<code>product_ids</code>).</li></ul></li><li>Carousel image type:<ul><li>Use the main images of the products: By default, the main image URLs of the dynamically selected catalog products will be used as Carousel images.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Specify a piece of music supported in Catalog Carousel Ads.</td>
    <td>Pass one music ID that is valid for use in Catalog Carousel Ads via <code>music_info</code>.<br><br>To obtain a valid music ID, you can use any of the following methods:<ul><li>Filter the pieces of music for Catalog Carousel Ads under an ad account by specifying <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740053909509122"  target="_blank">/file/music/get/</a>.</li><li>Upload a piece of customized music for Catalog Carousel Ads to an ad account by using any of the following methods:<ul><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code> and pass <code>music_file</code> and <code>music_signature</code> in <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650"  target="_blank">/file/music/upload/</a>.</li><li>Specify <code>music_scene</code> as <code>CATALOG_CAROUSEL</code>, <code>upload_type</code> as <code>UPLOAD_BY_FILE_ID</code>, and pass <code>file_id</code> to <a href="https://ads.tiktok.com/marketing_api/docs?id=1740052650395650"  target="_blank">/file/music/upload/</a>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>catalog video</td>
    <td>Generate a dynamic video based on information from the product catalog</td>
    <td>The catalog needs to be bound to at least one video template or you need to configure a video URL for each catalog product. <br>The system will automatically select one of your video templates for each catalog product to generate the corresponding catalog video or use the video URL for the catalog product.<br></td>
    <td>Ensure that at least one video template is bound to your catalog (<code>catalog_id</code>) or a video URL (<code>video_url</code>) has been configured for each catalog product. <ul><li>To ensure a video URL has been configured for each catalog product, use <a href="https://business-api.tiktok.com/portal/docs?id=1740564136678402"  target="_blank">/catalog/product/get/</a> and check the returned <code>video_url</code>.<ul><li>If you want to add video URLs for your catalog products, use <a href="https://business-api.tiktok.com/portal/docs?id=1740562287852546"  target="_blank">/catalog/product/update/</a>.</li></ul></li><li>To ensure that at least one video template (video package) is bound to your catalog, use <a href="https://business-api.tiktok.com/portal/docs?id=1740574099715073"   target="_blank">/catalog/video_package/get/</a>.<ul><li>To learn about how to create video templates on TikTok Ads Manager, see <a href="https://ads.tiktok.com/help/article/how-to-create-video-packages-in-a-catalog"  target="_blank">How to create video packages in a Catalog</a>.</li><li>To retrieve video templates for your catalog via API, use <a href="https://business-api.tiktok.com/portal/docs?id=1740574099715073"  target="_blank">/catalog/video_package/get/</a>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>single video</td>
    <td>Use specified video creatives to promote your products.</td>
    <td>Specify additional videos</td>
    <td>Specify additional videos via <code>video_info</code> and cover images via <code>image_info</code>.</li></ul></td>
  </tr>
</tbody>
</table>

### Example
#### Product source as all products & Optimization goal as Click

Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "spc_type": "WEB_ALL_IN_ONE", 
    "campaign_type": "REGULAR_CAMPAIGN",
    "campaign_name": "{{campaign_name}}",
    "promotion_type": "WEBSITE", 
    "web_all_in_one_catalog_status": "OPEN",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    <span style="background-color: green;">"product_specific_type": "ALL", 
    "optimization_goal": "CLICK",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "CPC",</span>
    "location_ids": [
        "6252001"
    ],
    "exclude_age_under_eighteen":true,
    "budget_mode": "BUDGET_MODE_DAY",
    "budget": {{budget}},
    "identity_type": "CUSTOMIZED_USER",
    "identity_id": "{{identity_id}}",
    "landing_page_urls": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ],
    "catalog_creative_toggle": true,
    "media_info_list": [
        { 
            "media_info": {
                "video_info": {
                    "video_id": "{{video_id}}"
                },
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ]
            }
        },
        { 
            "media_info": {
                "music_info": {
                    "music_id": "{{music_id}}"
                }
            }
        }
    ],
    "title_list": [
        {
            "title": "{{title}}"
        }
    ],
    "automatic_add_on_enabled": true,
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{schedule_start_time}}",
    "schedule_end_time": "{{schedule_end_time}}",
    "call_to_action_list": [
        {
            "call_to_action": "LEARN_MORE"
        }
    ]
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
        "card_list": [
            {
                "card_id": "{{card_id}}"
            },
            {
                "card_id": "{{card_id}}"
            }
        ],
        "catalog_id": "{{catalog_id}}",
        "promotion_type": "WEBSITE",
        "page_list": null,
        "bid_price": null,
        "web_all_in_one_catalog_status": "OPEN",
        "title_list": [
            {
                "title": "{{title}}"
            }
        ],
        "deeplink": "",
        "is_smart_performance_campaign": true,
        "campaign_secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "app_type": null,
        "shopping_ads_fallback_type": null,
        "exclude_age_under_eighteen": true,
        "identity_type": "CUSTOMIZED_USER",
        "landing_page_urls": [
            {
                "landing_page_url": "{{landing_page_url}}",
                "video_id": null,
                "tiktok_item_id": null
             }
        ],
        "pixel_id": null,
        "campaign_id": "{{campaign_id}}",
        "skip_learning_phase": null,
        "automatic_add_on_enabled": true,
        "click_tracking_url": null,
        "identity_id": "{{identity_id}}",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "operation_status": "ENABLE",
        "objective_type": "WEB_CONVERSIONS",
        "spc_type": "WEB_ALL_IN_ONE",
        "engaged_view_attribution_window": null,
        "product_specific_type": "ALL",
        "roas_bid": null,
        "blocked_pangle_app_ids": null,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "conversion_bid_price": null,
        "billing_event": "CPC",
        "app_name": null,
        "share_disabled": false,
        "budget": {{budget}},
        "promotion_website_type": "UNSET",
        "optimization_goal": "CLICK",
        "gender": "GENDER_UNLIMITED",
        "special_industries": null,
        "carousel_image_labels": null,
        "bid_type": "BID_TYPE_NO_BID",
        "view_attribution_window": null,
        "app_promotion_type": null,
        "video_download_disabled": false,
        "impression_tracking_url": null,
        "click_attribution_window": null,
        "schedule_start_time": "{{schedule_start_time}}",
        "media_info_list": [
            {
                "media_info": {
                    "aigc_disclosure_type": null,
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "music_info": null,
                    "tiktok_item_id": null,
                    "identity_type": null,
                    "identity_id": null,
                    "video_info": {
                        "file_name": "",
                        "video_id": "{{video_id}}"
                    }
                }
            },
            {
                "media_info": {
                    "aigc_disclosure_type": null,
                    "image_info": null,
                    "music_info": {
                        "music_id": "{{music_id}}"
                    },
                    "tiktok_item_id": null,
                    "identity_type": null,
                    "identity_id": null,
                    "video_info": null
                }
            }
        ],
        "optimization_event": null,
        "adgroup_secondary_status": "ADGROUP_STATUS_AUDIT",
        "advertiser_id": "{{advertiser_id}}",
        "app_id": null,
        "app_download_url": null,
        "modify_time": "{{modify_time}}",
        "spc_audience_age": "18+",
        "campaign_type": "REGULAR_CAMPAIGN",
        "catalog_creative_toggle": true,
        "carousel_image_index": null,
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "attribution_event_count": null,
        "comment_disabled": false,
        "call_to_action_list": [
        {
            "call_to_action": "LEARN_MORE"
        }
        ],
        "deep_bid_type": null,
        "budget_mode": "BUDGET_MODE_DAY",
        "product_ids": null,
        "create_time": "{{create_time}}",
        "excluded_audience_ids": null,
        "schedule_type": "SCHEDULE_START_END",
        "location_ids": [
            "6252001"
        ],
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "is_advanced_dedicated_campaign": false,
        "schedule_end_time": "{{schedule_end_time}}",
        "call_to_action_id": null,
        "campaign_app_profile_page_state": null,
        "campaign_name": "{{campaign_name}}",
        "languages": [],
        "scheduled_budget": 0,
        "product_set_id": null,
        "deeplink_type": null,
        "product_source": "CATALOG"
}
(/code)
```

#### Product source as product set & Optimization goal as Value

Request
```xcodeblock
(code http)
curl --location --request POST  'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "spc_type": "WEB_ALL_IN_ONE", 
    "campaign_type": "REGULAR_CAMPAIGN",
    "campaign_name": "{{campaign_name}}",
    "promotion_type": "WEBSITE", 
    "web_all_in_one_catalog_status": "OPEN",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    <span style="background-color: green;">"product_specific_type": "PRODUCT_SET",
    "product_set_id": "{{product_set_id}}",
    "optimization_goal": "VALUE",
    "pixel_id": "{{pixel_id}}",
    "optimization_event": "SHOPPING",
    "bid_type": "BID_TYPE_NO_BID",
    "deep_bid_type": "VO_HIGHEST_VALUE",
    "billing_event": "OCPM",</span>
    "location_ids": [
        "6252001"
    ],
    "exclude_age_under_eighteen": true,
    "budget_mode": "BUDGET_MODE_DAY",
    "budget": {{budget}},
    "identity_type": "CUSTOMIZED_USER",
    "identity_id": "{{identity_id}}",
    "landing_page_urls": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ],
    "catalog_creative_toggle": true,
    "media_info_list": [
        { 
            "media_info": {
                "video_info": {
                    "video_id": "{{video_id}}"
                },
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ]
            }
        },
        { 
            "media_info": {
                "music_info": {
                    "music_id": "{{music_id}}"
                }
            }
        }
    ],
    "title_list": [
        {
            "title": "{{title}}"
        }
    ],
    "automatic_add_on_enabled": true,
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{start_time}}",
    "schedule_end_time": "{{_time}}",
    "call_to_action_list": [
        {
            "call_to_action": "LEARN_MORE"
        }
    ]
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
        "conversion_bid_price": null,
        "deep_bid_type": "VO_HIGHEST_VALUE",
        "roas_bid": null,
        "identity_type": "CUSTOMIZED_USER",
        "modify_time": "{{modify_time}}",
        "app_name": null,
        "location_ids": [
            "6252001"
        ],
        "video_download_disabled": false,
        "comment_disabled": false,
        "card_list": [
            {
                "card_id": "{{card_id}}"
            },
            {
                "card_id": "{{card_id}}"
            }
        ],
        "bid_price": null,
        "product_ids": null,
        "budget_mode": "BUDGET_MODE_DAY",
        "campaign_app_profile_page_state": null,
        "blocked_pangle_app_ids": null,
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "app_type": null,
        "billing_event": "OCPM",
        "product_specific_type": "PRODUCT_SET",
        "impression_tracking_url": null,
        "schedule_end_time": "{{schedule_end_time}}",
        "click_attribution_window": "SEVEN_DAYS",
        "languages": [],
        "promotion_type": "WEBSITE",
        "skip_learning_phase": true,
        "catalog_id": "{{catalog_id}}",
        "product_source": "CATALOG",
        "campaign_secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "campaign_name": "{{campaign_name}}",
        "call_to_action_id": null,
        "carousel_image_index": null,
        "bid_type": "BID_TYPE_NO_BID",
        "optimization_event": "SHOPPING",
        "landing_page_urls": [
            {
                "video_id": null,
                "landing_page_url": "{{landing_page_url}}",
                "tiktok_item_id": null
            }
        ],
        "special_industries": null,
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "operation_status": "ENABLE",
        "call_to_action_list": [
            {
                "call_to_action": "LEARN_MORE"
            }
        ],
        "campaign_type": "REGULAR_CAMPAIGN",
        "budget": {{budget}},
        "click_tracking_url": null,
        "app_id": null,
        "gender": "GENDER_UNLIMITED",
        "exclude_age_under_eighteen": true,
        "adgroup_secondary_status": "ADGROUP_STATUS_AUDIT",
        "product_set_id": "{{product_set_id}}",
        "excluded_audience_ids": null,
        "catalog_creative_toggle": true,
        "share_disabled": false,
        "is_advanced_dedicated_campaign": false,
        "web_all_in_one_catalog_status": "OPEN",
        "carousel_image_labels": null,
        "identity_id": "{{identity_id}}",
        "spc_audience_age": "18+",
        "pixel_id": "{{pixel_id}}",
        "engaged_view_attribution_window": null,
        "title_list": [
            {
                "title": "{{title}}"
            }
        ],
        "promotion_website_type": "UNSET",
        "is_smart_performance_campaign": true,
        "media_info_list": [
            {
                "media_info": {
                    "identity_id": null,
                    "video_info": {
                        "video_id": "{{video_id}}",
                        "file_name": ""
                    },
                    "aigc_disclosure_type": null,
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "tiktok_item_id": null,
                    "identity_type": null,
                    "music_info": null
                }
            },
            {
                "media_info": {
                    "identity_id": null,
                    "video_info": null,
                    "aigc_disclosure_type": null,
                    "image_info": null,
                    "tiktok_item_id": null,
                    "identity_type": null,
                    "music_info": {
                        "music_id": "{{music_id}}"
                    }
                }
            }
        ],
        "optimization_goal": "VALUE",
        "spc_type": "WEB_ALL_IN_ONE",
        "app_promotion_type": null,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "app_download_url": null,
        "deeplink_type": null,
        "page_list": null,
        "automatic_add_on_enabled": true,
        "view_attribution_window": "ONE_DAY",
        "attribution_event_count": "EVERY",
        "schedule_type": "SCHEDULE_START_END",
        "deeplink": "",
        "campaign_id": "{{campaign_id}}",
        "advertiser_id": "{{advertiser_id}}",
        "schedule_start_time": "{{schedule_start_time}}",
        "create_time": "{{create_time}}",
        "shopping_ads_fallback_type": null,
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "objective_type": "WEB_CONVERSIONS",
        "scheduled_budget": 0
}
(/code)
```
#### Product source as specific products & Optimization goal as Landing page view

Request
```xcodeblock
(code http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/campaign/spc/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: application/json' \
--data '{
    "advertiser_id": "{{advertiser_id}}",
    "objective_type": "WEB_CONVERSIONS",
    "spc_type": "WEB_ALL_IN_ONE", 
    "campaign_type": "REGULAR_CAMPAIGN",
    "campaign_name": "{{campaign_name}}",
    "promotion_type": "WEBSITE", 
    "web_all_in_one_catalog_status": "OPEN",
    "product_source": "CATALOG",
    "catalog_id": "{{catalog_id}}",
    "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
    <span style="background-color: green;">"product_specific_type": "CUSTOMIZED_PRODUCTS",
    "product_ids": ["{{product_id1}}","{{product_id2}}","{{product_id3}}","{{product_id4}}","{{product_id5}}"],
    "optimization_goal": "TRAFFIC_LANDING_PAGE_VIEW",
    "bid_type": "BID_TYPE_NO_BID",
    "billing_event": "OCPM",</span>
    "location_ids": [
        "6252001"
    ],
    "exclude_age_under_eighteen": true,
    "budget_mode": "BUDGET_MODE_DAY",
    "budget": {{budget}},
    "identity_type": "CUSTOMIZED_USER",
    "identity_id": "{{identity_id}}",
    "landing_page_urls": [
        {
            "landing_page_url": "{{landing_page_url}}"
        }
    ],
    "catalog_creative_toggle": true,
    "media_info_list": [
        { 
            "media_info": {
                "video_info": {
                    "video_id": "{{video_id}}"
                },
                "image_info": [
                    {
                        "web_uri": "{{web_uri}}"
                    }
                ]
            }
        },
        { 
            "media_info": {
                "music_info": {
                    "music_id": "{{music_id}}"
                }
            }
        }
    ],
    "title_list": [
        {
            "title": "{{title}}"
        }
    ],
    "automatic_add_on_enabled": true,
    "schedule_type": "SCHEDULE_START_END",
    "schedule_start_time": "{{start_time}}",
    "schedule_end_time": "{{end_time}}",
    "call_to_action_list": [
        {
            "call_to_action": "LEARN_MORE"
        }
    ]
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
        "dayparting": "111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111111",
        "optimization_event": "LANDING_PAGE_VIEW",
        "app_promotion_type": null,
        "page_list": null,
        "campaign_type": "REGULAR_CAMPAIGN",
        "bid_price": null,
        "landing_page_urls": [
            {
                "video_id": null,
                "tiktok_item_id": null,
                "landing_page_url": "{{landing_page_url}}"
            }
        ],
        "deeplink_type": null,
        "carousel_image_index": null,
        "card_list": [
            {
                "card_id": "{{card_id}}"
            },
            {
                "card_id": "{{card_id}}"
            }
        ],
        "advertiser_id": "{{advertiser_id}}",
        "share_disabled": false,
        "attribution_event_count": "ONCE",
        "modify_time": "{{modify_time}}",
        "deeplink": "",
        "catalog_id": "{{catalog_id}}",
        "app_type": null,
        "gender": "GENDER_UNLIMITED",
        "schedule_end_time": "{{schedule_end_time}}",
        "optimization_goal": "TRAFFIC_LANDING_PAGE_VIEW",
        "is_advanced_dedicated_campaign": false,
        "impression_tracking_url": null,
        "click_attribution_window": "SEVEN_DAYS",
        "location_ids": [
            "6252001"
        ],
        "product_source": "CATALOG",
        "app_download_url": null,
        "app_id": null,
        "adgroup_secondary_status": "ADGROUP_STATUS_AUDIT",
        "comment_disabled": false,
        "promotion_type": "WEBSITE",
        "blocked_pangle_app_ids": null,
        "catalog_creative_toggle": true,
        "conversion_bid_price": null,
        "view_attribution_window": "ONE_DAY",
        "languages": [],
        "pixel_id": null,
        "deep_bid_type": null,
        "billing_event": "OCPM",
        "scheduled_budget": 0,
        "app_name": null,
        "product_set_id": null,
        "carousel_image_labels": null,
        "product_specific_type": "CUSTOMIZED_PRODUCTS",
        "budget_mode": "BUDGET_MODE_DAY",
        "bid_type": "BID_TYPE_NO_BID",
        "video_download_disabled": false,
        "promotion_website_type": "UNSET",
        "call_to_action_list": [
            {
                "call_to_action": "LEARN_MORE"
            }
        ],
        "roas_bid": null,
        "title_list": [
            {
                "title": "{{title}}"
            }
        ],
        "web_all_in_one_catalog_status": "OPEN",
        "media_info_list": [
            {
                "media_info": {
                    "image_info": [
                        {
                            "web_uri": "{{web_uri}}"
                        }
                    ],
                    "video_info": {
                        "video_id": "{{video_id}}",
                        "file_name": ""
                    },
                    "identity_id": null,
                    "tiktok_item_id": null,
                    "identity_type": null,
                    "aigc_disclosure_type": null,
                    "music_info": null
                }
            },
            {
                "media_info": {
                    "image_info": null,
                    "video_info": null,
                    "identity_id": null,
                    "tiktok_item_id": null,
                    "identity_type": null,
                    "aigc_disclosure_type": null,
                    "music_info": {
                        "music_id": "{{music_id}}"
                    }
                }
            }
        ],
        "schedule_start_time": "{{schedule_start_time}}",
        "budget": {{budget}},
        "product_ids": [
            "{{product_id}}",
            "{{product_id}}",
            "{{product_id}}",
            "{{product_id}}",
            "{{product_id}}"
        ],
        "call_to_action_id": null,
        "identity_type": "CUSTOMIZED_USER",
        "objective_type": "WEB_CONVERSIONS",
        "excluded_audience_ids": null,
        "campaign_app_profile_page_state": null,
        "exclude_age_under_eighteen": true,
        "placement_type": "PLACEMENT_TYPE_NORMAL",
        "placements": [
            "PLACEMENT_TIKTOK"
        ],
        "campaign_name": "{{campaign_name}}",
        "operation_status": "ENABLE",
        "skip_learning_phase": true,
        "special_industries": null,
        "campaign_id": "{{campaign_id}}",
        "spc_type": "WEB_ALL_IN_ONE",
        "is_smart_performance_campaign": true,
        "catalog_authorized_bc_id": "{{catalog_authorized_bc_id}}",
        "automatic_add_on_enabled": true,
        "click_tracking_url": null,
        "schedule_type": "SCHEDULE_START_END",
        "create_time": "{{create_time}}",
        "shopping_ads_fallback_type": null,
        "campaign_secondary_status": "CAMPAIGN_STATUS_ENABLE",
        "engaged_view_attribution_window": null,
        "identity_id": "{{identity_id}}",
        "spc_audience_age": "18+"
    }
}
(/code)
```

# (<span style="color:Red">to-be-deprecated</span>) Create a Smart Performance Web Campaign

Note that the following requirements must be met. To find a complete list of parameters, see [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362).

> <span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>Smart Performance Web Campaigns will be deprecated in the next API version. To ensure a smooth API integration, we recommend that you upgrade to <a href="#item-link-Create a Smart+ Web Campaign">Smart+ Web Campaigns</a> as soon as possible. Smart+ Web Campaigns are a new AI-powered automation solution that leverages machine learning to drive business outcomes for advertisers.</li><li>Smart Performance Web ad groups cannot be used in [/split_test/create/](https://business-api.tiktok.com/portal/docs?id=1742666471475201) to create split test groups.</li></ul>

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
    <td>Website conversions</td>
    <td><code>objective_type</code></td>
    <td><code>WEB_CONVERSIONS</code></td>
  </tr>
  <tr>
    <td>Web campaign type</td>
    <td><b>Smart Performance Web Campaign</b></td>
    <td><code>spc_type</code></td>
    <td><code>UNSET</code> or not passed</td>
  </tr>
  <tr>
    <td rowspan="3">iOS 14 Dedicated Campaign</td>
    <td rowspan="3">Disabled</td>
    <td><code>campaign_type</code></td>
    <td><code>REGULAR_CAMPAIGN</code> or not passed.</td>
  </tr>
  <tr>
    <td><code>app_id</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td><code>campaign_app_profile_page_state</code></td>
    <td>Not passed</td>
  </tr>
  <tr>
    <td>Special ad categories (Optional)</td>
    <td>Enabled or disabled<br><br><p><span style="color:darkred"><b>Note</b></span>: This setting is only supported for advertisers who are registered in the US or Canada.</td>
    <td><code>special_industries</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Campaign Budget Optimization (CBO)</td>
    <td>N/A<br><br>CBO is enabled by default for Smart Performance Web Campaigns.</td>
    <td>N/A</td>
    <td>N/A<br><br><p><span style="color:darkred"><b>Note</b></span>: If you retrieve Smart Performance Web Campaigns via <a href="https://business-api.tiktok.com/portal/docs?id=1739315828649986"  target="_blank">/campaign/get/</a>, the returned <code>budget_optimize_on</code> will be <code>true</code>.</td>
  </tr>
  <tr>
    <td>Optimization location <br>(Promotion type)</td>
    <td>One of the following types:<ul><li>Website</li><li>TikTok instant Page</li></ul></td>
    <td><code>promotion_type</code><br><code>promotion_website_type</code></td>
    <td><ul><li> To specify optimization location as Website: <ul><li>Set <code>promotion_type</code> to <code>WEBSITE</code>.</li><li>Set <code>promotion_website_type</code> to <code>UNSET</code> or do not pass <code>promotion_website_type</code>.</li></ul></li><li>To specify optimization location as TikTok Instant Page:<ul><li>Set <code>promotion_type</code> to <code>WEBSITE</code>.</li><li>Set <code>promotion_website_type</code> to <code>TIKTOK_NATIVE_PAGE</code>.</li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Optimization goal</td>
    <td><ul><li>When the optimization location is Website, the supported optimization goals are Click, Conversion and Value.</li><li>When the optimization location is TikTok Instant Page, the supported optimization goal is Conversion.</li></ul></td>
    <td><code>optimization_goal</code></td>
    <td><ul><li>When <code>promotion_website_type</code> is set to <code>UNSET</code> or not passed, set <code>optimization_goal</code> to <code>CLICK</code>, <code>CONVERT</code> or <code>VALUE</code>.</li><li>When <code>promotion_website_type</code> is set to <code>TIKTOK_NATIVE_PAGE</code>, set <code>optimization_goal</code> to <code>CONVERT</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Pixel</td>
    <td><ul><li>Required when the optimization location is Website.</li><li>Not supported when the optimization location is TikTok Instant Page.</li></ul></td>
    <td><code>pixel_id</code></td>
    <td><ul><li>Required when <code>promotion_website_type</code> is set to <code>UNSET</code> or not passed.</li><li>Not supported when <code>promotion_website_type</code> is set to <code>TIKTOK_NATIVE_PAGE</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Optimization event</td>
    <td>Specified.<br><br><p><span style="color:darkred"><b>Note</b></span>: When the optimization location is TikTok Instant Page, the only supported optimization event is Click Button.</td>
    <td><code>optimization_event</code></td>
    <td>Pass a valid value. <br>To obtain the optimization events that have been configured for a Pixel, use <a href="https://business-api.tiktok.com/portal/docs?id=1740858697598978"  target="_blank">/pixel/list/</a>. <br>See <a href="https://business-api.tiktok.com/portal/docs?id=1739361474981889#item-link-Pixel%20events"  target="_blank">Conversion events - Pixel events</a> for enum values.<br><br><p><span style="color:darkred"><b>Note</b></span>: When <code>promotion_website_type</code> is set to <code>TIKTOK_NATIVE_PAGE</code>, this field can only be set to <code>BUTTON</code>.</td>
  </tr>
  <tr>
    <td>Bid Strategy</td>
    <td>Maximum Delivery </td>
    <td><ul><li><code>bid_type</code></li><li><code>deep_bid_type</code></li></ul></td>
    <td><ul><li>If <code>optimization_goal</code> is <code>CLICK</code> or <code>CONVERT</code>:<ul><li>Set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. Do not pass <code>deep_bid_type</code>.</li></ul></li><li>If <code>optimization_goal</code> is <code>VALUE</code>:<ul><li>Set <code>deep_bid_type</code> to <code>VO_MIN_ROAS</code> and set <code>bid_type</code> to <code>BID_TYPE_NO_BID</code>. </li></ul></li></ul></td>
  </tr>
  <tr>
    <td>Attribution settings</td>
    <td>Select from the supported attribution setting options for the specific scenario.</td>
    <td><ul><li><code>click_attribution_window</code></li><li><code>view_attribution_window</code></li><li><code>attribution_event_count</code></li></ul></td>
    <td>To learn about the supported attribution setting options for different scenarios, see <a href="https://business-api.tiktok.com/portal/docs?id=1777694366654465#item-link-Website%20conversions" target="_blank">Attribution window and event count - Website conversions</a>.</td>
  </tr>
  <tr>
    <td>Billing event</td>
    <td>oCPM</td>
    <td><code>billing_event</code></td>
    <td><code>OCPM</code></td>
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
    <td>Audience targeting<br>&#183; Exclude audience (Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>excluded_audience_ids</code></td>
    <td>Pass valid values or not passed<br></td>
  </tr>
  <tr>
    <td>Audience targeting<br>&#183; Gender (Optional)</td>
    <td>Supported with allowlist<br><br><p><span style="color:darkred"><b>Note</b></span>: Gender targeting in Smart Performance Campaigns is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
    <td><code>gender</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Placement</td>
    <td>Any of the following options:<ul><li><b>Automatic Placement</b></li><li><b>Select Placement</b> with TikTok or Pangle or TikTok and Pangle placement</li></ul></td>
    <td><code>placement_type</code><br><code>placements</code></td>
    <td>Any of the following:<ul><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_AUTOMATIC</code>.</li><li>Set <code>placement_type</code> to <code>PLACEMENT_TYPE_NORMAL</code>, and include at least <code>PLACEMENT_TIKTOK</code> or <code>PLACEMENT_PANGLE</code> in the value of <code>placements</code>.</li></ul></td>
  </tr>
  <tr>
    <td>User comment (Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>comment_disabled</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Video download (Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>share_disabled</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Video sharing (Optional)</td>
    <td>Enabled or disabled</td>
    <td><code>video_download_disabled</code></td>
    <td>Pass valid values or not passed</td>
  </tr>
  <tr>
    <td>Budget mode</td>
    <td>Any of the following types:<ul><li>Daily budget</li><li>Dynamic daily budget</li></ul></td>
    <td><code>budget_mode</code></td>
    <td>Any of the following values:<ul><li><code>BUDGET_MODE_DAY</code></li><li><code>BUDGET_MODE_DYNAMIC_DAILY_BUDGET</code></li></ul></td>
  </tr>
<tr>
    <td rowspan="2">Identity</td>
    <td rowspan="2">Custom User (custom identity)</td>
    <td><code>identity_type</code></td>
    <td><code>CUSTOMIZED_USER</code><br><br><p><span style="color:darkred"><b>Note</b></span>: Set the <code>identity_type</code> outside of the <code>media_info</code> object to <code>CUSTOMIZED_USER</code>.</td>
  </tr>
  <tr>
    <td><code>identity_id</code></td>
    <td>Pass a valid value<br> To get the list of identities under your ad account, use <a href="https://business-api.tiktok.com/portal/docs?id=1740218420781057" target="_blank">/identity/get/</a>.</td>
  </tr>
  <tr>
    <td>Destination</td>
    <td><ul><li>If optimization location is specified as Website, the destination can be website URL or a TikTok Instant Page.</li><li>If optimization location is specified as TikTok Instant Page, destination can only be a TikTok Instant Page.</li></ul></td>
    <td><code>landing_page_urls</code><br><code>page_list</code></td>
    <td><ul><li>If <code>promotion_website_type</code> is set to <code>UNSET</code> or not passed, you need to specify one or more website URLs through <code>landing_page_urls</code>, or specify a TikTok Instant Page through <code>page_list</code>.<ul><li>To filter TikTok Instant Pages within your ad account, call <a href="https://business-api.tiktok.com/portal/docs?id=1820826387779586"  target="_blank">/page/get/</a>. Pass <code>advertiser_id</code> and set <code>business_type</code> to <code>TIKTOK_INSTANT_PAGE</code>.</li></ul></li><li>If <code>promotion_website_type</code> is set to <code>TIKTOK_NATIVE_PAGE</code>, you need to specify a TikTok Instant Page through <code>page_list</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: Multiple landing page mode is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.</td>
  </tr>
  <tr>
    <td>Ad creative</td>
    <td><ul><li>For Custom User identity or Spark Ads PUSH with TikTok User identity, specify one to 30 videos with cover images and one to five ad texts.</li><li>For Authorized User identity, or Spark Ads PULL with TikTok User identity, specify one to 30 TikTok posts.</li></ul></td>
    <td><code>video_info</code><br><code>image_info</code><br><code>title_list</code><br><code>tiktok_item_id</code></td>
    <td><ul><li>If <code>identity_type</code> is <code>CUSTOMIZED_USER</code>, specify one to 30 videos (<code>video_info</code>) with cover images (<code>image_info</code>) and one to five ad texts (<code>title_list</code>). Do not pass <code>tiktok_item_id</code>.</li><li> If <code>identity_type</code> is <code>TT_USER</code>, you can create the campaign through Spark Ads PULL or Spark Ads PUSH: <ul><li>Spark Ads PUSH: Specify one to 30 videos (<code>video_info</code>) with cover images (<code>image_info</code>) and one to five ad texts (<code>title_list</code>). Do not pass <code>tiktok_item_id</code>.</li><li>Spark Ads PULL: Specify one to 30 TikTok posts through <code>tiktok_item_id</code>. Do not pass <code>video_info</code>, <code>image_info</code>, and <code>title_list</code>.</li></ul>To learn more about Spark Ads, see <a href="https://business-api.tiktok.com/portal/docs?id=1739470744631298" target="_blank">Create Spark Ads</a>.</li><li>If <code>identity_type</code> is <code>AUTH_CODE</code>, specify one to 30 TikTok posts through <code>tiktok_item_id</code>. Do not pass <code>video_info</code>, <code>image_info</code>, and <code>title_list</code>.</li></ul></td>
  </tr>
  <tr>
    <td>Interactive add-ons (Optional)</td>
    <td>Disabled or enabled with any of the following types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul></td>
    <td><code>card_list</code></td>
    <td>Not passed or specify the ID of one of the following creative portfolio types:<ul><li>Countdown Sticker</li><li>Gift Code Sticker</li><li>Display Card</li></ul><br>To create a creative portfolio, use <a href="https://business-api.tiktok.com/portal/docs?id=1739091950439426" target="_blank">/creative/portfolio/create/</a>.<ul><li>To learn about how to get the ID of a Display Card portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019652141058#item-link-Display%20Card"  target="_blank">Cards</a>.</li><li>To learn about how to get the ID of a Countdown Sticker or Gift Code Sticker portfolio, see <a href="https://business-api.tiktok.com/portal/docs?id=1749019667506177"  target="_blank">Stickers</a>.</li></ul></td>
  </tr>
  <tr>
    <td>Call to action</td>
    <td>Any of the following options:<ul><li>Dynamic</li><li>Standard</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: When you use a Spark Ads identity, you can only use dynamic call to action.</td>
    <td><code>call_to_action_id</code><br><code>call_to_action_list</code></td>
    <td><ul><li>To use dynamic call to action, pass <code>call_to_action_id</code>.</li><li>To use standard call to action, pass <code>call_to_action_list</code>.</li></ul><br><p><span style="color:darkred"><b>Note</b></span>: If you set <code>identity_type</code> within the <code>media_info</code> object to <code>TT_USER</code> or <code>AUTH_CODE</code>, <code>call_to_action_id</code> is required.</td>
  </tr>
</tbody>
</table>


