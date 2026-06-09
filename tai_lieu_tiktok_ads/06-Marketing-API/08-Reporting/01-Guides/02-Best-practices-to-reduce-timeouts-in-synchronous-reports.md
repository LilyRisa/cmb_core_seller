---
title: "Best practices to reduce timeouts in synchronous reports"
doc_id: 1738864817670145
path: "Marketing API / Reporting / Guides / Best practices to reduce timeouts in synchronous reports"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Best practices to reduce timeouts in synchronous reports

Due to throughput limits, a synchronous request for getting a large amount of data may time out. The following best practices help you reduce or avoid time-out issues.  




# Use filters to get data in smaller batches


- Use `campaign_ids`, `adgroup_ids`, `ad_ids` as filters (`filtering`) to get data in smaller batches. You can specify up to 100 IDs as filters in one request (Note: If it is campaign ID, then the threshold is 30). This best practice especially works for audience reports. 


> <span style="color: DodgerBlue">**Note**</span><br>If you are running a report at the ad level, you need to add `campaign_ids` as a filter. No more than 30 campaign IDs should be added. 

- Use `campaign_status`, `adgroup_status`, `ad_status`, or `playable_status` as filters (`filtering`) to reduce system load and achieve better overall efficiency, and avoid specifying `STATUS_ALL`, `STATUS_DELETE` or `STATUS_DISABLE` as values in these status filters. 

## Example
Below is an example of how to use filters to reduce the amount of returned data. 

### Incorrect example
The following request returns thousands of ad group IDs, which not only causes timeouts but also affects the overall performance of our Reporting products. 

```xcodeblock
(code)
{
    "advertiser_id": "xxx",
    "report_type": "AUDIENCE",
    "dimensions": "[\"adgroup_id\", \"interest_category_tier2\"]",
    "data_level": "AUCTION_ADGROUP",
    "start_date": "2023-01-16",
    "end_date": "2023-01-16",
    "metrics": "[\"spend\", \"cost_per_result\", \"ctr\", \"clicks\", \"campaign_id\", \"cpc\", \"conversion_rate\", \"impressions\", \"cost_per_conversion\", \"conversion\"]",
    "page_size": "1000",
}
(/code)
```

### Correct example
To solve the issue, you can add `campaign_ids` as filters to only get ad groups under the specified campaigns. 
1. Use [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986) to get the campaigns under your advertiser ID. 
2. Use [/adgroup/get/](https://ads.tiktok.com/marketing_api/docs?id=1739314558673922) to get the ad groups under a campaign, and check if they are the ad groups that you want to report.
3. Based on the above information, specify the `campaign_ids` as filters to reduce the amount of returned data. 

```xcodeblock
(code)
{
    "advertiser_id": "xxx",
    "report_type": "AUDIENCE",
    "dimensions": "[\"adgroup_id\", \"interest_category_tier2\"]",
    "data_level": "AUCTION_ADGROUP",
    "start_date": "2023-01-16",
    "end_date": "2023-01-16",
    "metrics": "[\"spend\", \"cost_per_result\", \"ctr\", \"clicks\", \"campaign_id\", \"cpc\", \"conversion_rate\", \"impressions\", \"cost_per_conversion\", \"conversion\"]",
    "page_size": "1000",
    "filters": "[{\"field_name\":\"campaign_ids\",\"filter_type\":\"IN\",\"filter_value\":\"[xxxxx, xxxxx]\"}]"
}
(/code)
```

In the above sample code, we add two campaign IDs in `filter_value`, and less than 10 ad group IDs are returned. No timeouts occur during the process. 

# Use time range to narrow down results
Specify `start_date` and `end_date` to narrow down your time range, and the time difference between the start date and end date should not exceed seven days. Though you have to query data in smaller batches, time-out errors will be prevented, especially for audience reports. 

# Reduce the number of metrics
The number of metrics that you want to query should be no more than 100. This best practice especially works for audience reports.

# Don't retry the same requests if timeouts occur
If time-out issues occur, please do not retry with the same request. You should adopt the following best practices, for example, using a filter or time range, to update your request and try again. 

# Ensure that the advertiser ID matches campaign, ad group, or ad IDs

For users that manage multiple advertiser IDs, the `advertiser_id` you specified must match the campaign, ad group, or ad IDs that you want to query in your report. Otherwise, no data will be returned. Enumerating the combinations of advertiser IDs and different levels of ad IDs is a misuse of the Reporting endpoint, and a waste of resources.

We recommend that you use the [/campaign/get/](https://ads.tiktok.com/marketing_api/docs?id=1739315828649986), [/adgroup/get/](https://ads.tiktok.com/marketing_api/docs?id=1739314558673922), or [/ad/get/](https://ads.tiktok.com/marketing_api/docs?id=1735735588640770) endpoint to get campaign, ad group, or ad IDs for an advertiser ID, and then pass in the advertiser ID and the correct campaign, ad group, or ad IDs to run a report.


# Pull the previous day's audience data after 9 a.m.
The latency for audience data is 10-12 hours. To pull the audience data for the previous day, you are recommended to run the audience report later than 9 a.m. the next day (advertiser time zone).

# Run an asynchronous report

Asynchronous reports may take a bit longer to complete, but they do not time out. With asynchronous reports, you can get data for any time frame. For asynchronous reports, the rate limit is 1 QPS per app, and the total number of tasks per app per day is 4500. 

To learn about how to run an asynchronous report, see [Run an asynchronous report](https://ads.tiktok.com/marketing_api/docs?id=1738864800380930).



