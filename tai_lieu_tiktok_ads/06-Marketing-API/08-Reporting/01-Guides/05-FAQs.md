---
title: "FAQs"
doc_id: 1738864850353154
path: "Marketing API / Reporting / Guides / FAQs"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# FAQs

This article summarizes FAQs about the Reporting API.

## Data discrepancies
Q1: **I notice data discrepancies between API reports and TikTok Ads Manager reports, and why would these discrepancies occur?**
<br>There could be several reasons for the discrepancies you're experiencing. Before reaching out to support, please check the following possibilities on your end:
- Different report types and dimensions: API report types are different from the types on TikTok Ads Manager. For example, the API report is a basic report, while the TikTok Ads Manager report is a "catalog/DPA" report, because TTAM reports include "product name" or other columns that make the report a "catalog/DPA" type.<br> Additionally, it is important to note that there may be discrepancies between the data displayed in the <strong>"View data"</strong> tab on TikTok Ads Manager and the data obtained from API reports. This is because the "View data" tab presents a value of `0` for metrics with no data in each ad, whereas in API reports, certain metrics with no data are filtered out. As a result, API reports may contain fewer ad data compared to the information shown in the "View data" tab on TikTok Ads Manager.<br>
- Data latency in different dimensions: Data latency is different under dimensions. For example, if you include country code as your dimension in basic reports, the data latency is about 11 hours, but the data latency is 30 minutes without country code. See [Data latency](https://ads.tiktok.com/marketing_api/docs?id=1738864894606337) to find out more details.
- Different filters: Ensure the filters are the same via API and TikTok Ads Manager. For example, the `campaign_status`, `adgroup_status` and `ad_status` filters default to `STATUS_NOT_DELETE`, and if you want to query all ads(including deleted ads), you can change the corresponding API filter to `STATUS_ALL` to include ALL statuses.
- Different data levels:  This is often caused by comparing different levels. For example, the API report is the campaign level, but you are looking at and comparing the ad level on TikTok Ads Manager. You can use the `dimension` field in Reporting API to choose the level. 
- Different running times: Reports on API and TikTok Ads Manager are run at different times. If you want to get the accurate data for the T-1 date, you are recommended to pull data after 8:00 (UTC+0) as a data fix script is executed every day at 8:00 (UTC+0). 
- Page size:  We might return multiple pages in API responses.  If you find that you get less data via the [/report/integrated/get/](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353) endpoint than from TikTok Ads Manager, one of the reasons might be that the `page_size` is not large enough. In this case, set `page_size` to a larger number.

Q2: **Why is the request successful but the data returned is null? For example, the `list` object in response is `[]`.**
<br>It might be due to the following reasons: 
- The API report does not return a full list of all ads. If your advertiser stops spending on TikTok during the time specified in the request (`start_date` and `end_date`), then we will only return the data for active ads.
- If the `service_type` in the request does not match your advertiser account type, the report will return a 200 success code but an empty data list (`[]`). For instance, if your advertiser account type is `RESERVATION`, but you specify `AUCTION` as the service type when running a report, the result will be an empty data list.

Q3:  **Why do API reports seem to return data for MORE ads than the actual number of ads created?**
<br>It might be due to the following reason:
- API reports return actual data for all the ads created within the time range (defined by `start_date` and `end_date`) and assign a value of `0` to metrics for all ads created after the `end_date`. For instance, if you created 20 ads between May 1st, 2023, and May 3rd, 2023, and 10 ads on May 4th, 2023, when you pull API reports on May 4th, 2023, you may observe the results for 30 ads instead of 20 ads. This discrepancy does not affect the data for requested metrics, because the additional ten ads will have their metric values set to `0`.

Q4:  **Why do API reports seem to return data for FEWER ads than the actual number of ads created ?**
<br>It might be due to the following reason: 
- API reports filter out some of the ads with no spending (note that whether an ad has spending is determined by an internal algorithim). For instance, if you created ten ads and four of them have no spending, when retrieving data through API reports, you may only retrieve data for eight ads. This is because two of the ads with no spending have been filtered out and for the remaining two, a value of `0` is assigned to the metrics.

Q5: **For campaigns with no available reporting data, such as when no delivering ads exist in the campaign, why does the synchronous report endpoint [/report/integrated/get/](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353) sometimes return a value of `0`, while at other times it returns simply an empty `list`?**
<br>It might be due to the following reason:
- The synchronous report endpoint `/report/integrated/get/` might return a value of `0` or an empty `list` object for metrics with no data due to historical design. When `stat_time_day` is specified in the `dimensions`, an empty `list` object is likely to be returned in the response, because metrics with no data are filtered out. This filtering behavior is intended to indicate that no data has been generated for the requested metrics. Both results essentially convey the same message of no available data.

Q6: **Why is the data returned for the `province_id` dimension in the audience reports not complete, and not the same as the data obtained on TikTok Ads Manager when using the "Subregion" dimension?**

The `province_id` dimension in the audience reports filters out data for some location IDs, which can result in incomplete data. On the other hand, when the "Subregion" dimension is used on TikTok Ads Manager, data for all location IDs is returned. This difference in data is a known issue and will be fixed later.  In the meantime, if you need complete subregion breakdown data, it is recommended to use the "Subregion" dimension on TikTok Ads Manager.

Q7: **Why is the data returned for the dimension related with interest categories in sync reports not complete, and not the same as the data obtained on TikTok Ads Manager when using the "Interest" dimension?**
The `interest_category`, `interest_category_tier2`, `interest_category_tier3`, `interest_category_tier4` dimension in sync reports filter out data for some interest categories, which can result in incomplete data. API does not support all interest categories for now. If you need complete interest category breakdown data, it is recommended to use the "**Interest**" dimension on TikTok Ads Manager.

## Data latency
Q1: **What are the current latency details for report data?**
* For details, see  [Data latency for reports](https://ads.tiktok.com/marketing_api/docs?id=1738864894606337).

## Dimensions and metrics
Q1: **Where can I find the supported metrics under dimensions?**

Click onto the following doc links to find out the supported metrics for dimensions in [basic reports](https://business-api.tiktok.com/portal/docs?id=1759239462689793), [audience reports](https://business-api.tiktok.com/portal/docs?id=1762404685488130), [playable ad reports](https://business-api.tiktok.com/portal/docs?id=1762405483224065), and [Business Center reports](https://business-api.tiktok.com/portal/docs?id=1775747492072449). 

Q2: <b>I received an error message called "Conflict occurs. The input params including metrics and dimensions have no relation." when calling the <a href="https://ads.tiktok.com/marketing_api/docs?id=1740302848100353" target="_blank">/report/integrated/get/</a> endpoint. What are the potential causes?</b>

The potential cause is that there is a conflict between the data level, dimensions or metrics that you specified in your request. For example, if your ad account is running auction campaigns, then `data_level` cannot be  RESERVATION_XXX.  To check the supported metrics under dimensions in different report types, check [basic reports](https://business-api.tiktok.com/portal/docs?id=1759239462689793), [audience reports](https://business-api.tiktok.com/portal/docs?id=1762404685488130), [playable ad reports](https://business-api.tiktok.com/portal/docs?id=1762405483224065), and [Business Center reports](https://business-api.tiktok.com/portal/docs?id=1775747492072449). 

## Report generation

Q1: **Can I run reports for multiple ad accounts in one go?**
* No. You can run reports for only one ad account at a time, and the advertiser ID should match campaign, ad group, or ad IDs.

Q2：**When I make a request to create report, what’s the time zone for times in my request?**
* `start_date` and `end_date` from request parameters are based on the ad account time zone, and the time zone for `create_time` from `filter_name` is UTC+0 by default. 
* For filter field `create_time`, you need to consider the current offset from UTC according to the ad account time zone. The data available to advertisers will be during the converted local time range (requested time + ad account time zone offset). For instance,  if your local time zone is UTC-8 and you want to filter out data after July 1st, 15:00 (local time), you need to set `create_time` as July 1st, 23:00.

## Data incompleteness
Q1: **What’s the 20,000-truncation issue for [synchronous reports](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353)?**
* **Issue**: The reporting endpoints check whether the number of ads under the current account exceeds 20,000. When you want to run a report for over 20,000 ads, only the data of the recent 20,000 ads (according to ad creation time) will be processed, integrated according to integration rules, and only the ads with cost data will be returned to users.<br>In the Response Header, you will see <code>X-Tt-Ads-Throttle:"Number of ad ids is equal or more than 20k, only return the top 20k id's results, sorting by create time desc order."</code>
<br><br>For example, if you attempt to query 30,000 ads where 2,000 have cost data, you may only receive data for 1,356 ads (a number smaller than 2,000). This is because:<ol><li>The 20,000 truncation limit is triggered, causing only the most recent 20,000 ads to be processed. Some ads with cost data may not be returned because they fall within the 10,000 truncated ads.</li><li>Of the remaining 20,000 untruncated ads, only those with cost data (in this case, 1,356) will be returned, while those without cost data (in this case, 8,644) will be filtered out. Note that having a Total Cost greater than 0 is just one of the preconditions for being considered to have cost data.</li></ol>Therefore, when the 20,000 truncation limit is triggered, an ad may not be returned for either of the following reasons:<ul><li>The ad falls within the 10,000 truncated ads, regardless of whether it has cost data or not.</li><li>The ad is untruncated but is filtered out for lacking cost data before being returned.</li></ul>
  
><span style="color:DodgerBlue">**Note**</span><br>
>If you compare the synchronous report with a report downloaded from TTAM (TikTok Ads Manager）, you will discover data inconsistency because the downloaded report should be compared with its counterpart, an asynchronous report, rather than a synchronous report. Both the TTAM downloaded report and the API asynchronous report do not implement the 20,000-truncation for ads.
* **Solution**: If you see <code>x-tt-ads-throttle</code> in the Response Header when running a synchronous report, which means the 20,000-truncation is triggered, you can use more granular filters via the specified <code>filtering</code>. For instance, if you pass <code>ad_status</code> = <code>STATUS_ALL</code> as filter when running a synchronous basic report to get all ads and see <code>x-tt-ads-throttle</code>, you can update the filter as <code>adgroup_ids</code> to narrow down the data scope.
 

## Data limits
Q1: **What are the time range limits for the data we can get via Reporting API?**
* For synchronous reports, the time range limits are:<ul><li> 24 hours if requesting for hourly breakdown data.</li><li>30 days if requesting for daily breakdown data.</li><li>365 days otherwise.</li></ul>
For asynchronous reports, there are no time range limits.

Q2: **What are the rate limits for asynchronous reporting?**
* For asynchronous reporting, the rate limit is 1 QPS per app, and the maximum number of tasks per app per day is 4500. 
* For details about asynchronous reporting, see [Asynchronous reports](https://ads.tiktok.com/marketing_api/docs?id=1738864800380930). 
* For reporting best practices, see [Reporting best practices](https://ads.tiktok.com/marketing_api/docs?id=1738864817670145).

Q3: **What is the data retention period for ads reporting?**
* Report data is retained forever, and there is no query lookback range restriction.


## Audience Report
Q1: **Do interest categories at different levels (tiers 1,2,3 and 4) follow a tree structure? Can the interest category data at different levels for the same account ID be integrated upwards in audience reports?**
* Yes,  interest categories at different levels generally follow a tree structure.
* Yes, data at different interest category levels can be integrated upwards in audience report. For instance, data of level-2 interest categories belonging to the same level-1 interest category can be integrated as level-1 interest category data.

## Creative insights
Q1: **Why is it that only performance data of the first 60 seconds are returned when I use the endpoint [/report/video_performance/get/](https://ads.tiktok.com/marketing_api/docs?id=1738825259075586) to get data for my 65-second video?**
* The endpoint only returns data of 60 seconds at most, and all data after the 60th second will be attributed to the 60th second.

Q2: **What is the difference between Reporting API and Creative Reporting API?**
- Below is a summary of the differences between Reporting and Creative Reporting in three aspects:

<table style="width: 80%">
<colgroup>
<col style="width: 15%">
<col style="width: 35%">
<col style="width: 30%">
</colgroup>
<thead>
  <tr>
    <th colspan="3"; style= "text-align:center">Comparing Reporting and Creative Reporting</th>
  </tr>
</thead>
<tbody>
  <tr>
    <td></td>
    <td>Reporting</td>
    <td>Creative Reporting</td>
  </tr>
  <tr>
    <td rowspan="4">Endpoint</td>
    <td>/report/integrated/get/<br> (Run a synchronous report)</td>
    <td rowspan="4">/creative/report/get/<br> (Run a creative report)</td>
  </tr>
  <tr>
    <td>/report/task/create/<br> (Create an asynchronous report task)</td>
  </tr>
  <tr>
    <td>/report/task/check/ <br>(Get the status of an asynchronous report task)</td>
  </tr>
  <tr>
    <td>/report/task/download/ <br>(Download an asynchronous report)</td>
  </tr>
  <tr>
    <td rowspan="4">Data level</td>
    <td>campaign level</td>
    <td rowspan="4">creative asset level</td>
  </tr>
  <tr>
    <td>ad group level</td>
  </tr>
  <tr>
    <td>ad level</td>
  </tr>
  <tr>
    <td>ad account level</td>
  </tr>
  <tr>
    <td>Grouping</td>
    <td>can group data by <code>dimensions</code> in the request, and different report types support different dimensions. </td>
    <td>cannot group data</td>
  </tr>
</tbody>
</table>

- Number of endpoints. With the four Reporting endpoints, you can run a synchronous report (using [/report/integrated/get/](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353)), create an asynchronous report task (using [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602)), get the status of an asynchronous report task (using [/report/task/check/](https://ads.tiktok.com/marketing_api/docs?id=1740302781443073)), and download an asynchronous report (using [/report/task/download/](https://ads.tiktok.com/marketing_api/docs?id=1740302808815618)). <br>However, to run a creative report you only need to call the Creative Reporting endpoint [/creative/report/get/](https://ads.tiktok.com/marketing_api/docs?id=1740662135093314). 
- Supported data level. When creating a synchronous report or an asynchronous report, you can specify one of the four data levels via `data_level`: campaign level, ad group level, ad level, or ad account level. However, for all creative reports, the data are at the creative asset level by default, and you can specify the material type (`material_type`) you want to run a report on `VIDEO`, `IMAGE`, or `INSTANT_PAGE`. 
- Whether grouping is supported. When using Reporting endpoints [/report/integrated/get/](https://ads.tiktok.com/marketing_api/docs?id=1740302848100353) or [/report/task/create/](https://ads.tiktok.com/marketing_api/docs?id=1740302766489602) to create a report, you can set the grouping conditions via the `dimensions` field. For instance, basic reports support grouping data by `advertiser_id`, `campaign_id`, `adgroup_id`, `ad_id`, `stat_time_day`, `stat_time_hour`, or `country_code` In contrast, the Creative Reporting endpoint [/creative/report/get/](https://ads.tiktok.com/marketing_api/docs?id=1740662135093314) does not support grouping data.

## Miscellaneous
 Q1: **In the response of endpoint [/ad/get/](https://ads.tiktok.com/marketing_api/docs?id=1735735588640770) I only get `app_name` and no `app_id` , and the endpoints for managing app only return `app_id` but no `ad_id` in the response. How can I link the app info for the two endpoints?**
* After you get `app_name` in the response of endpoint  [/ad/get/](https://ads.tiktok.com/marketing_api/docs?id=1735735588640770), you can use the endpoint [/adgroup/get/](https://ads.tiktok.com/marketing_api/docs?id=1739314558673922) to obtain `app_id`. Then pass in `app_id` to a request to [/app/info/](https://business-api.tiktok.com/portal/docs?id=1740859272887297), and in the response `app_name` will be available. Now the app info for the two endpoints is related.
