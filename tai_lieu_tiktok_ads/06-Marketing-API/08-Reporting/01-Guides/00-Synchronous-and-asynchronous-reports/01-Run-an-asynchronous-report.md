---
title: "Run an asynchronous report"
doc_id: 1738864800380930
path: "Marketing API / Reporting / Guides / Synchronous  and asynchronous reports / Run an asynchronous report"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Run an asynchronous report

You can run a report in an asynchronous mode when you’re not in a time-sensitive environment. Asynchronous reports may take a bit longer to complete, but they do not time out. With asynchronous reports, you can get data for any time frame.

> <span style="color: DodgerBlue">**Note**</span><br>Asynchronous report is currently an allowlist-only feature. If you would like to access it, please apply for the allowlisting using the [Allowlist Management](https://ads.tiktok.com/marketing_api/blog/detail/7102266224578396162) page.

# Steps
Please perform the following steps to get asynchronous reports.
1. **Use the [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602) endpoint** to create an asynchronous report task. You need to specify the following settings:
- **Advertiser ID**: Specify your advertiser ID in the `advertiser_id` field. Note that this is a required field. 
- **Service type**: Use the `service_type` field to run a report on auction ad data or auction and reservation ad data (`AUCTION`).  
- **Report type**: Based on the data type that you want to run a report on, you can specify one of the four types in the `report_type` field: basic reports, audience reports, playable ads reports, and DSA reports. Note that this is a required field. 

> <span style="color: DodgerBlue">**Note**</span><br>
Playable ads reports cannot be obtained asynchronously.    

- **Data level**: Use the `data_level` field to specify the level of data that you want to get. The following data levels are available: campaign, ad group, ad and advertiser.<br><br>The data level that you select must match the dimensions that you use. For example, if you select `AUCTION_CAMPAIGN` as the data level for your report, you must use `campaign_id` as one of your dimensions. 
- **Time range**: If you want to get data in a time range, you need to specify `start_date` and `end_date`. Note that the start date, end date, and data calculations are all based on the ad account time zone.  
> <span style="color:DodgerBlue">**Note**</span><br>
> Asynchronous reports have no time range limitations.
- **Dimensions**: Use the `dimensions` field to group your data, and you can specify more than one dimension. Different report types support different dimensions, and you can see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) for details. Note that this is a required field. 
> <span style="color:DodgerBlue">**Note**</span><br>
> The dimension and metric combinations that are supported may vary between synchronous and asynchronous reports. Therefore, a valid dimension and metric combination in synchronous reports may not be supported in asynchronous reports.
- **Metrics**: Use the `metrics` field to specify the metrics that you want to query. Different report types support different metrics, and you can see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) for details.
- **Filters**: If you want to narrow down the scope of returned data, you can use the `filtering` object. Different types of reports support different filters and filter types, and you can see the corresponding articles under [Report types](https://ads.tiktok.com/marketing_api/docs?id=1738864835805186) for details. 
> <span style="color:DodgerBlue">**Note**</span><br>
> In asynchronous mode for the report types of basic reports, audience reports, DSA reports, and reservation ad reports,  the only supported filter fields are `campaign_ids`, `adgroup_ids`, and `ad_ids`.
- **File format**: Use `output_format` to determine your output format. The available formats are: `CSV_STRING`, `CSV_DOWNLOAD` and `XLSX_DOWNLOAD`. If you choose `CSV_DOWNLOAD` and `XLSX_DOWNLOAD`, we will return a download link in response. 
2. **Use the [/report/task/check/](https://ads.tiktok.com/marketing_api/docs?id=1740302781443073) endpoint** to check whether the task has been completed.
3. **Use the [/report/task/download/](https://ads.tiktok.com/marketing_api/docs?id=1740302808815618) endpoint** to download the output of the task when the task has been completed.

# Example

Below is an example of the [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602) endpoint.

Request
```xcodeblock
(code)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/report/task/create/' \
--header 'Access-Token: {{access-token}}' \
--data-raw '{
    "advertiser_id": "{{advertiser_id}}",
    "data_level":"AUCTION_AD",
    "report_type":"BASIC",
    "dimensions": ["ad_id"],
    "metrics": ["clicks"],
    "start_date": "{{start_date}}",
    "end_date": "{{end_date}}",
    "output_format": "CSV_DOWNLOAD",
    "file_name": "{{file_name}}",
    "filtering": [
        {
        "field_name":"ad_ids",
        "filter_type":"IN",
        "filter_value": "[\"{{ad_id}}\",\"{{ad_id}}\"]"
        }
    ]
}'
(/code)
```
Response
```xcodeblock
(code)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "task_id": "{{task_id}}"
    },
    "request_id": "{{request_id}}"
}
(/code)
```


