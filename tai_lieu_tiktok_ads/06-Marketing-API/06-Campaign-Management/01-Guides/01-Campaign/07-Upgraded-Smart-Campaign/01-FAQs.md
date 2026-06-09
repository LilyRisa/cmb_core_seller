---
title: "FAQs"
doc_id: 1854104036724929
path: "Marketing API / Campaign Management / Guides / Campaign / Upgraded Smart+ Campaign / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

# Access and eligibility

## Q1: How can I determine if my ad account has access to Upgrade Smart+ Campaigns (the Upgraded Smart+ experience)?
A: To check your access, [log in to your TikTok Ads Manager account](https://ads.tiktok.com/i18n/login) and navigate to the campaign creation page. 

If you see a notification stating that Manual and Smart+ campaigns have been consolidated into a single workflow when App promotion, Lead generation, or Sales is selected as the advertising objective, then your ad account has access to Upgrade Smart+ Campaigns.

Conversely, if the notification is absent and you have the options for **Manual campaign** and **Smart+ campaign** or just **Manual campaign** available for selection, it indicates that your ad account does not have access to Upgrade Smart+ Campaigns.

The following table illustrates the different Ads Manager UIs for different scenarios.

<table>
  <thead>
    <tr>
      <th style="width:30%">Scenario</th>
      <th style="width:20%">Advertising Objective</th>
      <th style="width:50%">UI screenshot</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td rowspan="3">Creating Upgrade Smart+ Campaigns</td>
      <td>App promotion</td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/9a0ec71585dc9831eaa7a4791be13e15" alt="Upgrade Smart+ App Promotion Campaign" width="80%" style="color:#808080" /></td>
    </tr>
    <tr>
      <td>Lead generation</td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/13a2bea715051d135d6ef409bf09623d" alt="Upgrade Smart+ Lead Generation Campaign" width="80%" style="color:#808080" /></td>
    </tr>
    <tr>
      <td>Sales</td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/986c370ecfbcf35462a89894dc85b053" alt="Upgrade Smart+ Sales Campaign" width="80%" style="color:#808080" /></td>
    </tr>
    <tr>
      <td rowspan="3">Creating Smart+ Campaigns</td>
      <td>App promotion</td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6ed91e6c217d68e0dc6789123d515ff3" alt="Smart+ App Promotion Campaign" width="80%" style="color:#808080" /></td>
    </tr>
    <tr>
      <td>Lead generation</td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/d86059090dd196e566ce4b557ad42cc8" alt="Smart+ Lead Generation Campaign" width="80%" style="color:#808080" /></td>
    </tr>
    <tr>
      <td>Sales</td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/7eff932da9bdb179576f26d278fcad81" alt="Smart+ Sales Campaign" width="80%" style="color:#808080" /></td>
    </tr>
    <tr>
      <td rowspan="3">Creating Manual Campaigns</td>
      <td>App promotion</td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/333b6ee7acf25399df63ebfc6635d743" alt="Manual App Promotion Campaign" width="80%" style="color:#808080" /></td>
    </tr>
    <tr>
      <td>Lead generation</td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/653f6cf1bde3e598ec4b0b998c6e41af" alt="Manual Lead Generation Campaign" width="80%" style="color:#808080" /></td>
    </tr>
    <tr>
      <td>Sales</td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/f3b23eebe3e33e7a49ba555343c7f3ee" alt="Manual Sales Campaign" width="80%" style="color:#808080" />
</td>
    </tr>
  </tbody>
</table>

# Differentiation between campaign types

## Q1: How do I differentiate between Upgrade Smart+, Smart+, and Manual Campaigns?
A: You can retrieve all three campaign types using [/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1739315828649986) and identify each campaign type through the `campaign_automation_type` response parameter.
The following table outlines the differences:

<table>
  <thead>
    <tr>
      <th style="width:20%">Campaign Type</th>
      <th style="width:25%"><code>campaign_automation_type</code> Value</th>
      <th style="width:35%">Example Campaign on Ads Manager UI</th>
      <th style="width:20%">Sample Response from <code>/campaign/get/</code></th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Upgrade Smart+ Campaign</td>
      <td><code>UPGRADED_SMART_PLUS</code></td>
      <td>Upgrade Smart+ Campaigns display a Smart+ icon and lack the <b>Smart+ Campaign</b> setting when edited.</td>
      <td>See <a href="#item-link-Sample response for Upgrade Smart+ Campaigns from /campaign/get/">sample response below</a></td>
    </tr>
    <tr>
      <td>Smart+ Campaign</td>
      <td><code>SMART_PLUS</code></td>
      <td>Smart+ Campaigns also display a Smart+ icon, but the <b>Smart+ Campaign</b> setting is toggled on when edited.</td>
      <td>See <a href="#item-link-Sample response for Smart+ Campaigns from /campaign/get/">sample response below</a></td>
    </tr>
    <tr>
      <td>Manual Campaign</td>
      <td><code>MANUAL</code></td>
      <td>Manual Campaigns do not display a Smart+ icon, and the <b>Smart+ Campaign</b> setting is toggled off when edited.</td>
      <td>See <a href="#item-link-Sample response for Manual Campaigns from /campaign/get/">sample response below</a></td>
    </tr>
  </tbody>
</table>

### Sample response for Upgrade Smart+ Campaigns from `/campaign/get/`
```xcodeblock
(code)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "sales_destination": null,
                "advertiser_id": "{{advertiser_id}}",
                "modify_time": "{{modify_time}}",
                "is_advanced_dedicated_campaign": false,
                "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
                "virtual_objective_type": null,
                <span style="background-color: green;">"campaign_automation_type": "UPGRADED_SMART_PLUS",</span>
                "objective_type": "LEAD_GENERATION",
                "budget": {{budget}},
                "catalog_enabled": false,
                "secondary_status": "CAMPAIGN_STATUS_ENABLE",
                "rta_bid_enabled": false,
                "rta_id": null,
                "roas_bid": 0.0,
                "rta_product_selection_enabled": false,
                "campaign_name": "{{campaign_name}}",
                "special_industries": [],
                "campaign_id": "{{campaign_id}}",
                "app_promotion_type": "UNSET",
                <span style="background-color: green;">"is_smart_performance_campaign": false,</span>
                "budget_optimize_on": true,
                "create_time": "{{create_time}}",
                "disable_skan_campaign": null,
                "is_search_campaign": false,
                "operation_status": "ENABLE",
                "deep_bid_type": null,
                "is_new_structure": true,
                "campaign_type": "REGULAR_CAMPAIGN",
                "objective": "LANDING_PAGE"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_page": 1,
            "total_number": 1
        }
    }
}
(/code)
```
### Sample response for Smart+ Campaigns from `/campaign/get/`
```xcodeblock
(code)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "campaign_name": "{{campaign_name}}",
                <span style="background-color: green;">"is_smart_performance_campaign": true,</span>
                "create_time": "{{create_time}}",
                "rta_product_selection_enabled": false,
                "catalog_enabled": false,
                "roas_bid": 0,
                "campaign_id": "{{campaign_id}}",
                "is_new_structure": true,
                "rta_bid_enabled": false,
                "modify_time": "{{modify_time}}",
                "operation_status": "ENABLE",
                "virtual_objective_type": null,
                "budget": {{budget}},
                "bid_type": "BID_TYPE_NO_BID",
                "campaign_type": "REGULAR_CAMPAIGN",
                "is_advanced_dedicated_campaign": false,
                "objective": "LANDING_PAGE",
                <span style="background-color: green;">"campaign_automation_type": "SMART_PLUS",</span>
                "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
                "budget_optimize_on": true,
                "rta_id": null,
                "is_search_campaign": false,
                "objective_type": "WEB_CONVERSIONS",
                "advertiser_id": "{{advertiser_id}}",
                "deep_bid_type": null,
                "disable_skan_campaign": null,
                "optimization_goal": "CONVERT",
                "secondary_status": "CAMPAIGN_STATUS_ENABLE",
                "sales_destination": null
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_page": 1,
            "total_number": 1
        }
    }
}
(/code)
```
### Sample response for Manual Campaigns from `/campaign/get/`
```xcodeblock
(code)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "campaign_type": "REGULAR_CAMPAIGN",
                "budget_optimize_on": true,
                "campaign_name": "{{campaign_name}}",
                "sales_destination": null,
                <span style="background-color: green;">"campaign_automation_type": "MANUAL",</span>
                "campaign_id": "{{campaign_id}}",
                "catalog_enabled": false,
                "rta_id": null,
                "secondary_status": "CAMPAIGN_STATUS_ENABLE",
                "objective": "LANDING_PAGE",
                "modify_time": "{{modify_time}}",
                "special_industries": [],
                "operation_status": "ENABLE",
                "create_time": "{{create_time}}",
                "advertiser_id": "{{advertiser_id}}",
                "rta_product_selection_enabled": false,
                "virtual_objective_type": null,
                "budget_mode": "BUDGET_MODE_DYNAMIC_DAILY_BUDGET",
                "objective_type": "WEB_CONVERSIONS",
                "is_advanced_dedicated_campaign": false,
                "deep_bid_type": null,
                "rta_bid_enabled": false,
                <span style="background-color: green;">"is_smart_performance_campaign": false,</span>
                "is_new_structure": true,
                "is_search_campaign": false,
                "disable_skan_campaign": null,
                "budget": {{budget}},
                "roas_bid": 0
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_page": 1,
            "total_number": 1
        }
    }
}
(/code)
```

The following screenshot illustrates how these three types of campaigns appear in the Campaign List within Ads Manager:

<img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1050e2b02b25f58978d9c3cfac12074a" alt="three types of campaigns" width="60%" style="color:#808080" />

# Data retrieval and API issues
## Q1: Why is the `ad_name` returned from `/ad/get/` for my Upgraded Smart+ Ad matching the creative name?

A: This discrepancy may occur because you created Upgraded Smart+ Ads but did not apply the `ad_ids_v2` filter while using [/ad/get/](https://business-api.tiktok.com/portal/docs?id=1735735588640770). Without this filter, you're receiving creative-level data, leading to the `ad_name` returned from `/ad/get/` reflecting the creative name rather than the ad name.

To fetch ad-level data specific to Upgraded Smart+ Ads, you need to include the IDs of these ads in the filter. By doing so, the `ad_name` returned will accurately represent the ad name. Alternatively, consider using the [/smart_plus/ad/get/](https://business-api.tiktok.com/portal/docs?id=1843317378982914) endpoint for detailed ad-level data retrieval.

The following table summarizes the ad or creative data available for Upgraded Smart+ Ads through various endpoints:


<table>
  <thead>
    <tr>
      <th style="width:12%">Data Level</th>
      <th style="width:26%">The Endpoint to Use</th>
      <th style="width:23%">UI Screenshot</th>
      <th style="width:24%">Configuration Requirement</th>
      <th style="width:15%">Code Example</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td rowspan="3">Ad level</td>
      <td>Option 1: <a href="https://business-api.tiktok.com/portal/docs?id=1735735588640770" target="_blank">/ad/get/</a></td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/bb1b414eb396b101d086216276926b50" alt="Ad level data through /ad/get/" width="80%" style="color:#808080" /></td>
      <td>Use the <code>ad_ids_v2</code> filter with IDs of Upgraded Smart+ Ads.</td>
      <td>See <a href="#item-link-Example for retrieving ad level data through /ad/get/">code examples below</a></td>
    </tr>
    <tr>
      <td>Option 2: <a href="https://business-api.tiktok.com/portal/docs?id=1843317378982914" target="_blank">/smart_plus/ad/get/</a></td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/bb1b414eb396b101d086216276926b50" alt="Ad level data through /smart_plus/ad/get/" width="80%" style="color:#808080" /></td>
      <td>N/A</td>
      <td>See <a href="#item-link-Example for retrieving ad level data through /smart_plus/ad/get/">code examples below</a></td>
    </tr>
    <tr>
      <td>Option 3: <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> or <a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a></td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/eefb4a275dff6cfc9254227414a41ec1" alt="Ad level data through /report/integrated/get/ or /report/task/create/" width="80%" style="color:#808080" /></td>
      <td>Set <code>data_level</code> to <code>AUCTION_AD</code> and include <code>ad_id_v2</code> in <code>dimensions</code>. <br>Optionally, you can specify the IDs of certain Upgraded Smart+ Ads in the filter field <code>ad_id_v2</code> to filter the reporting data for these ads.</td>
      <td>See <a href="#item-link-Example for retrieving ad level data through /report/integrated/get/">code examples below</a></td>
    </tr>
    <tr>
      <td rowspan="3">Creative level</td>
      <td>Option 1: <a href="https://business-api.tiktok.com/portal/docs?id=1735735588640770" target="_blank">/ad/get/</a></td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/17e8221f81b7a57b7c53995686219f93" alt="Creative level data through /ad/get/" width="80%" style="color:#808080" /></td>
      <td>Do not use the <code>ad_ids_v2</code> filter. <br>Optionally, you can specify the IDs of creatives within an Upgraded Smart+ Ad in the filter field <code>ad_ids</code> to filter the reporting data for these creatives.</td>
      <td>See <a href="#item-link-Example for retrieving creative level data through /ad/get/">code examples below</a></td>
    </tr>
    <tr>
      <td>Option 2: <a href="https://business-api.tiktok.com/portal/docs?id=1843317489576961" target="_blank">/smart_plus/material_report/overview/</a> or <a href="https://business-api.tiktok.com/portal/docs?id=1843317510389761" target="_blank">/smart_plus/material_report/breakdown/</a></td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/df6eaf628adb5e7452c0d263432b15ba" alt="Creative level data through /smart_plus/material_report/overview/ or /smart_plus/material_report/breakdown/ " width="80%" style="color:#808080" /></td>
      <td>N/A</td>
      <td>See <a href="#item-link-Example for retrieving creative level data through /smart_plus/material_report/overview/">code examples below</a></td>
    </tr>
    <tr>
      <td>Option 3: <a href="https://business-api.tiktok.com/portal/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> or <a href="https://business-api.tiktok.com/portal/docs?id=1740302766489602" target="_blank">/report/task/create/</a></td>
      <td><img src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e12f95501d11218c1350dd2e24b2f78e" alt="Creative level data through /report/integrated/get/  or /report/task/create/" width="80%" style="color:#808080" /></td>
      <td>Set <code>data_level</code> to <code>AUCTION_AD</code> and include <code>ad_id</code> in <code>dimensions</code>. <br>Optionally, you can specify the ID of a creative within an Upgraded Smart+ Ad in the filter field <code>ad_id</code> to filter the reporting data for the creative.</td>
      <td>See <a href="#item-link-Example for retrieving creative level data through /report/integrated/get/">code examples below</a></td>
    </tr>
  </tbody>
</table>


### Example for retrieving ad level data through `/ad/get/`

Request

```xcodeblock
(code)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/ad/get/?advertiser_id={{advertiser_id}}<span style="background-color: green;">&filtering={"ad_ids_v2":["{{ad_id_v2}}"]}</span>' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response

```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "campaign_id": "{{campaign_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "campaign_name": "{{campaign_name}}",
                "ad_name": "{{ad_name}}",
                "ad_id_v2": "{{ad_id_v2}}",
                "advertiser_id": "{{advertiser_id}}",
                "campaign_automation_type": "UPGRADED_SMART_PLUS",
                "adgroup_name": "{{adgroup_name}}"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 10,
            "total_page": 1,
            "total_number": 1
        }
    }
}
(/code)
```

### Example for retrieving ad level data through `/smart_plus/ad/get/`

Request

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/ad/get/?advertiser_id={{advertiser_id}}&page=1&page_size=100&filtering={"smart_plus_ad_ids":["{{smart_plus_ad_id}}"]}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response

```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "ad_configuration": {
                    "call_to_action_id": "{{call_to_action_id}}",
                    "catalog_creative_toggle": false,
                    "fallback_type": "UNSET"
                },
                "ad_name": "{{ad_name}}",
                "ad_text_list": [],
                "adgroup_id": "{{adgroup_id}}",
                "adgroup_name": "{{adgroup_name}}",
                "advertiser_id": "{{advertiser_id}}",
                "campaign_id": "{{campaign_id}}",
                "campaign_name": "{{campaign_name}}",
                "create_time": "{{create_time}}",
                "creative_list": [
                    {
                        "ad_material_id": "{{ad_material_id}}",
                        "creative_info": {
                            "ad_format": "CAROUSEL_ADS",
                            "identity_id": "{{identity_id}}",
                            "identity_type": "AUTH_CODE",
                            "tiktok_item_id": "{{tiktok_item_id}}"
                        },
                        "material_operation_status": "ENABLE"
                    }
                ],
                "deeplink_list": [],
                "landing_page_url_list": [
                    {
                        "landing_page_url": "{{landing_page_url}}"
                    }
                ],
                "modify_time": "{{modify_time}}",
                "operation_status": "ENABLE",
                "secondary_status": "AD_STATUS_CAMPAIGN_DISABLE",
                "smart_plus_ad_id": "{{smart_plus_ad_id}}"
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 100,
            "total_number": 1,
            "total_page": 1
        }
    }
}
(/code)
```

### Example for retrieving ad level data through `/report/integrated/get/`

Request

```xcodeblock
(code)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/?advertiser_id={{advertiser_id}}&service_type=AUCTION&report_type=BASIC<span style="background-color: green;">&data_level=AUCTION_AD&dimensions=["ad_id_v2"]</span>&metrics=["spend","ad_id_v2","ad_name"]&filtering=[{"field_name": "ad_ids_v2","filter_type": "IN","filter_value": "[\"{{ad_id_v2}}\"]"}]&start_date={{start_date}}&end_date={{end_date}}&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response

```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "page": 1,
            "total_page": 1,
            "page_size": 1,
            "total_number": 1
        },
        "list": [
            {
                "dimensions": {
                    "ad_id_v2": "{{ad_id_v2}}"
                },
                "metrics": {
                    "spend": "{{spend}}",
                    "ad_id_v2": "{{ad_id_v2}}",
                    "ad_name": "{{ad_name}}"
                }
            }
        ]
    }
}
(/code)
```

### Example for retrieving creative level data through `/ad/get/`

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/ad/get/?advertiser_id={{advertiser_id}}&filtering={"ad_ids":["{{ad_id}}"]}' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response

```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "video_id": "{{video_id}}",
                "advertiser_id": "{{advertiser_id}}",
                "campaign_name": "{{campaign_name}}",
                "adgroup_name": "{{adgroup_name}}",
                "campaign_id": "{{campaign_id}}",
                "adgroup_id": "{{adgroup_id}}",
                "modify_time": "{{modify_time}}",
                "identity_id": "{{identity_id}}",
                "ad_id": "{{ad_id}}",
                "secondary_status": "AD_STATUS_CAMPAIGN_DISABLE",
                "identity_type": "CUSTOMIZED_USER",
                "campaign_automation_type": "UPGRADED_SMART_PLUS_CREATIVE",
                "ad_text": "",
                "smart_plus_ad_id": "{{smart_plus_ad_id}}",
                "operation_status": "ENABLE",
                "ad_name": "{{ad_name}}",
                "create_time": "{{create_time}}",
                "image_ids": [
                    "{{image_id}}"
                ]
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 10,
            "total_page": 1,
            "total_number": 1
        }
    }
}
(/code)
```

### Example for retrieving creative level data through `/smart_plus/material_report/overview/`

Request

```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/smart_plus/material_report/overview/?advertiser_id={{advertiser_id}}&dimensions=["smart_plus_ad_id","main_material_id"]&metrics=["spend"]&filtering={"smart_plus_ad_ids": ["{{smart_plus_ad_id}}"]}&start_date={{start_date}}&end_date={{end_date}}&page=1&page_size=100' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response

```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "main_material_id": "{{main_material_id}}",
                    "main_material_type": "VIDEO_SPARK_ADS",
                    "smart_plus_ad_id": "{{smart_plus_ad_id}}"
                },
                "metrics": {
                    "main_material_name": "{{main_material_name}}",
                    "spend": "0.00"
                }
            },
            {
                "dimensions": {
                    "main_material_id": "{{main_material_id}}",
                    "main_material_type": "CAROUSEL_SPARK_ADS",
                    "smart_plus_ad_id": "{{smart_plus_ad_id}}"
                },
                "metrics": {
                    "main_material_name": "{{main_material_name}}",
                    "spend": "0.00"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 100,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```

### Example for retrieving creative level data through `/report/integrated/get/`

Request

```xcodeblock
(code)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/?advertiser_id={{advertiser_id}}&service_type=AUCTION&report_type=BASIC<span style="background-color: green;">&data_level=AUCTION_AD&dimensions=["ad_id"]</span>&metrics=["spend","ad_id","ad_name"]&filtering=[{"field_name":"ad_ids","filter_type":"IN","filter_value":"[\"{{ad_id}}\"]"}]&start_date={{start_date}}&end_date={{end_date}}&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response

```xcodeblock
(code curl http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "ad_id": "{{ad_id}}"
                },
                "metrics": {
                    "spend": "{{spend}}",
                    "ad_id": "{{ad_id}}",
                    "ad_name": "{{ad_name}}"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "total_number": 1,
            "page_size": 1,
            "total_page": 1
        }
    }
}
(/code)
```



## Q2: I attempted to update a campaign using both `/campaign/update/` and `/campaign/spc/update/`, but both endpoints returned the error: `This API does not support Upgraded Smart Plus ads.` What should I do?

A: This issue may arise if you are trying to update an Upgraded Smart+ Campaign. To properly update such a campaign, you should use the endpoints [/smart_plus/campaign/update/](https://business-api.tiktok.com/portal/docs?id=1843312876001282) or [/smart_plus/campaign/status/update/](https://business-api.tiktok.com/portal/docs?id=1843312888885314). These endpoints are specifically designed to handle updates for Upgraded Smart+ Campaigns.

