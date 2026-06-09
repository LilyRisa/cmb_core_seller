---
title: "Metrics in GMV Max Campaign reports"
doc_id: 1824722485971009
path: "API Reference / GMV Max / Run a GMV Max Campaign report / Metrics in GMV Max Campaign reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Metrics in GMV Max Campaign reports

This article summarizes the supported metric types for GMV Max Campaign reports.

# GMV Max Campaign metrics overview
The following tables outlines all available attribute and delivery metrics.

To learn about the detailed description of each metric along with available dimension groupings and filters for different metric levels across all GMV Max Campaigns, for Product GMV Max Campaigns, or for LIVE GMV Max Campaigns, see [For all GMV Max Campaigns](https://business-api.tiktok.com/portal/docs/metrics-in-gmv-max-campaign-reports/v1.3#item-link-For%20all%20GMV%20Max%20Campaigns), [For Product GMV Max Campaigns](https://business-api.tiktok.com/portal/docs/metrics-in-gmv-max-campaign-reports/v1.3#item-link-For%20Product%20GMV%20Max%20Campaigns), and [For LIVE GMV Max Campaigns](https://business-api.tiktok.com/portal/docs/metrics-in-gmv-max-campaign-reports/v1.3#item-link-For%20LIVE%20GMV%20Max%20Campaigns).


## Attribute metrics
Attribute metrics represent the essential characteristics of your campaigns, products, creatives, livestreams, and durations. Examples include campaign ID, campaign name, and creative name.

> <span style="color:DodgerBlue">**Note**</span><br>
> You can retrieve attribute metrics in any of the following scenarios: 
> - When querying data at the campaign level
> - When querying data at the product, livestream, or duration level while specifying a single ID in the `campaign_ids` , `item_group_ids`， or `room_ids` filter and including only one ID dimension in the `dimensions` parameter
> 
> However, you cannot retrieve attribute metrics in any of the following scenarios:
> - When querying data at the product, creative, livestream, or duration level while specifying multiple IDs in the `campaign_ids` , `item_group_ids`， or `room_ids` filter
> - When querying data at the product, creative, livestream, or duration level while including multiple ID dimensions in the `dimensions` parameter

<table><thead>
  <tr>
    <th style="width:40%">Supported GMV Max Campaign type and metric level</th>
    <th style="width:30%">Metric field</th>
    <th style="width:30%">Description</th>
  </tr></thead>
<tbody>
  <tr>
    <td rowspan="9">Product GMV Max Campaign, Campaign-level<br></td>
    <td>campaign_id</td>
    <td>Campaign ID</td>
  </tr>
  <tr>
    <td>operation_status</td>
    <td>Campaign on/off status</td>
  </tr>
  <tr>
    <td>campaign_name</td>
    <td>Campaign name</td>
  </tr>
  <tr>
    <td>schedule_type</td>
    <td>Schedule type</td>
  </tr>
  <tr>
    <td>schedule_start_time</td>
    <td>Schedule start time</td>
  </tr>
  <tr>
    <td>schedule_end_time</td>
    <td>Schedule end time</td>
  </tr>
  <tr>
    <td>target_roi_budget</td>
    <td>Target ROI budget</td>
  </tr>
  <tr>
    <td>bid_type</td>
    <td>Optimization mode</td>
  </tr>
  <tr>
    <td>max_delivery_budget</td>
    <td>Max delivery budget</td>
  </tr>
  <tr>
    <td rowspan="4">Product GMV Max Campaign, Product-level</td>
    <td>product_name</td>
    <td>Product name</td>
  </tr>
  <tr>
    <td>item_group_id</td>
    <td>Product SPU ID</td>
  </tr>
  <tr>
    <td>product_image_url</td>
    <td>Product image URL</td>
  </tr>
  <tr>
    <td>bid_type</td>
    <td>Optimization mode</td>
  </tr>
  <tr>
    <td rowspan="6">Product GMV Max Campaign, Creative-level</td>
    <td>title </td>
    <td>Creative name</td>
  </tr>
  <tr>
    <td>item_id</td>
    <td>TikTok post ID</td>
  </tr>
  <tr>
    <td>tt_account_name </td>
    <td>TikTok account name</td>
  </tr>
  <tr>
    <td>tt_account_profile_image_url </td>
    <td>TikTok account profile image URL</td>
  </tr>
  <tr>
    <td>tt_account_authorization_type</td>
    <td>Authorization type</td>
  </tr>
  <tr>
    <td>shop_content_type</td>
    <td>Shop content type</td>
  </tr>
  <tr>
    <td>Product GMV Max Campaign, Duration-level</td>
    <td>bid_type</td>
    <td>Optimization mode</td>
  </tr>
  <tr>
    <td rowspan="12">LIVE GMV Max Campaign, Campaign-level</td>
    <td>campaign_id</td>
    <td>Campaign ID</td>
  </tr>
  <tr>
    <td>operation_status</td>
    <td>Campaign on/off status</td>
  </tr>
  <tr>
    <td>campaign_name</td>
    <td>Campaign name</td>
  </tr>
  <tr>
    <td>tt_account_name</td>
    <td>TikTok account name</td>
  </tr>
  <tr>
    <td>tt_account_profile_image_url</td>
    <td>TikTok account profile image URL</td>
  </tr>
  <tr>
    <td>identity_id</td>
    <td>Identity ID (TikTok account ID)</td>
  </tr>
  <tr>
    <td>bid_type</td>
    <td>Optimization mode</td>
  </tr>
  <tr>
    <td>schedule_type</td>
    <td>Schedule type</td>
  </tr>
  <tr>
    <td>schedule_start_time</td>
    <td>Schedule start time</td>
  </tr>
  <tr>
    <td>schedule_end_time</td>
    <td>Schedule end time</td>
  </tr>
  <tr>
    <td>target_roi_budget</td>
    <td>Target ROI budget</td>
  </tr>
  <tr>
    <td>max_delivery_budget</td>
    <td>Max delivery budget</td>
  </tr>
  <tr>
    <td rowspan="4">LIVE GMV Max Campaign, Livestream-level</td>
    <td>live_name</td>
    <td>LIVE name</td>
  </tr>
  <tr>
    <td>live_status</td>
    <td>Livestream status</td>
  </tr>
  <tr>
    <td>live_launched_time</td>
    <td>Livestream launched time</td>
  </tr>
  <tr>
    <td>live_duration</td>
    <td>Livestream duration</td>
  </tr>
  <tr>
    <td>LIVE GMV Max Campaign, Duration-level</td>
    <td>bid_type</td>
    <td>Optimization mode</td>
  </tr>
</tbody>
</table>

## Delivery metrics
Delivery metrics provide fundamental insights into the performance of your campaigns, products, creatives, livestreams and durations, such as Cost and Gross Revenue.
<table><thead>
  <tr>
    <th style="width:40%">Supported GMV Max Campaign type and metric level</th>
    <th style="width:30%">Metric field</th>
    <th style="width:30%">Description</th>
  </tr></thead>
<tbody>
  <tr>
    <td rowspan="6">All GMV Max Campaigns, Ad account-level</td>
    <td>cost</td>
    <td>Cost</td>
  </tr>
  <tr>
    <td>orders</td>
    <td>Orders (SKU)</td>
  </tr>
  <tr>
    <td>cost_per_order</td>
    <td>Cost per order</td>
  </tr>
  <tr>
    <td>gross_revenue</td>
    <td>Gross revenue</td>
  </tr>
  <tr>
    <td>roi</td>
    <td>ROI</td>
  </tr>
  <tr>
    <td>net_cost</td>
    <td>Net cost</td>
  </tr>
  <tr>
    <td rowspan="7">Product GMV Max Campaign, Campaign-level</td>
    <td>roas_bid</td>
    <td>Target ROI</td>
  </tr>
  <tr>
    <td>cost</td>
    <td>Cost</td>
  </tr>
  <tr>
    <td>net_cost</td>
    <td>Net cost</td>
  </tr>
  <tr>
    <td>orders</td>
    <td>Orders (SKU)</td>
  </tr>
  <tr>
    <td>cost_per_order</td>
    <td>Cost per order</td>
  </tr>
  <tr>
    <td>gross_revenue</td>
    <td>Gross revenue</td>
  </tr>
  <tr>
    <td>roi</td>
    <td>ROI</td>
  </tr>
  <tr>
    <td rowspan="3">Product GMV Max Campaign, Product-level</td>
    <td>product_status</td>
    <td>Product status</td>
  </tr>
  <tr>
    <td>orders</td>
    <td>Orders (SKU)</td>
  </tr>
  <tr>
    <td>gross_revenue</td>
    <td>Gross revenue</td>
  </tr>
  <tr>
    <td rowspan="14">Product GMV Max Campaign, Creative-level</td>
    <td>creative_delivery_status</td>
    <td>Status</td>
  </tr>
  <tr>
    <td>orders</td>
    <td>Orders (SKU)</td>
  </tr>
  <tr>
    <td>gross_revenue</td>
    <td>Gross revenue</td>
  </tr>
  <tr>
    <td>product_impressions</td>
    <td>Product ad impressions</td>
  </tr>
  <tr>
    <td>product_clicks</td>
    <td>Product ad clicks</td>
  </tr>
  <tr>
    <td>product_click_rate</td>
    <td>Product ad click rate</td>
  </tr>
  <tr>
    <td>ad_click_rate</td>
    <td>Ad click rate</td>
  </tr>
  <tr>
    <td>ad_conversion_rate</td>
    <td>Ad conversion rate</td>
  </tr>
  <tr>
    <td>ad_video_view_rate_2s</td>
    <td>2-second ad video view rate</td>
  </tr>
  <tr>
    <td>ad_video_view_rate_6s</td>
    <td>6-second ad video view rate</td>
  </tr>
  <tr>
    <td>ad_video_view_rate_p25</td>
    <td>25% ad video view rate</td>
  </tr>
  <tr>
    <td>ad_video_view_rate_p50</td>
    <td>50% ad video view rate</td>
  </tr>
  <tr>
    <td>ad_video_view_rate_p75</td>
    <td>75% ad video view rate</td>
  </tr>
  <tr>
    <td>ad_video_view_rate_p100</td>
    <td>100% ad video view rate</td>
  </tr>
  <tr>
    <td rowspan="6">Product GMV Max Campaign, Duration-level</td>
    <td>cost</td>
    <td>Cost</td>
  </tr>
  <tr>
    <td>orders</td>
    <td>Orders (SKU)</td>
  </tr>
  <tr>
    <td>cost_per_order</td>
    <td>Cost per order</td>
  </tr>
  <tr>
    <td>gross_revenue</td>
    <td>Gross revenue</td>
  </tr>
  <tr>
    <td>roi</td>
    <td>ROI</td>
  </tr>
  <tr>
    <td>roas_bid</td>
    <td>Target ROI</td>
  </tr>
  <tr>
    <td rowspan="11">LIVE GMV Max Campaign, Campaign-level<br></td>
    <td>cost</td>
    <td>Cost</td>
  </tr>
  <tr>
    <td>net_cost</td>
    <td>Net cost</td>
  </tr>
  <tr>
    <td>orders</td>
    <td>Orders</td>
  </tr>
  <tr>
    <td>cost_per_order</td>
    <td>Cost per order</td>
  </tr>
  <tr>
    <td>gross_revenue</td>
    <td>Gross revenue</td>
  </tr>
  <tr>
    <td>roi</td>
    <td>ROI</td>
  </tr>
  <tr>
    <td>live_views</td>
    <td>LIVE views</td>
  </tr>
  <tr>
    <td>cost_per_live_view</td>
    <td>Cost per LIVE view</td>
  </tr>
  <tr>
    <td>10_second_live_views</td>
    <td>10-second LIVE views</td>
  </tr>
  <tr>
    <td>cost_per_10_second_live_view</td>
    <td>Cost per 10-second LIVE view</td>
  </tr>
  <tr>
    <td>live_follows</td>
    <td>LIVE follows</td>
  </tr>
  <tr>
    <td rowspan="11">LIVE GMV Max Campaign, Livestream-level</td>
    <td>cost</td>
    <td>Cost</td>
  </tr>
  <tr>
    <td>net_cost</td>
    <td>Net cost</td>
  </tr>
  <tr>
    <td>orders</td>
    <td>Orders</td>
  </tr>
  <tr>
    <td>cost_per_order</td>
    <td>Cost per order</td>
  </tr>
  <tr>
    <td>gross_revenue</td>
    <td>Gross revenue</td>
  </tr>
  <tr>
    <td>roi</td>
    <td>ROI</td>
  </tr>
  <tr>
    <td>live_views</td>
    <td>LIVE views</td>
  </tr>
  <tr>
    <td>cost_per_live_view</td>
    <td>Cost per LIVE view</td>
  </tr>
  <tr>
    <td>10_second_live_views</td>
    <td>10-second LIVE views</td>
  </tr>
  <tr>
    <td>cost_per_10_second_live_view</td>
    <td>Cost per 10-second LIVE view</td>
  </tr>
  <tr>
    <td>live_follows</td>
    <td>LIVE follows</td>
  </tr>
  <tr>
    <td rowspan="6">LIVE GMV Max Campaign, Duration-level</td>
    <td>cost</td>
    <td>Cost</td>
  </tr>
  <tr>
    <td>orders</td>
    <td>Orders</td>
  </tr>
  <tr>
    <td>cost_per_order</td>
    <td>Cost per order</td>
  </tr>
  <tr>
    <td>gross_revenue</td>
    <td>Gross revenue</td>
  </tr>
  <tr>
    <td>roi</td>
    <td>ROI</td>
  </tr>
</tbody>
</table>

# For all GMV Max Campaigns
Reporting metric data for all GMV Max Campaigns that are associated with a TikTok Shop is available when you set the filter field `gmv_max_promotion_types` to `["PRODUCT", "LIVE"]` or leave `gmv_max_promotion_types` unspecified.


## Overview metrics
These metrics show the overall performance for GMV Max Campaigns that are associated with a TikTok Shop, regardless of whether they are created on TikTok Ads Manager or TikTok Seller Center. 

**Supported dimension groupings**

To retrieve these metrics, specify any of the following dimension groupings through `dimensions`:
- `["advertiser_id"]`
- `["advertiser_id","stat_time_day"]`
- `["advertiser_id","stat_time_hour"]`

**Supported filters**
```xtable
| Filter field{25%} | Required/Optional{15%} | Max size{20%} | Requirement{40%} |
|---|---|---|---|
| gmv_max_promotion_types | Optional   |  N/A | Specify any of the following values: <br><br><ul><li>`PRODUCT`: Product GMV Max Campaign.</li><li>`LIVE`: LIVE GMV Max Campaign.</li></ul>|
```

**Supported metrics**
```xtable
| Metric field {25%}| Type{15%} | Description{20%} | Detail {40%}|
|---|---|---|---|
| cost | string | Cost | The estimated total amount you've spent on your ads during its schedule, in the currency of the ad account. |
| orders | string | Orders (SKU) | The number of individual SKU orders completed during the GMV Max Campaign. |
| cost_per_order | string | Cost per order | The average cost incurred for each order placed, in the currency of the ad account. It's calculated by dividing the total ad cost by the number of orders generated within the ad schedule. (Cost/Orders) |
| gross_revenue | string | Gross revenue | The total gross revenue of TikTok Shop orders attributed to your campaign, in the currency of the ad account. It's the amount the user pays, plus all TikTok Shop platform subsidies provided to the user. |
| roi | string | ROI | The total return on investment (ROI) from all TikTok Shop orders attributed to your campaign. |
| net_cost | string | Net cost | The amount spent, excluding ad credit or coupons used, in the currency of the ad account. |
```

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/?advertiser_id={{advertiser_id}}&store_ids=["{{store_id}}"]&start_date={{start_date}}&end_date={{end_date}}&dimensions=["advertiser_id", "stat_time_day"]&metrics=["cost", "orders", "cost_per_order", "gross_revenue", "roi", "net_cost"]&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "advertiser_id": "{{advertiser_id}}",
                    "stat_time_day": "{{stat_time_day}}"
                },
                "metrics": {
                    "cost": "9387.58",
                    "cost_per_order": "1.41",
                    "gross_revenue": "43117.68",
                    "net_cost": "6550.08",
                    "orders": "6674",
                    "roi": "4.59"
                }
            },
            {
                "dimensions": {
                    "advertiser_id": "{{advertiser_id}}",
                    "stat_time_day": "{{stat_time_day}}"
                },
                "metrics": {
                    "cost": "7230.56",
                    "cost_per_order": "1.44",
                    "gross_revenue": "31547.59",
                    "net_cost": "7230.56",
                    "orders": "5036",
                    "roi": "4.36"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```

# For Product GMV Max Campaigns
Reporting metric data for Product GMV Max Campaigns that are associated with a TikTok Shop is available at the campaign, product, creative, and duration levels.


## Campaign-level metrics (for Product GMV Max)
These metrics show the basic attributes and performance for Product GMV Max Campaigns created on TikTok Ads Manager or TikTok Seller Center. 

**Supported dimension groupings**

To retrieve campaign-level metrics for Product GMV Max Campaigns, specify any of the following dimension groupings using the `dimensions` parameter:
- `["campaign_id"]`
- `["campaign_id","stat_time_day"]`
- `["campaign_id","stat_time_hour"]`

**Supported filters**
```xtable
| Filter field{25%} | Required/Optional{15%} | Max size{20%} | Requirement{40%} |
|---|---|---|---|
| gmv_max_promotion_types | Required   | N/A |  `["PRODUCT"]` |
| campaign_ids | Optional  | 100|  Specify the IDs of Product GMV Max Campaigns.<br><br>To obtain the IDs of Product GMV Max Campaigns, use [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177). Set `gmv_max_promotion_types` to `["PRODUCT_GMV_MAX"]` and specify the ID of the TikTok Shop via `store_ids`. |
| campaign_name | Optional |N/A |  N/A |
| campaign_statuses | Optional  | N/A | Specify any of the following values:<ul><li>`STATUS_DELIVERY_OK`: Active. </li><li>`STATUS_DISABLE`: Inactive. </li><li> `STATUS_DELETE`: Deleted.</li><li>`STATUS_NOT_DELIVERY`: Not delivering.</li><li>`STATUS_ALL`: All statuses.</li></ul> |
```

**Supported metrics**
```xtable
| Metric field {25%}| Type{15%} | Description{20%} | Detail {40%}|
|---|---|---|---|
| campaign_id | string | Campaign ID   | N/A |
| operation_status | string | Campaign on/off status | Enum values: <ul><li>`ENABLE`: on.</li><li>`DISABLE`: off.</li></ul>|
| campaign_name | string | Campaign name   | N/A |
| schedule_type | string | Schedule type | Enum value: <ul><li>`Continuously`: To run the campaign continuously after the scheduled start time. </li></ul> |
| schedule_start_time | string | Schedule start time | Time zone: UTC+0. |
| schedule_end_time | string | Schedule end time | Time zone: UTC+0. |
| target_roi_budget | string | Target ROI budget   |N/A |
| bid_type | string | Optimization mode | Enum values: <ul><li>`CUSTOM`: Target ROI. </li><li>`NO_BID`: Maximum delivery. </li></ul>|
| max_delivery_budget | string | Max delivery budget   | N/A |
| roas_bid | string | Target ROI   | N/A |
| cost | string | Cost | The estimated total amount you've spent on your ads during its schedule, in the currency of the ad account.|
| net_cost | string | Net cost | The amount spent, excluding ad credit or coupons used, in the currency of the ad account. Note that the data latency for this metric can be up to 11 hours. <br>  Campaign data for is metric is available starting September 1, 2023. |
| orders | string | Orders (SKU) | The number of individual paid and organic SKU orders completed during the GMV Max Campaign, excluding SKU orders created from LIVE orders. <br> Campaign data for this metric is available starting September 27, 2024. |
| cost_per_order | string | Cost per order | The average ad cost for each order placed during this GMV Max Campaign in the currency of the ad account, excluding LIVE orders, calculated by dividing the total ad cost by the number of orders generated within the ad schedule.  <br> Campaign data for this metric is available starting September 27, 2024. |
| gross_revenue | string | Gross revenue | The total gross revenue of paid and organic TikTok Shop orders placed during your GMV Max Campaign in the currency of the ad account, excluding LIVE orders. It's the amount paid by users, plus all TikTok Shop platform product subsidies provided to those users.  <br> Campaign data for this metric is available starting September 27, 2024. |
| roi | string | ROI | Product ROI, calculated with all paid and organic sales attributed to an active GMV Max Campaign, excluding LIVE orders, divided by total ad cost. <br> Campaign data for this metric is available starting September 27, 2024. |
```

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/?advertiser_id={{advertiser_id}}&store_ids=["{{store_id}}"]&start_date={{start_date}}&end_date={{end_date}}&dimensions=["campaign_id", "stat_time_day"]&metrics=["campaign_id", "operation_status", "campaign_name", "schedule_type", "schedule_start_time", "schedule_end_time", "target_roi_budget", "bid_type", "max_delivery_budget", "roas_bid", "cost", "net_cost", "orders", "cost_per_order", "gross_revenue", "roi"]&filtering={"gmv_max_promotion_types": ["PRODUCT"]}&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "campaign_id": "{{campaign_id}}",
                    "stat_time_day": "{{stat_time_day}}"
                },
                "metrics": {
                    "bid_type": "CUSTOM",
                    "campaign_id": "{{campaign_id}}",
                    "campaign_name": "{{campaign_name}}",
                    "cost": "0.00",
                    "cost_per_order": "0.00",
                    "gross_revenue": "0.00",
                    "max_delivery_budget": "0.00",
                    "net_cost": "0.00",
                    "operation_status": "ENABLE",
                    "orders": "0",
                    "roas_bid": "5.50",
                    "roi": "0.00",
                    "schedule_end_time": "{{schedule_end_time}}",
                    "schedule_start_time": "{{schedule_start_time}}",
                    "schedule_type": "Continuously",
                    "target_roi_budget": "20000.00"
                }
            },
            {
                "dimensions": {
                    "campaign_id": "{{campaign_id}}",
                    "stat_time_day": "{{stat_time_day}}"
                },
                "metrics": {
                    "bid_type": "CUSTOM",
                    "campaign_id": "{{campaign_id}}",
                    "campaign_name": "{{campaign_name}}",
                    "cost": "0.00",
                    "cost_per_order": "0.00",
                    "gross_revenue": "0.00",
                    "max_delivery_budget": "0.00",
                    "net_cost": "0.00",
                    "operation_status": "DISABLE",
                    "orders": "0",
                    "roas_bid": "4.50",
                    "roi": "0.00",
                    "schedule_end_time": "{{schedule_end_time}}",
                    "schedule_start_time": "{{schedule_start_time}}",
                    "schedule_type": "Continuously",
                    "target_roi_budget": "1500.00"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```

## Product-level metrics
These metrics show the basic attributes and performance for products within Product GMV Max Campaigns created on TikTok Ads Manager or TikTok Seller Center. 

**Supported dimension groupings**

To retrieve product-level metrics for a Product GMV Max Campaign, specify any of the following dimension groupings using the `dimensions` parameter:
- `["item_group_id"]`
- `["item_group_id","stat_time_day"]`
- `["item_group_id","stat_time_hour"]`
- `["campaign_id","item_group_id"]`
- `["campaign_id","item_group_id","stat_time_day"]`
- `["campaign_id","item_group_id","stat_time_hour"]`

**Supported filters**
```xtable
| Filter field{25%} | Required/Optional{15%} | Max size{20%} | Requirement{40%} |
|---|---|---|---|
| campaign_ids | Required   | 100 | Specify the ID of a Product GMV Max Campaign. <br><br> To obtain such an ID, use the dimension `campaign_id` to query <a href="#item-link-Campaign-level metrics (for Product GMV Max)">campaign-level metrics</a> first. |
```

**Supported metrics**
```xtable
| Metric field {25%}| Type{15%} | Description{20%} | Detail {40%}|
|---|---|---|---|
| product_name | string | Product name   |N/A |
| item_group_id | string | Product SPU ID   |N/A |
| product_image_url | string | Product image URL   |N/A |
| product_status | string | Product status |Enum values: `available`, `unavailable`. <br>Example: `available`. |
| bid_type | string | Optimization mode | Enum values:<ul><li> `CUSTOM`: Target ROI</li><li> `NO_BID`: Maximum delivery.</li></ul> |
| cost | string | Cost | Total amount you've spent on your ads for this product during your Product GMV Max Campaign. Note that the data latency for this metric can be up to 11 hours. <br> Campaign data for this metric is available starting September 27, 2024. |
| orders | string | Orders (SKU) | The number of individual paid and organic SKU orders completed during the GMV Max Campaign for this product, excluding SKU orders created from LIVE orders. |
| cost_per_order | string | Cost per order | The average ad cost for each order placed for this product during this GMV Max Campaign, excluding LIVE orders, calculated by dividing the total ad cost by the number of orders generated within the ad schedule. <br> Campaign data for this metric is available starting September 27, 2024. |
| gross_revenue | string | Gross revenue | The total gross revenue of paid and organic TikTok Shop orders placed during your GMV Max Campaign in the currency of the ad account, excluding LIVE orders. It's the amount paid by users, plus all TikTok Shop platform product subsidies provided to those users. |
| roi | string | ROI | Product ROI is calculated with all paid and organic sales attributed to an active GMV Max Campaign, excluding LIVE orders, divided by total ad cost. <br> Campaign data for this metric is available starting September 27, 2024. |
```

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/?advertiser_id={{advertiser_id}}&store_ids=["{{store_id}}"]&start_date={{start_date}}&end_date={{end_date}}&dimensions=["item_group_id","stat_time_day"]&metrics=["product_name", "item_group_id", "product_image_url", "product_status", "bid_type","cost","orders","cost_per_order", "gross_revenue", "roi"]&filtering={"campaign_ids":["{{campaign_id}}"]}&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "item_group_id": "{{item_group_id}}",
                    "stat_time_day": "{{stat_time_day}}"
                },
                "metrics": {
                    "bid_type": "CUSTOM",
                    "cost": "13.86",
                    "cost_per_order": "2.77",
                    "currency": "0",
                    "gross_revenue": "55.47",
                    "item_group_id": "{{item_group_id}}",
                    "orders": "5",
                    "product_image_url": "{{product_image_url}}",
                    "product_name": "{{product_name}}",
                    "product_status": "available",
                    "roi": "4.00"
                }
            },
            {
                "dimensions": {
                    "item_group_id": "{{item_group_id}}",
                    "stat_time_day": "{{stat_time_day}}"
                },
                "metrics": {
                    "bid_type": "CUSTOM",
                    "cost": "12.11",
                    "cost_per_order": "2.02",
                    "currency": "0",
                    "gross_revenue": "69.49",
                    "item_group_id": "{{item_group_id}}",
                    "orders": "6",
                    "product_image_url": "{{product_image_url}}",
                    "product_name": "{{product_name}}",
                    "product_status": "available",
                    "roi": "5.74"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```

## Creative-level metrics
These metrics show the basic attributes and performance for creatives within Product GMV Max Campaigns created on TikTok Ads Manager or TikTok Seller Center. 

**Supported dimension groupings**

To retrieve creative-level metrics for Product GMV Max Campaigns, specify the following dimension grouping using the `dimensions` parameter:
- `["campaign_id","item_group_id","item_id"]`
- `["campaign_id","item_group_id","item_id","stat_time_day"]`
- `["item_id","stat_time_day"]`

> <span style="color:DodgerBlue">**Note**</span><br>
> If you specify the dimension grouping `["campaign_id","item_group_id","item_id","stat_time_day"]`, the API returns only records with associated cost data. As an example, for a campaign with 100 creatives where just 20 generated costs, you will receive reporting data solely for those 20.

**Supported filters**

```xtable
| Filter field{25%} | Required/Optional/Conditional{15%} | Max size{20%} | Requirement{40%} |
|---|---|---|---|
| campaign_ids | Required   | 100 | Specify the ID of a Product GMV Max Campaign. <br><br> To obtain such an ID, use the dimension `campaign_id` to query <a href="#item-link-Campaign-level metrics (for Product GMV Max)">campaign-level metrics</a> first. <br><br><p><span style="color:darkred"><b>Note</b></span>: When you filter multiple IDs using this field, you cannot retrieve <a href="#item-link-Attribute metrics">attribute metrics</a>.|
| item_group_ids | Required   | 100 |Specify the ID of an SPU used in the Product GMV Max Campaign. <br><br>  To obtain such an ID, use the dimension `item_group_id` to query <a href="#item-link-Product-level metrics">product-level metrics</a> first.  <br><br><p><span style="color:darkred"><b>Note</b></span>: When you filter multiple IDs using this field, you cannot retrieve <a href="#item-link-Attribute metrics">attribute metrics</a>.|
| creative_types {-to-be-deprecated}  | Conditional| 1 |<ul><li>Required when you filter one SPU ID using `campaign_ids` and `item_group_ids`.</li><li>Not supported when you specify multiple IDs in the `campaign_ids` filter or `item_group_ids` filter or both, or specify multiple ID dimensions.</li></ul><br>Specify any of the following values:<ul><li>`ADS_AND_ORGANIC`: Ads and organic.</li><li>`ORGANIC`: Organic only. </li><li>`REMOVED`: Removed.</li></ul> <br><p><span style="color:darkred"><b>Note</b></span>: This filter cannot be used together with `creative_delivery_statuses`.|
| creative_delivery_statuses | Optional | N/A |<ul><li>You can only use this filter in any of the following scenarios: <ul><li>When you specify multiple IDs in the `campaign_ids` filter or `item_group_ids` filter or both</li><li>When you specify one ID in the `campaign_ids` filter or `item_group_ids` filter or both</li><li>When you specify multiple ID dimensions</li></ul></li><li>This filter cannot be used together with `creative_types`.</li></ul><br>Specify one or more of the following values:<ul><li>`IN_QUEUE`: In queue. Post is ready to be tested for its conversion-driving potential.</li><li>`LEARNING`: Learning. Post is being tested for its conversion-driving potential.</li><li>`DELIVERING`: Delivering. Post is being regularly promoted as part of your campaign.</li><li>`NOT_DELIVERYING`: Not delivering. Post didn't show strong conversion potential during testing and will not be regularly promoted as part of your campaign.</li><li>`AUTHORIZATION_NEEDED`: Authorization needed. This video hasn't been authorized for use in ads. You can authorize your own videos from your TikTok account or request authorization from other creators. This status will be updated here within 20 minutes of authorization.</li><li>`EXCLUDED`: Excluded. Post was manually removed and will not be regularly promoted as part of your campaign. You can add this post back to your campaign at any time.</li><li>`UNAVAILABLE`: Unavailable. Private videos, rejected videos, deleted videos, or videos from suspended TikTok accounts can't be used in ads. In some cases, you may observe cost from unavailable videos because videos that are rejected by TikTok’s content review process part-way through the campaign may still be delivered.</li><li>`REJECTED`: Rejected. The video has been rejected by the content moderation team. If you've appealed a rejection, the video status will be "rejected" until the appeal has been reviewed.</li><li>`NOT_ACTIVE`: Not active. This post has been deprioritized because it was uploaded more than 30 days ago and hasn't earned any gross revenue in the past 30 days.</li></ul>|
| search_word | Optional |N/A | N/A |
```

### For creatives with statuses
Creative-level metrics for creatives with statuses are metrics for creatives with statuses displaying your ad creatives' progress as they are tested.
```xtable
| Metric field {30%}| Type{15%} | Description{15%} | Detail {40%}|
|---|---|---|---|
| title | string | Creative name   |N/A |
| item_id | string | TikTok post ID   |<p><span style="color:darkred"><b>Note</b></span>: The product card doesn't have an item ID. When `shop_content_type` is `PRODUCT_CARD`, the value of this metric will be `-1`. |
| tt_account_name| string | TikTok account name |  <p><span style="color:darkred"><b>Note</b></span>: If your access token lacks permission for the TikTok account, the value of this metric will be `0` or `-1`. |
| tt_account_profile_image_url | string | TikTok account profile image URL   |N/A |
| tt_account_authorization_type | string | Authorization type | Enum values:<ul><li>`TTS_TT`: TikTok Shop official account.</li><li>`AFFILIATE`: Affiliate mass authorization.</li><li>`TT_USER`: Business Account. </li><li> `BC_AUTH_TT`: Business Center. </li><li>`AUTH_CODE`: Video code.</li><li>`UNSET`: N/A.</li></ul>|
| shop_content_type| string | Shop content type | Enum values: <ul><li>`VIDEO`: Video. </li><li>`PRODUCT_CARD`: Product Card. </li></ul> |
| creative_delivery_status |string | Status |Enum values:<ul><li>`IN_QUEUE`: In queue. Post is ready to be tested for its conversion-driving potential.</li><li>`LEARNING`: Learning. Post is being tested for its conversion-driving potential.</li><li>`DELIVERING`: Delivering. Post is being regularly promoted as part of your campaign.</li><li>`NOT_DELIVERYING`: Not delivering. Post didn't show strong conversion potential during testing and will not be regularly promoted as part of your campaign.</li><li>`AUTHORIZATION_NEEDED`: Authorization needed. This video hasn't been authorized for use in ads. You can authorize your own videos from your TikTok account or request authorization from other creators. This status will be updated here within 20 minutes of authorization.</li><li>`EXCLUDED`: Excluded. Post was manually removed and will not be regularly promoted as part of your campaign. You can add this post back to your campaign at any time.</li><li>`UNAVAILABLE`: Unavailable. Private videos, rejected videos, deleted videos, or videos from suspended TikTok accounts can't be used in ads. In some cases, you may observe cost from unavailable videos because videos that are rejected by TikTok’s content review process part-way through the campaign may still be delivered.</li><li>`REJECTED`: Rejected. The video has been rejected by the content moderation team. If you've appealed a rejection, the video status will be "rejected" until the appeal has been reviewed.</li><li>`NOT_ACTIVE`: Not active. This post has been deprioritized because it was uploaded more than 30 days ago and hasn't earned any gross revenue in the past 30 days.</li></ul>  |
| cost | string | Cost | Total amount you've spent on your ads using this video during your Product GMV Max Campaign. Note that the data latency for this metric can be up to 11 hours. <br> Campaign data for this metric is available starting September 27, 2024. |
| orders | string | Orders (SKU) | The number of individual paid and organic SKU orders completed during the GMV Max Campaign, excluding SKU orders created from LIVE orders. |
| cost_per_order | string | Cost per order | The average ad cost for each order placed during this GMV Max Campaign, excluding LIVE orders, calculated by dividing the total ad cost by the number of orders generated within the ad schedule.  <br> Campaign data for this metric is available starting September 27, 2024. |
| gross_revenue | string | Gross revenue | The total gross revenue of paid and organic TikTok Shop orders placed during your GMV Max Campaign in the currency of the ad account, excluding LIVE orders. It's the amount paid by users, plus all TikTok Shop platform product subsidies provided to those users. |
| roi | string | ROI |Product ROI, which is calculated with all paid and organic sales attributed to an active GMV Max Campaign, excluding LIVE orders, divided by total ad cost. <br>Your ROI combines organic and paid GMV results. High ad spend may lower overall ROI despite good performance. Use total gross revenue to assess video quality.|
| product_impressions | string | Product ad impressions | Total paid product views during the Product GMV Max Campaign for the selected product from this video, excluding LIVE product views.<br> Campaign data for this metric is available starting September 27, 2024. |
| product_clicks | string | Product ad clicks | Total paid product clicks during the Product GMV Max Campaign for the selected product from this video, excluding LIVE product clicks.<br> Campaign data for this metric is available starting September 27, 2024. |
| product_click_rate | string | Product ad click rate | Click rate for paid product clicks during your Product GMV Max Campaign for the selected product from this video, excluding LIVE clicks, calculated by dividing total number of product clicks by total product views. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_click_rate | string | Ad click rate | Click-through rate of paid views from this video during your Product GMV Max Campaign for the selected product, excluding paid LIVE views, calculated by dividing total ad clicks by total ad views. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_conversion_rate | string | Ad conversion rate | Conversion rate of paid clicks from this video during your Product GMV Max Campaign for the selected product, excluding paid LIVE clicks, calculated by dividing total conversions by total clicks. <br>Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_2s | string | 2-second ad video view rate | Percentage of times your video was played for at least two seconds during your GMV Max Campaign for the selected product, calculated by dividing two-second video views by total video views. For each video view, plays are counted separately and replays are excluded. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_6s | string | 6-second ad video view rate | Percentage of times your video was played for at least six seconds during your GMV Max Campaign for the selected product, calculated by dividing six second video views by total video views. For each video view, plays are counted separately and replays are excluded.<br>Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_p25 | string | 25% ad video view rate | Percentage of times your video was played at least 25% of its runtime during your GMV Max Campaign for the selected product in paid views. For each video view, plays are counted separately and replays are excluded. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_p50 | string | 50% ad video view rate | Percentage of times your video was played at least 50% of its runtime during your GMV Max Campaign for the selected product in paid views. For each video view, plays are counted separately and replays are excluded. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_p75 | string | 75% ad video view rate | Percentage of times your video was played at least 75% of its runtime during your GMV Max Campaign for the selected product in paid views. For each video view, plays are counted separately and replays are excluded. <br>Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_p100 | string | 100% ad video view rate | Percentage of times your video was played at 100% of its runtime during your GMV Max Campaign for the selected product in paid views. For each video view, plays are counted separately and replays are excluded.<br> Campaign data for this metric is available starting September 27, 2024. |
```
**Example**

**Query delivery metrics by filtering multiple SPU IDs**

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/?advertiser_id={{advertiser_id}}&store_ids=["{{store_id}}"]&start_date={{start_date}}&end_date={{end_date}}&metrics=["creative_delivery_status","cost","orders","cost_per_order","gross_revenue","roi","product_impressions","product_clicks","product_click_rate","ad_click_rate","ad_conversion_rate","ad_video_view_rate_2s","ad_video_view_rate_6s","ad_video_view_rate_p25","ad_video_view_rate_p50","ad_video_view_rate_p75","ad_video_view_rate_p100"]&dimensions=["campaign_id","item_group_id","item_id"]&filtering={"campaign_ids":["{{campaign_id}}"],"item_group_ids":["{{item_group_id}}","{{item_group_id}}"]}&page=1&page_size=1000' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
 {
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "campaign_id": "{{campaign_id}}",
                    "item_group_id": "{{item_group_id}}",
                    "item_id": "{{item_id}}"
                },
                "metrics": {
                    "ad_click_rate": "16.67",
                    "ad_conversion_rate": "0.00",
                    "ad_video_view_rate_2s": "40.00",
                    "ad_video_view_rate_6s": "40.00",
                    "ad_video_view_rate_p100": "20.00",
                    "ad_video_view_rate_p25": "40.00",
                    "ad_video_view_rate_p50": "20.00",
                    "ad_video_view_rate_p75": "20.00",
                    "creative_delivery_status": "UNAVAILABLE",
                    "currency": "USD",
                    "cost": "0.00",
                    "cost_per_order": "0.00",
                    "gross_revenue": "0.00",
                    "orders": "0",
                    "roi": "0.00"
                    "product_click_rate": "16.67",
                    "product_clicks": "1",
                    "product_impressions": "6"
                }
            },
            {
                "dimensions": {
                    "campaign_id": "{{campaign_id}}",
                    "item_group_id": "{{item_group_id}}",
                    "item_id": "{{item_id}}0"
                },
                "metrics": {
                    "ad_click_rate": "0.00",
                    "ad_conversion_rate": "0.00",
                    "ad_video_view_rate_2s": "0.00",
                    "ad_video_view_rate_6s": "0.00",
                    "ad_video_view_rate_p100": "0.00",
                    "ad_video_view_rate_p25": "0.00",
                    "ad_video_view_rate_p50": "0.00",
                    "ad_video_view_rate_p75": "0.00",
                    "creative_delivery_status": "UNAVAILABLE",
                    "currency": "USD",
                    "cost": "0.00",
                    "cost_per_order": "0.00",
                    "gross_revenue": "0.00",
                    "orders": "0",
                    "roi": "0.00"
                    "product_click_rate": "0.00",
                    "product_clicks": "0",
                    "product_impressions": "0"
                }
            },
            {
                "dimensions": {
                    "campaign_id": "{{campaign_id}}",
                    "item_group_id": "{{item_group_id}}",
                    "item_id": "{{item_id}}"
                },
                "metrics": {
                    "ad_click_rate": "0.00",
                    "ad_conversion_rate": "0.00",
                    "ad_video_view_rate_2s": "0.00",
                    "ad_video_view_rate_6s": "0.00",
                    "ad_video_view_rate_p100": "0.00",
                    "ad_video_view_rate_p25": "0.00",
                    "ad_video_view_rate_p50": "0.00",
                    "ad_video_view_rate_p75": "0.00",
                    "creative_delivery_status": "UNAVAILABLE",
                    "currency": "USD",
                    "cost": "0.00",
                    "cost_per_order": "0.00",
                    "gross_revenue": "0.00",
                    "orders": "0",
                    "roi": "0.00"
                    "product_click_rate": "0.00",
                    "product_clicks": "0",
                    "product_impressions": "0"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 3,
            "total_page": 1
        }
    }
}
(/code)
```

### (<span style="color:Red">To-be-deprecated</span>) For ads and organic creatives
Creative-level metrics for ads and organic creatives are metrics for all creatives that are authorized and in use for ads. The total gross revenue from videos includes sales from both ads and organic videos.

To retrieve these metrics, set the filter field `creative_types` to `["ADS_AND_ORGANIC"]`.

```xtable
| Metric field {25%}| Type{15%} | Description{20%} | Detail {40%}|
|---|---|---|---|
| title | string | Creative name   |N/A |
| item_id | string | TikTok post ID   |<p><span style="color:darkred"><b>Note</b></span>: The product card doesn't have an item ID. When `shop_content_type` is `PRODUCT_CARD`, the value of this metric will be `-1`. |
| tt_account_name | string | TikTok account name |  <p><span style="color:darkred"><b>Note</b></span>: If your access token lacks permission for the TikTok account, the value of this metric will be `0` or `-1`. |
| tt_account_profile_image_url | string | TikTok account profile image URL   |N/A |
| tt_account_authorization_type | string | Authorization type | Enum values:<ul><li>`TTS_TT`: TikTok Shop official account.</li><li>`AFFILIATE`: Affiliate mass authorization.</li><li>`TT_USER`: Business Account. </li><li> `BC_AUTH_TT`: Business Center. </li><li>`AUTH_CODE`: Video code.</li><li>`UNSET`: N/A.</li></ul>|
| shop_content_type | string | Shop content type | Enum values: <ul><li>`VIDEO`: Video. </li><li>`PRODUCT_CARD`: Product Card. </li></ul> |
| orders | string | Orders (SKU) | The number of individual paid and organic SKU orders completed during the GMV Max Campaign, excluding SKU orders created from LIVE orders. |
| gross_revenue | string | Gross revenue | The total gross revenue of paid and organic TikTok Shop orders placed during your GMV Max Campaign in the currency of the ad account, excluding LIVE orders. It's the amount paid by users, plus all TikTok Shop platform product subsidies provided to those users. |
| product_impressions | string | Product ad impressions | Total organic and paid product views during the Product GMV Max Campaign for the selected product from this video, excluding LIVE product views.<br> Campaign data for this metric is available starting September 27, 2024. |
| product_clicks | string | Product ad clicks | Total organic and paid product clicks during the Product GMV Max Campaign for the selected product from this video, excluding LIVE product clicks.<br> Campaign data for this metric is available starting September 27, 2024. |
| product_click_rate | string | Product ad click rate | Click rate for organic and paid product clicks during your Product GMV Max Campaign for the selected product from this video, excluding LIVE clicks, calculated by dividing total number of product clicks by total product views. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_click_rate | string | Ad click rate | Click-through rate of paid views from this video during your Product GMV Max Campaign for the selected product, excluding paid LIVE views, calculated by dividing total ad clicks by total ad views. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_conversion_rate | string | Ad conversion rate | Conversion rate of paid clicks from this video during your Product GMV Max Campaign for the selected product, excluding paid LIVE clicks, calculated by dividing total conversions by total clicks. <br>Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_2s | string | 2-second ad video view rate | Percentage of times your video was played for at least two seconds during your GMV Max Campaign for the selected product, calculated by dividing two-second video views by total video views. For each video view, plays are counted separately and replays are excluded. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_6s | string | 6-second ad video view rate | Percentage of times your video was played for at least six seconds during your GMV Max Campaign for the selected product, calculated by dividing six second video views by total video views. For each video view, plays are counted separately and replays are excluded.<br>Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_p25 | string | 25% ad video view rate | Percentage of times your video was played at least 25% of its runtime during your GMV Max Campaign for the selected product in paid views. For each video view, plays are counted separately and replays are excluded. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_p50 | string | 50% ad video view rate | Percentage of times your video was played at least 50% of its runtime during your GMV Max Campaign for the selected product in paid views. For each video view, plays are counted separately and replays are excluded. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_p75 | string | 75% ad video view rate | Percentage of times your video was played at least 75% of its runtime during your GMV Max Campaign for the selected product in paid views. For each video view, plays are counted separately and replays are excluded. <br>Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_p100 | string | 100% ad video view rate | Percentage of times your video was played at 100% of its runtime during your GMV Max Campaign for the selected product in paid views. For each video view, plays are counted separately and replays are excluded.<br> Campaign data for this metric is available starting September 27, 2024. |
```

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/?advertiser_id={{advertiser_id}}&store_ids=["{{store_id}}"]&start_date={{start_date}}&end_date={{end_date}}&dimensions=["item_id"]&metrics=["title","item_id","tt_account_name","tt_account_profile_image_url","tt_account_authorization_type","shop_content_type","orders","gross_revenue","product_impressions","product_clicks","product_click_rate","ad_click_rate","ad_conversion_rate","ad_video_view_rate_2s","ad_video_view_rate_6s","ad_video_view_rate_p25","ad_video_view_rate_p50","ad_video_view_rate_p75","ad_video_view_rate_p100"]&filtering={"campaign_ids":["{{campaign_id}}"], "item_group_ids": ["{{item_group_id}}"], "creative_types": ["ADS_AND_ORGANIC"]}&page_size=1000&page=1' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "item_id": "{{item_id}}"
                },
                "metrics": {
                    "ad_click_rate": "0.94",
                    "ad_conversion_rate": "0.00",
                    "ad_video_view_rate_2s": "11.92",
                    "ad_video_view_rate_6s": "3.97",
                    "ad_video_view_rate_p100": "0.70",
                    "ad_video_view_rate_p25": "7.71",
                    "ad_video_view_rate_p50": "3.27",
                    "ad_video_view_rate_p75": "1.87",
                    "gross_revenue": "0.00",
                    "item_id": "{{item_id}}",
                    "orders": "0",
                    "product_click_rate": "0.94",
                    "product_clicks": "5",
                    "product_impressions": "531",
                    "shop_content_type": "VIDEO",
                    "title": "{{title}}",
                    "tt_account_authorization_type": "BC_AUTH_TT",
                    "tt_account_name": "{{tt_account_name}}",
                    "tt_account_profile_image_url": "{{tt_account_profile_image_url}}"
                }
            },
            {
                "dimensions": {
                    "item_id": "{{item_id}}"
                },
                "metrics": {
                    "ad_click_rate": "0.00",
                    "ad_conversion_rate": "0.00",
                    "ad_video_view_rate_2s": "8.33",
                    "ad_video_view_rate_6s": "0.00",
                    "ad_video_view_rate_p100": "0.00",
                    "ad_video_view_rate_p25": "0.00",
                    "ad_video_view_rate_p50": "0.00",
                    "ad_video_view_rate_p75": "0.00",
                    "gross_revenue": "0.00",
                    "item_id": "{{item_id}}",
                    "orders": "0",
                    "product_click_rate": "0.00",
                    "product_clicks": "0",
                    "product_impressions": "15",
                    "shop_content_type": "VIDEO",
                    "title": "{{title}}",
                    "tt_account_authorization_type": "AFFILIATE",
                    "tt_account_name": "{{tt_account_name}}",
                    "tt_account_profile_image_url": "{{tt_account_profile_image_url}}"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```

### (<span style="color:Red">To-be-deprecated</span>) For organic only creatives
Creative-level metrics for organic only creatives are metrics for videos that are used in organic traffic only. The total gross revenue from videos includes sales from organic videos only.

To retrieve these metrics, set the filter field `creative_types` to `["ORGANIC"]`.

```xtable
| Metric field {25%}| Type{15%} | Description{20%} | Detail {40%}|
|---|---|---|---|
| title | string | Creative name   | N/A |
| item_id | string | TikTok post ID   | N/A |
| tt_account_name | string | TikTok account name  | N/A |
| tt_account_profile_image_url | string | TikTok account profile image URL  | N/A |
| shop_content_type | string | Shop content type | Enum value: <ul><li>`VIDEO`：Video. </li></ul> |
| orders | string | Orders (SKU) | The number of individual paid and organic SKU orders completed during the GMV Max Campaign, excluding SKU orders created from LIVE orders. |
| gross_revenue | string | Gross revenue | The total gross revenue of paid and organic TikTok Shop orders placed during your GMV Max Campaign in the currency of the ad account, excluding LIVE orders. It's the amount paid by users, plus all TikTok Shop platform product subsidies provided to those users. |
```

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/?advertiser_id={{advertiser_id}}&store_ids=["{{store_id}}"]&start_date={{start_date}}&end_date={{end_date}}&dimensions=["item_id"]&metrics=["title","item_id","tt_account_name","tt_account_profile_image_url","shop_content_type","orders","gross_revenue"]&filtering={"campaign_ids":["{{campaign_id}}"], "item_group_ids": ["{{item_group_id}}"], "creative_types": ["ORGANIC"]}&page_size=1000&page=1' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "item_id": "{{item_id}}"
                },
                "metrics": {
                    "gross_revenue": "0.00",
                    "item_id": "{{item_id}}",
                    "orders": "0",
                    "shop_content_type": "VIDEO",
                    "title": "{{title}}",
                    "tt_account_name": "{{tt_account_name}}",
                    "tt_account_profile_image_url": "{{tt_account_profile_image_url}}"
                }
            },
            {
                "dimensions": {
                    "item_id": "{{item_id}}"
                },
                "metrics": {
                    "gross_revenue": "13.56",
                    "item_id": "{{item_id}}",
                    "orders": "2",
                    "shop_content_type": "VIDEO",
                    "title": "{{title}}",
                    "tt_account_name": "{{tt_account_name}}",
                    "tt_account_profile_image_url": "{{tt_account_profile_image_url}}"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```

### (<span style="color:Red">To-be-deprecated</span>) For removed creatives
Creative-level metrics for removed creatives are metrics for automatically selected videos that have been manually removed from your campaign.

To retrieve these metrics, set the filter field `creative_types` to `["REMOVED"]`.

```xtable
| Metric field {25%}| Type{15%} | Description{20%} | Detail {40%}|
|---|---|---|---|
| title | string | Creative name   |N/A |
| item_id | string | TikTok post ID   |<p><span style="color:darkred"><b>Note</b></span>: The product card doesn't have an item ID. When `shop_content_type` is `PRODUCT_CARD`, the value of this metric will be `-1`. |
| tt_account_name | string | TikTok account name |  <p><span style="color:darkred"><b>Note</b></span>: If your access token lacks permission for the TikTok account, the value of this metric will be `0` or `-1`. |
| tt_account_profile_image_url | string | TikTok account profile image URL   |N/A |
| tt_account_authorization_type | string | Authorization type | Enum values:<ul><li>`TTS_TT`: TikTok Shop official account.</li><li>`AFFILIATE`: Affiliate mass authorization.</li><li>`TT_USER`: Business Account. </li><li> `BC_AUTH_TT`: Business Center. </li><li>`AUTH_CODE`: Video code.</li><li>`UNSET`: N/A.</li></ul>|
| shop_content_type | string | Shop content type | Enum values: <ul><li>`VIDEO`: Video. </li><li>`PRODUCT_CARD`: Product Card. </li></ul> |
| orders | string | Orders (SKU) | The number of individual paid and organic SKU orders completed during the GMV Max Campaign, excluding SKU orders created from LIVE orders. |
| gross_revenue | string | Gross revenue | The total gross revenue of paid and organic TikTok Shop orders placed during your GMV Max Campaign in the currency of the ad account, excluding LIVE orders. It's the amount paid by users, plus all TikTok Shop platform product subsidies provided to those users. |
| product_impressions | string | Product ad impressions | Total organic and paid product views during the Product GMV Max Campaign for the selected product from this video, excluding LIVE product views.<br> Campaign data for this metric is available starting September 27, 2024. |
| product_clicks | string | Product ad clicks | Total organic and paid product clicks during the Product GMV Max Campaign for the selected product from this video, excluding LIVE product clicks.<br> Campaign data for this metric is available starting September 27, 2024. |
| product_click_rate | string | Product ad click rate | Click rate for organic and paid product clicks during your Product GMV Max Campaign for the selected product from this video, excluding LIVE clicks, calculated by dividing total number of product clicks by total product views. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_click_rate | string | Ad click rate | Click-through rate of paid views from this video during your Product GMV Max Campaign for the selected product, excluding paid LIVE views, calculated by dividing total ad clicks by total ad views. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_conversion_rate | string | Ad conversion rate | Conversion rate of paid clicks from this video during your Product GMV Max Campaign for the selected product, excluding paid LIVE clicks, calculated by dividing total conversions by total clicks. <br>Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_2s | string | 2-second ad video view rate | Percentage of times your video was played for at least two seconds during your GMV Max Campaign for the selected product, calculated by dividing two-second video views by total video views. For each video view, plays are counted separately and replays are excluded. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_6s | string | 6-second ad video view rate | Percentage of times your video was played for at least six seconds during your GMV Max Campaign for the selected product, calculated by dividing six second video views by total video views. For each video view, plays are counted separately and replays are excluded.<br>Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_p25 | string | 25% ad video view rate | Percentage of times your video was played at least 25% of its runtime during your GMV Max Campaign for the selected product in paid views. For each video view, plays are counted separately and replays are excluded. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_p50 | string | 50% ad video view rate | Percentage of times your video was played at least 50% of its runtime during your GMV Max Campaign for the selected product in paid views. For each video view, plays are counted separately and replays are excluded. <br> Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_p75 | string | 75% ad video view rate | Percentage of times your video was played at least 75% of its runtime during your GMV Max Campaign for the selected product in paid views. For each video view, plays are counted separately and replays are excluded. <br>Campaign data for this metric is available starting September 27, 2024. |
| ad_video_view_rate_p100 | string | 100% ad video view rate | Percentage of times your video was played at 100% of its runtime during your GMV Max Campaign for the selected product in paid views. For each video view, plays are counted separately and replays are excluded.<br> Campaign data for this metric is available starting September 27, 2024. |
```

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/?advertiser_id={{advertiser_id}}&store_ids=["{{store_id}}"]&start_date={{start_date}}&end_date={{end_date}}&dimensions=["item_id"]&metrics=["title","item_id","tt_account_name","tt_account_profile_image_url","tt_account_authorization_type","shop_content_type","orders","gross_revenue","product_impressions","product_clicks","product_click_rate","ad_click_rate","ad_conversion_rate","ad_video_view_rate_2s","ad_video_view_rate_6s","ad_video_view_rate_p25","ad_video_view_rate_p50","ad_video_view_rate_p75","ad_video_view_rate_p100"]&filtering={"campaign_ids":["{{campaign_id}}"], "item_group_ids": ["{{item_group_id}}"], "creative_types": ["REMOVED"]}&page_size=1000&page=1' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "item_id": "{{item_id}}"
                },
                "metrics": {
                    "ad_click_rate": "1.02",
                    "ad_conversion_rate": "0.00",
                    "ad_video_view_rate_2s": "25.00",
                    "ad_video_view_rate_6s": "9.38",
                    "ad_video_view_rate_p100": "1.04",
                    "ad_video_view_rate_p25": "4.17",
                    "ad_video_view_rate_p50": "2.08",
                    "ad_video_view_rate_p75": "2.08",
                    "gross_revenue": "0.00",
                    "item_id": "{{item_id}}",
                    "orders": "0",
                    "product_click_rate": "1.02",
                    "product_clicks": "1",
                    "product_impressions": "98",
                    "shop_content_type": "VIDEO",
                    "title": "{{title}}",
                    "tt_account_authorization_type": "AFFILIATE",
                    "tt_account_name": "{{tt_account_name}}",
                    "tt_account_profile_image_url": "{{tt_account_profile_image_url}}"
                }
            },
            {
                "dimensions": {
                    "item_id": "{{item_id}}"
                },
                "metrics": {
                    "ad_click_rate": "1.73",
                    "ad_conversion_rate": "8.33",
                    "ad_video_view_rate_2s": "35.09",
                    "ad_video_view_rate_6s": "7.67",
                    "ad_video_view_rate_p100": "0.85",
                    "ad_video_view_rate_p25": "5.28",
                    "ad_video_view_rate_p50": "3.24",
                    "ad_video_view_rate_p75": "1.70",
                    "gross_revenue": "0.00",
                    "item_id": "{{item_id}}",
                    "orders": "0",
                    "product_click_rate": "1.73",
                    "product_clicks": "12",
                    "product_impressions": "692",
                    "shop_content_type": "VIDEO",
                    "title": "{{title}}",
                    "tt_account_authorization_type": "AFFILIATE",
                    "tt_account_name": "{{tt_account_name}}",
                    "tt_account_profile_image_url": "{{tt_account_profile_image_url}}"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```

## Duration-level metrics
These metrics show the basic attributes and performance for each duration of Product GMV Max Campaigns created on TikTok Ads Manager or TikTok Seller Center. 
- If the optimization mode remained unchanged throughout the campaign, you will see a single record corresponding to the configured optimization mode.
- If the optimization mode was changed during the campaign, you will see multiple records, with each record corresponding to each optimization mode used.

For example, if you initially set the optimization mode to Target ROI and later switched to Maximum Delivery, you will receive two separate records: one for the period using Target ROI and another for Maximum Delivery.

**Supported dimension groupings**

To retrieve duration-level metrics for Product GMV Max Campaigns, specify any of the following dimension groupings using the `dimensions` parameter:
- `["duration"]`
- `["campaign_id","duration"]`
- `["item_group_id","duration"]`
- `["campaign_id","item_group_id","duration"]`

**Supported filters**
```xtable
| Filter field{25%} | Required/Optional{15%} | Max size{20%} | Requirement{40%} |
|---|---|---|---|
| campaign_ids | Required | 100  | Specify the ID of a Product GMV Max Campaign.<br><br> To obtain such an ID, use the dimension `campaign_id` to <a href="#item-link-Campaign-level metrics (for Product GMV Max)">campaign-level metrics</a> first. <br><br><p><span style="color:darkred"><b>Note</b></span>: When you filter multiple IDs using this field, you cannot retrieve <a href="#item-link-Attribute metrics">attribute metrics</a>. |
| item_group_ids | Required  | 100 | Specify the ID of an SPU used in the Product GMV Max Campaign.<br><br>To obtain such an ID, use the dimension `item_group_id` to query <a href="#item-link-Product-level metrics">product-level metrics</a> first. <br><br><p><span style="color:darkred"><b>Note</b></span>: When you filter multiple IDs using this field, you cannot retrieve <a href="#item-link-Attribute metrics">attribute metrics</a>. |
```

**Supported metrics**
```xtable
| Metric field {25%}| Type{15%} | Description{20%} | Detail {40%}|
|---|---|---|---|
| bid_type | string | Optimization mode | Enum values: <ul><li>`CUSTOM`: Target ROI.</li><li>`NO_BID`: Maximum delivery.</li></ul>|
| cost | string | Cost | Total amount you've spent on your ads for this product during your Product GMV Max Campaign, in the currency of the ad account. <br>Note that the data latency for this metric can be up to 11 hours. <br> Campaign data for this metric is available starting September 27, 2024. |
| orders | string | Orders (SKU) | The number of individual paid and organic SKU orders completed during the GMV Max Campaign for this product, excluding SKU orders created from livestreams. |
| cost_per_order | string | Cost per order | Average ad cost for each order placed for this product in your Product GMV Max Campaign in the currency of the ad account, calculated by dividing your total ad cost by the number of SKU orders placed while your campaign is live. <br>Note that the data latency for this metric can be up to 11 hours. |
| gross_revenue | string | Gross revenue | Total gross revenue of paid and organic TikTok Shop orders placed during this Product GMV Max Campaign, in the currency of the ad account. It's the amount paid by users, plus TikTok Shop platform price subsidies provided to those users. |
| roi | string | ROI | Product ROI, which is calculated with all paid and organic sales attributed to an active GMV Max Campaign, excluding LIVE orders, divided by total ad cost. <br>Campaign data for this metric is available starting September 27, 2024. |
```

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/?advertiser_id={{advertiser_id}}&store_ids=["{{store_id}}"]&start_date={{start_date}}&end_date={{end_date}}&dimensions=["duration"]&metrics=["bid_type", "cost", "orders", "cost_per_order", "gross_revenue", "roi"]&filtering={"campaign_ids":["{{campaign_id}}"], "item_group_ids": ["{{item_group_id}}"]}&page_size=1000&page=1' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "duration": "{{duration}}"
                },
                "metrics": {
                    "bid_type": "CUSTOM",
                    "cost": "1428.80",
                    "cost_per_order": "2.62",
                    "gross_revenue": "6141.15",
                    "orders": "545",
                    "roi": "4.30"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 1,
            "total_page": 1
        }
    }
}
(/code)
```

# For LIVE GMV Max Campaigns
Reporting metric data for LIVE GMV Max Campaigns that are associated with a TikTok Shop is available at the campaign, livestream, and duration levels.


## Campaign-level metrics (for LIVE GMV Max)
These metrics show the basic attributes and performance for LIVE GMV Max Campaigns created on TikTok Ads Manager or TikTok Seller Center. 

**Supported dimension groupings**

To retrieve campaign-level metrics for LIVE GMV Max Campaigns, specify any of the following dimension groupings using the `dimensions` parameter:
- `["campaign_id"]`
- `["campaign_id","stat_time_day"]`
- `["campaign_id","stat_time_hour"]`

**Supported filters**
```xtable
| Filter field{25%} | Required/Optional{15%} | Max size{20%} | Requirement{40%} |
|---|---|---|---|
| gmv_max_promotion_types | Required | N/A   | `["LIVE"]` |
| campaign_ids | Optional   | 100 |Specify the IDs of LIVE GMV Max Campaigns. <br><br>To obtain the IDs of LIVE Max Campaigns, use [/gmv_max/campaign/get/](https://business-api.tiktok.com/portal/docs?id=1826463372290177). Set `gmv_max_promotion_types` to `["LIVE_GMV_MAX"]` and specify the ID of the TikTok Shop via `store_ids`. |
| campaign_name | Optional | N/A | N/A |
| campaign_statuses | Optional   | N/A |Specify any of the following values: <ul><li>`STATUS_DELIVERY_OK`: Active.</li><li>`STATUS_DISABLE`: Inactive.</li><li> `STATUS_DELETE`: Deleted.</li><li>`STATUS_NOT_DELIVERY`: Not delivering.</li><li>`STATUS_ALL`: All statuses.</li></ul>|
```

**Supported metrics**
```xtable
| Metric field {25%}| Type{15%} | Description{20%} | Detail {40%}|
|---|---|---|---|
| campaign_id | string | Campaign ID.   |N/A |
| operation_status | string | Campaign on/off status | Enum values:<ul><li>`ENABLE`: on.</li><li>`DISABLE`: off.</li></ul>|
| campaign_name | string | Campaign name | N/A |
| tt_account_name | string | TikTok account name    | <p><span style="color:darkred"><b>Note</b></span>: If your access token lacks permission for the TikTok account, the value of this metric will be `0` or `-1`. |
| tt_account_profile_image_url | string | TikTok account profile image URL   |N/A |
| identity_id | string | Identity ID (TikTok account ID)     | <p><span style="color:darkred"><b>Note</b></span>: If your access token lacks permission for the TikTok account, the value of this metric will be `0` or `-1`. |
| bid_type | string | Optimization mode | Enum values: <ul><li>`CUSTOM`: Target ROI.</li><li>`NO_BID`: Maximum delivery. </li></ul>|
| schedule_type | string | Schedule type. | Enum value:<ul><li>`Continuously`: To run the campaign continuously after the scheduled start time.</li></ul> |
| schedule_start_time | string | Schedule start time | Time zone: UTC+0. |
| schedule_end_time | string | Schedule end time | Time zone: UTC+0. |
| target_roi_budget | string | Target ROI budget   |N/A |
| max_delivery_budget | string | Max delivery budget   |N/A |
| roas_bid | string | Target ROI   |N/A |
| cost | string | Cost | The estimated total amount you've spent on your ads during its schedule, in the currency of the ad account. |
| net_cost | string | Net cost | The amount spent, excluding ad credit or coupons used, in the currency of the ad account.<br>Note that the data latency for this metric can be up to 11 hours. Campaign data for is metric is available starting September 1, 2023. |
| cost_per_order | string | Cost per order (Current shop) | The average cost incurred for each order placed, calculated by dividing the total ad cost by the number of orders generated within the ad schedule. Only orders from your current shop are included, even if the livestream promoted products from other shops. |
| orders | string | Orders (Current shop) | The number of individual paid and organic SKU LIVE orders during the GMV Max campaign. Only orders from your current shop are included, even if the livestream promoted products from other shops. |
| gross_revenue | string | Gross revenue (Current shop) | The total gross revenue of Shop orders attributed to your campaign. It's the amount the user pays, plus all Shop price subsidies provided to the user. Only orders from your current shop are included, even if the livestream promoted products from other shops. |
| roi | string | ROI (Current shop) | The total return on investment (ROI) from all Shop orders attributed to your campaign. Only orders from your current shop are included, even if the livestream promoted products from other shops. |
| all_shops_cost_per_order | double | Cost per order | The average ad cost for each LIVE order placed during this GMV Max Campaign in the currency of the ad account, calculated by dividing total ad cost by the number of orders generated within the ad schedule.<br><br>Campaign data for this metric is available starting September 27, 2024. |
| all_shops_orders | integer | Orders | The number of individual paid and organic SKU LIVE orders during the GMV Max Campaign.<br><br>Campaign data for this metric is available starting September 27, 2024. |
| all_shops_gross_revenue | double | Gross revenue | The total gross revenue of paid and organic TikTok Shop LIVE orders during your GMV Max Campaign, in the currency of the ad account. It's the amount paid by users, plus all TikTok Shop platform subsidies provided to those users.<br><br>Campaign data for this metric is available starting September 27, 2024. |
| all_shops_roi | double | ROI | The total return on investment (ROI) from all paid and organic TikTok Shop LIVE orders during this GMV Max Campaign.<br><br>Campaign data for this metric is available starting September 27, 2024. |
| live_views | string | LIVE views | Number of times your LIVE was viewed during your GMV Max Campaign. Multiple views from the same user are counted separately. <br> Campaign data for this metric is available starting September 27, 2024. |
| cost_per_live_view | string | Cost per LIVE view | Average cost for each LIVE view during your GMV Max Campaign in the currency of the ad account, calculated by dividing total cost by total live views. <br>Campaign data for this metric is available starting September 27, 2024. |
| 10_second_live_views | string | 10-second LIVE views | Number of times your LIVE was viewed for at least 10 seconds from the GMV Max Campaign. Multiple views from the same user are counted separately. <br>Campaign data for this metric is available starting September 27, 2024. |
| cost_per_10_second_live_view | string | Cost per 10-second LIVE view | Average cost for each 10-second LIVE view in the currency of the ad account, calculated by dividing total cost by total number of 10-second LIVE views. <br>Campaign data for this metric is available starting September 27, 2024. |
| live_follows | string | LIVE follows | Total number of users who followed your profile during your LIVE. <br> Campaign data for this metric is available starting September 27, 2024. |
```

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/?advertiser_id={{advertiser_id}}&store_ids=["{{store_id}}"]&start_date={{start_date}}&end_date={{end_date}}&dimensions=["campaign_id","stat_time_day"]&metrics=["campaign_id", "operation_status", "campaign_name", "tt_account_name", "tt_account_profile_image_url", "identity_id", "bid_type", "schedule_type", "schedule_start_time", "schedule_end_time", "target_roi_budget", "max_delivery_budget", "roas_bid", "cost", "net_cost", "orders", "cost_per_order", "gross_revenue", "roi", "live_views", "cost_per_live_view", "10_second_live_views", "cost_per_10_second_live_view", "live_follows"]&filtering={"gmv_max_promotion_types": ["LIVE"]}&page_size=1000&page=1' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "campaign_id": "{{campaign_id}}",
                    "stat_time_day": "{{stat_time_day}}"
                },
                "metrics": {
                    "10_second_live_views": "739",
                    "bid_type": "CUSTOM",
                    "campaign_id": "{{campaign_id}}",
                    "campaign_name": "{{campaign_name}}",
                    "cost": "57.75",
                    "cost_per_10_second_live_view": "0.08",
                    "cost_per_live_view": "0.01",
                    "cost_per_order": "1.15",
                    "gross_revenue": "313.17",
                    "identity_id": "",
                    "live_follows": "8",
                    "live_views": "4120",
                    "max_delivery_budget": "0.00",
                    "net_cost": "57.75",
                    "operation_status": "ENABLE",
                    "orders": "50",
                    "roas_bid": "5.50",
                    "roi": "5.42",
                    "schedule_end_time": "{{schedule_end_time}}",
                    "schedule_start_time": "{{schedule_start_time}}",
                    "schedule_type": "Continuously",
                    "target_roi_budget": "10000.00",
                    "tt_account_name": "{{tt_account_name}}",
                    "tt_account_profile_image_url": "{{tt_account_profile_image_url}}"
                }
            },
            {
                "dimensions": {
                    "campaign_id": "{{campaign_id}}",
                    "stat_time_day": "{{stat_time_day}}"
                },
                "metrics": {
                    "10_second_live_views": "0",
                    "bid_type": "CUSTOM",
                    "campaign_id": "{{campaign_id}}",
                    "campaign_name": "{{campaign_name}}",
                    "cost": "0.00",
                    "cost_per_10_second_live_view": "0.00",
                    "cost_per_live_view": "0.00",
                    "cost_per_order": "0.00",
                    "gross_revenue": "0.00",
                    "identity_id": "",
                    "live_follows": "0",
                    "live_views": "0",
                    "max_delivery_budget": "0.00",
                    "net_cost": "0.00",
                    "operation_status": "DISABLE",
                    "orders": "0",
                    "roas_bid": "5.00",
                    "roi": "0.00",
                    "schedule_end_time": "{{schedule_end_time}}1",
                    "schedule_start_time": "{{schedule_start_time}}",
                    "schedule_type": "Continuously",
                    "target_roi_budget": "1000.00",
                    "tt_account_name": "0",
                    "tt_account_profile_image_url": "0"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```

## Livestream-level metrics
These metrics show the basic attributes and performance for each livestream room in your LIVE GMV Max Campaigns created on TikTok Ads Manager or TikTok Seller Center. 

**Supported dimension groups**

To retrieve livestream-level metrics for LIVE GMV Max Campaigns, specify any of the following dimension groupings using the `dimensions` parameter:
- `["room_id"]`
- `["room_id","stat_time_day"]`
- `["room_id","stat_time_hour"]`
- `["campaign_id","room_id"]`
- `["campaign_id","room_id","stat_time_day"]`
- `["campaign_id","room_id","stat_time_hour"]`

**Supported filters**
```xtable
| Filter field{25%} | Required/Optional{15%} | Max size{20%} | Requirement{40%} |
|---|---|---|---|
| campaign_ids | Required | 100 | Specify the ID of a LIVE GMV Max Campaign. <br><br>To obtain such an ID, use the dimension `campaign_id` to query <a href="#item-link-Campaign-level metrics (for LIVE GMV Max)">campaign-level metrics</a> first. |
```

**Supported metrics**
```xtable
| Metric field {25%}| Type{15%} | Description{20%} | Detail {40%}|
|---|---|---|---|
| live_name | string | LIVE name   |N/A |
| live_status | string | Livestream status | Enum values:<ul><li>`ONGOING`: Ongoing. </li><li>`END`: Ended.</li></ul> |
| live_launched_time | string | Livestream launched time | Time zone: ad account time zone. |
| live_duration | string | Livestream duration   |N/A |
| cost | string | Cost | The estimated total amount you've spent on your ads during its schedule, in the currency of the ad account.|
| net_cost | string | Net cost | The amount spent, excluding ad credit or coupons used, in the currency of the ad account.<br>Note that the data latency for this metric can be up to 11 hours. Campaign data for is metric is available starting September 1, 2023. |
| cost_per_order | string | Cost per order (Current shop) | The average cost incurred for each order placed, calculated by dividing the total ad cost by the number of orders generated within the ad schedule. Only orders from your current shop are included, even if the livestream promoted products from other shops. |
| orders | string | Orders (Current shop) | The number of individual paid and organic SKU LIVE orders during the GMV Max campaign. Only orders from your current shop are included, even if the livestream promoted products from other shops. |
| gross_revenue | string | Gross revenue (Current shop) | The total gross revenue of Shop orders attributed to your campaign. It's the amount the user pays, plus all Shop price subsidies provided to the user. Only orders from your current shop are included, even if the livestream promoted products from other shops. |
| roi | string | ROI (Current shop) | The total return on investment (ROI) from all Shop orders attributed to your campaign. Only orders from your current shop are included, even if the livestream promoted products from other shops. |
| all_shops_cost_per_order | double | Cost per order | The average ad cost for each LIVE order placed during this GMV Max Campaign in the currency of the ad account, calculated by dividing total ad cost by the number of orders generated within the ad schedule.<br><br>Campaign data for this metric is available starting September 27, 2024. |
| all_shops_orders | integer | Orders | The number of individual paid and organic SKU LIVE orders during the GMV Max Campaign.<br><br>Campaign data for this metric is available starting September 27, 2024. |
| all_shops_gross_revenue | double | Gross revenue | The total gross revenue of paid and organic TikTok Shop LIVE orders during your GMV Max Campaign, in the currency of the ad account. It's the amount paid by users, plus all TikTok Shop platform subsidies provided to those users.<br><br>Campaign data for this metric is available starting September 27, 2024. |
| all_shops_roi | double | ROI | The total return on investment (ROI) from all paid and organic TikTok Shop LIVE orders during this GMV Max Campaign.<br><br>Campaign data for this metric is available starting September 27, 2024. |
| live_views | string | LIVE views | Number of times your LIVE was viewed during your GMV Max Campaign. Multiple views from the same user are counted separately. <br> Campaign data for this metric is available starting September 27, 2024. |
| cost_per_live_view | string | Cost per LIVE view | Average cost for each LIVE view during your GMV Max Campaign in the currency of the ad account, calculated by dividing total live views by total cost.<br> Campaign data for this metric is available starting September 27, 2024. |
| 10_second_live_views | string | 10-second LIVE views | Number of times your LIVE was viewed for at least 10 seconds from the GMV Max Campaign. Multiple views from the same user are counted separately. <br>Campaign data for this metric is available starting September 27, 2024. |
| cost_per_10_second_live_view | string | Cost per 10-second LIVE view | Average cost for each 10-second LIVE view in the currency of the ad account, calculated by dividing total number of 10-second LIVE views by total cost. <br>Campaign data for this metric is available starting September 27, 2024. |
| live_follows | string | LIVE follows | Total number of users who followed your profile during your LIVE.<br> Campaign data for this metric is available starting September 27, 2024. |
```

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/?advertiser_id={{advertiser_id}}&store_ids=["{{store_id}}"]&start_date={{start_date}}&end_date={{end_date}}&dimensions=["room_id","stat_time_day"]&metrics=["live_name", "live_status", "live_launched_time", "live_duration", "cost", "net_cost", "orders", "cost_per_order", "gross_revenue", "roi", "live_views", "cost_per_live_view", "10_second_live_views", "cost_per_10_second_live_view", "live_follows"]&filtering={"campaign_ids":["{{campaign_id}}"]}&page_size=1000&page=1' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "room_id": "{{room_id}}",
                    "stat_time_day": "{{stat_time_day}}"
                },
                "metrics": {
                    "10_second_live_views": "0",
                    "cost": "0.00",
                    "cost_per_10_second_live_view": "0.00",
                    "cost_per_live_view": "0.00",
                    "cost_per_order": "0.00",
                    "gross_revenue": "11.84",
                    "live_duration": "14h 1m",
                    "live_follows": "0",
                    "live_launched_time": "{{live_launched_time}}",
                    "live_name": "{{live_name}}",
                    "live_status": "END",
                    "live_views": "0",
                    "net_cost": "0.00",
                    "orders": "1",
                    "roi": "0.00"
                }
            },
            {
                "dimensions": {
                    "room_id": "{{room_id}}",
                    "stat_time_day": "{{stat_time_day}}"
                },
                "metrics": {
                    "10_second_live_views": "0",
                    "cost": "0.00",
                    "cost_per_10_second_live_view": "0.00",
                    "cost_per_live_view": "0.00",
                    "cost_per_order": "0.00",
                    "gross_revenue": "0.00",
                    "live_duration": "11h 29m",
                    "live_follows": "0",
                    "live_launched_time": "{{live_launched_time}}",
                    "live_name": "{{live_name}}",
                    "live_status": "END",
                    "live_views": "0",
                    "net_cost": "0.00",
                    "orders": "0",
                    "roi": "0.00"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 2,
            "total_page": 1
        }
    }
}
(/code)
```

## Duration-level metrics
These metrics show the basic attributes and performance for each duration of LIVE GMV Max Campaigns created on TikTok Ads Manager or TikTok Seller Center. 
- If the optimization mode remained unchanged throughout the campaign, you will see a single record corresponding to the configured optimization mode.
- If the optimization mode was changed during the campaign, you will see multiple records, with each record corresponding to each optimization mode used.

For example, if you initially set the optimization mode to Target ROI and later switched to Maximum Delivery, you will receive two separate records: one for the period using Target ROI and another for Maximum Delivery.

**Supported dimension groupings**

To retrieve duration-level metrics for LIVE GMV Max Campaigns, specify any of the following dimension groupings using the `dimensions` parameter:
- `["duration"]`
- `["campaign_id","duration"]`
- `["room_id","duration"]`
- `["campaign_id","room_id","duration"]`

**Supported filters**
```xtable
| Filter field{25%} | Required/Optional{15%} | Max size{20%} | Requirement{40%} |
|---|---|---|---|
| campaign_ids | Required | 100 | Specify the ID of a LIVE GMV Max Campaign. <br><br>To obtain such an ID, use the dimension `campaign_id` to query <a href="#item-link-Campaign-level metrics (for LIVE GMV Max)">campaign-level metrics</a> first.  <br><br><p><span style="color:darkred"><b>Note</b></span>: When you filter multiple IDs using this field, you cannot retrieve <a href="#item-link-Attribute metrics">attribute metrics</a>.|
| room_ids | Required   | 100 |Specify the ID of a livestream room used in the LIVE GMV Max Campaign. <br><br>To obtain such an ID, use the dimension room_id to query <a href="#item-link-Livestream-level metrics">livestream-level metrics</a> first.  <br><br><p><span style="color:darkred"><b>Note</b></span>: When you filter multiple IDs using this field, you cannot retrieve <a href="#item-link-Attribute metrics">attribute metrics</a>.|
```

**Supported metrics**
```xtable
| Metric field {25%}| Type{15%} | Description{20%} | Detail {40%}|
|---|---|---|---|
| bid_type | string | Optimization mode | Enum values: <ul><li>`CUSTOM`: Target ROI.</li><li>`NO_BID`: Maximum delivery.</li></ul>|
| cost | string | Cost | The estimated total amount you've spent on your ads during its schedule, in the currency of the ad account. |
| cost_per_order | string | Cost per order (Current shop) | The average cost incurred for each order placed, calculated by dividing the total ad cost by the number of orders generated within the ad schedule. Only orders from your current shop are included, even if the livestream promoted products from other shops. |
| orders | string | Orders (Current shop) | The number of individual paid and organic SKU LIVE orders during the GMV Max campaign. Only orders from your current shop are included, even if the livestream promoted products from other shops. |
| gross_revenue | string | Gross revenue (Current shop) | The total gross revenue of Shop orders attributed to your campaign. It's the amount the user pays, plus all Shop price subsidies provided to the user. Only orders from your current shop are included, even if the livestream promoted products from other shops. |
| roi | string | ROI (Current shop) | The total return on investment (ROI) from all Shop orders attributed to your campaign. Only orders from your current shop are included, even if the livestream promoted products from other shops. |
| all_shops_cost_per_order | double | Cost per order | The average ad cost for each LIVE order placed during this GMV Max Campaign in the currency of the ad account, calculated by dividing total ad cost by the number of orders generated within the ad schedule.<br><br>Campaign data for this metric is available starting September 27, 2024. |
| all_shops_orders | integer | Orders | The number of individual paid and organic SKU LIVE orders during the GMV Max Campaign.<br><br>Campaign data for this metric is available starting September 27, 2024. |
| all_shops_gross_revenue | double | Gross revenue | The total gross revenue of paid and organic TikTok Shop LIVE orders during your GMV Max Campaign, in the currency of the ad account. It's the amount paid by users, plus all TikTok Shop platform subsidies provided to those users.<br><br>Campaign data for this metric is available starting September 27, 2024. |
| all_shops_roi | double | ROI | The total return on investment (ROI) from all paid and organic TikTok Shop LIVE orders during this GMV Max Campaign.<br><br>Campaign data for this metric is available starting September 27, 2024. |
```

**Example**

Request
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/gmv_max/report/get/?advertiser_id={{advertiser_id}}&store_ids=["{{store_id}}"]&start_date={{start_date}}&end_date={{end_date}}&dimensions=["duration"]&metrics=["bid_type", "cost", "orders", "cost_per_order", "gross_revenue", "roi"]&filtering={"campaign_ids":["{{campaign_id}}"],"room_ids":["{{room_id}}"]}&page_size=1000&page=1' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```

Response
```xcodeblock
(code curl http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "dimensions": {
                    "duration": "{{duration}}"
                },
                "metrics": {
                    "bid_type": "CUSTOM",
                    "cost": "763.83",
                    "cost_per_order": "1.43",
                    "gross_revenue": "3749.91",
                    "orders": "535",
                    "roi": "4.91"
                }
            }
        ],
        "page_info": {
            "page": 1,
            "page_size": 1000,
            "total_number": 1,
            "total_page": 1
        }
    }
}
(/code)
```


