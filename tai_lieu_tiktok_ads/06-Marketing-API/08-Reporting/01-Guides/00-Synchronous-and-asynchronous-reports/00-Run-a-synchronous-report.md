---
title: "Run a synchronous report"
doc_id: 1738864778664961
path: "Marketing API / Reporting / Guides / Synchronous  and asynchronous reports / Run a synchronous report"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Run a synchronous report

You can run a synchronous report to get data in real time. 

# Steps

Use [/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353) and specify the following settings. 

## For Basic, Audience, Playable ad, or DSA reports
When you create a Basic report, Audience report, Playable ad report, or DSA report using [/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353):
- **Advertiser ID**: Specify your advertiser ID in the `advertiser_id` or `advertiser_ids`  field. Note that this is a required field and `advertiser_ids` is only supported for Basic and Audience reports.
- **Service type**: Use the `service_type` field to run a report on auction ad data or auction and reservation ad data (`AUCTION`).  
- **Report type**: Based on the data type that you want to run a report on, you can specify one of the four types in the `report_type` field: basic reports, audience reports, playable ads reports, and DSA reports. Note that this is a required field.                  
- **Data level**: Use the `data_level` field to specify the level of data that you want to get. The following data levels are available: campaign, ad group, ad, and advertiser.<br>The data level that you select must match the dimensions that you use. For example, if you select `AUCTION_CAMPAIGN` as the data level for your report, you must use `campaign_id` as one of your dimensions. 
- **Time range**: If you want to get data in a time range, you need to specify `start_date` and `end_date`. Note that the start date, end date, and data calculations are all based on the ad account time zone.  

> <span style="color:DodgerBlue">**Note**</span><br>
> For basic reports, audience reports, playable ad reports, and DSA reports:<ul><li>If you don't include `stat_time_day` in the value of the `dimensions` field, you can retrieve data for a maximum of <b>365</b> days.</li><li>If you include `stat_time_day` in the value of the `dimensions` field to query daily breakdown data, you can retrieve data for a maximum of <b>30</b> days.</li></ul> 

- **Dimensions**: Use the `dimensions` field to group your data, and you can specify more than one dimension. Different report types support different dimensions, and you can see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) for details. Note that this is a required field. 
- **Metrics**: Use the `metrics` field to specify the metrics that you want to query. Different report types support different metrics, and you can see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) for details.
> <span style="color: DodgerBlue">**Note**</span><br>
 If you want to get the total added-up data for your requested metrics in auction basic reports, enable the `enable_total_metrics` field. 
- **Filters**: If you want to narrow down the scope of returned data, you can use the `filtering` object. Different types of reports support different filters and filter types, and you can see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) for details. 
- **Page size**: The default page size is 10. If your report returns a large number of results, we suggest that you use `page_size` to set a larger page size. 

## For Business Center reports
When you create a Business Center report using [/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353):
- **Business Center ID**: Specify Business Center ID in the `bc_id` field. 
- **Report type**: Specify `BC` in the `report_type` field.
- **Time range**: If you want to get data in a time range, you need to specify `start_date` and `end_date`. Note that the start date, end date, and data calculations are all based on the ad account time zone.

> <span style="color:DodgerBlue">**Note**</span><br>
> Business Center reports support data retrieval for up to 365 days, regardless of whether you include `stat_time_day` in the value of the `dimensions` field. 

- **Dimensions**: Use the `dimensions` field to group your data, and you can specify more than one dimension. Different report types support different dimensions, and you can see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) for details. Note that this is a required field. 
- **Metrics**: Use the `metrics` field to specify the metrics that you want to query. Different report types support different metrics, and you can see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) for details.
- **Filters**: If you want to narrow down the scope of returned data, you can use the `filtering` object. Different types of reports support different filters and filter types, and you can see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) for details. 
- **Page size**: The default page size is 10. If your report returns a large number of results, we suggest that you use `page_size` to set a larger page size. 


## For GMV max ads reports
When you create a GMV max ads report using [/report/integrated/get/](https://business-api.tiktok.com/portal/docs?id=1740302848100353):
- **Advertiser ID**: Specify your advertiser ID in the `advertiser_id` field. 
- **Service type**: Specify `AUCTION` in the `service_type` field.  
- **Report type**: Specify `TT_SHOP` in the `report_type` field.              
- **Data level**: Use the `data_level` field to specify the level of data that you want to get. The following data levels are available: campaign and advertiser.<br>The data level that you select must match the dimensions that you use. For example, if you select `AUCTION_CAMPAIGN` as the data level for your report, you must use `campaign_id` as one of your dimensions. 
- **Time range**: If you want to get data in a time range, you need to specify `start_date` and `end_date`. Note that the start date, end date, and data calculations are all based on the ad account time zone.  

> <span style="color:DodgerBlue">**Note**</span><br>
> For GMV max ads reports:<ul><li>If you don't include `stat_time_day` in the value of the `dimensions` field, you can retrieve data for a maximum of <b>365</b> days.</li><li>If you include `stat_time_day` in the value of the `dimensions` field to query daily breakdown data, you can retrieve data for a maximum of <b>30</b> days.</li></ul> 

- **Dimensions**: Use the `dimensions` field to group your data, and you can specify more than one dimension. Different report types support different dimensions, and you can see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) for details. Note that this is a required field. 
- **Metrics**: Use the `metrics` field to specify the metrics that you want to query. Different report types support different metrics, and you can see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) for details.
> <span style="color: DodgerBlue">**Note**</span><br>
 If you want to get the total added-up data for your requested metrics in GMV max ads reports, enable the `enable_total_metrics` field. 
 
- **Page size**: The default page size is 10. If your report returns a large number of results, we suggest that you use `page_size` to set a larger page size. 


# Example
Request
```xcodeblock
(code)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/report/integrated/get/?advertiser_id={{advertiser_id}}}&service_type=AUCTION&data_level=AUCTION_AD&report_type=BASIC&dimensions=["ad_id"]&metrics=["clicks"]&start_date={{start_date}}}&end_date={{end_date}}}&filtering=[{"field_name":"ad_ids","filter_type":"IN","filter_value":"[\"{{ad_id}}\",\"{{ad_id}}\"]"}]' \
--header 'Access-Token:{{Access-Token}}}'
(/code)
```

Response
```xcodeblock
(code)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "page_info": {
            "total_number": 1,
            "total_page": 1,
            "page_size": 1,
            "page": 1
        },
        "list": [
            {
                "dimensions": {
                    "ad_id": "{{ad_id}}"
                },
                "metrics": {
                    "clicks": "34"
                }
            }
        ]
    }
}
(/code)
```

## More examples
For code examples pertaining to different report types, please refer to the "Use cases" section for each specific report under [Report Types](https://business-api.tiktok.com/portal/docs?id=1738864835805186).

# Use mock data for testing

To make it easier for developers to test our Reporting capabilities, we populated mock data in all sandbox accounts. To learn about how to use the mock data in sandbox accounts and the supported settings, see [Sandbox accounts - Mock data for synchrounous reporting](https://ads.tiktok.com/marketing_api/docs?id=1738855331457026).

