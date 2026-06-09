---
title: "Download leads"
doc_id: 1739053563877378
path: "API Reference / Leads / Download leads"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Download leads

Use this endpoint to download leads. You need to create a download task through [/page/lead/task/](https://ads.tiktok.com/marketing_api/docs?id=1739052936772610) and ensure the task is complete first (`status` = `SUCCEED`).

- If the call is successful, the data (UTC+0 Time) is in CSV (original CSV file < = 10MB) or zip (original CSV file > 10MB) format instead of JSON format.
- If the endpoint produces an error, it may return a response in JSON format or output an error message in the returned CSV file. This depends on the error type.

>  <span style="color:DodgerBlue">**Note**</span><br> To protect customer information, only ad account admins can create and query lead download tasks or download the leads. Ad account roles are configured in the Business Center.<br><br>If leads are migrated to a Business Center, only Admins of the Business Center can download the leads.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/pages/leads/task/download/|/v1.3/page/lead/task/download/|
|Request parameter data type |`advertiser_id`:number<br>`library_id `:number|`advertiser_id`: string<br>`library_id `:string|
```

## Request
**Endpoint** 
- **For leads data in EEA/CH/UK**: `https://business-api.tiktok.com/open_api/v1.3/page/lead/task/download/` and the header `x-lead-region: eu`
<br>**Example**

  ```xcodeblock
	(code curl http)
  curl --location 'https://business-api.tiktok.com/open_api/v1.3/page/lead/task/download/?advertiser_id={{advertiser_id}}&task_id={{task_id}}' \
  --header 'Access-Token: ACCESS_TOKEN' \
  --header 'x-lead-region: eu'
	(/code)
	```

> <span style="color: DodgerBlue">**Note**</span><br>
 The new URL with the `eu` header above is effective from July 13th (UTC+0), 2023. After July 13th (UTC+0), if you continue using the old URL `https://ads.us.tiktok.com/open_api/`, you will only receive US leads. If you use the URL `https://business-api.tiktok.com/open_api/` without specifying the region in the header, you will only receive leads from other countries/regions.

- **For leads data in US:** 
  -   `https://ads.us.tiktok.com/open_api/v1.3/page/lead/task/download/` (planned to be deprecated in future versions)
  - **[Or]** `https://business-api.tiktok.com/open_api/v1.3/page/lead/task/download/` with the header `x-lead-region: us`.
  
- **For leads data in other countries/regions**: `https://business-api.tiktok.com/open_api/v1.3/page/lead/task/download/`

**Method** GET

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**
```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {+Conditional}|string| If leads are under an ad account, you must specify the advertiser ID.|
|library_id {+Conditional}|string|If leads are under a Business Center, you must specify the ID of the form library that contains the leads.|
|task_id {Required}|string| Task ID |
```

### Example
```xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "task_id=TASK_ID" \
https://ads.tiktok.com/open_api/v1.3/page/lead/task/download/
(/code)
```

## Response
If the endpoint produces an error, it may return a response in JSON format or output an error message in the returned CSV file. This depends on the error type.

### Example
Success
```xcodeblock
(code Success CSV)
lead id,created_time,ad_id,ad_name,adgroup_id,adgroup_name,campaign_id,campaign_name,form_id,form_name,Name,First name,Surname,Gender,Birthday,Phone number,Email,Country,Province/State,City,Address,Please select the service you are interested in?)
6864523782069944581,2020-06-24 12:18:11(UTC+00:00),1674635134905355,,0,,0,,6856667798307864597,test,test,joe,smith,male,1998.02.22,17681120000,test@foo.bar,china,shanghai,shanghai,somewhere,my name is test
6864523781998641414,2020-06-24 12:18:11(UTC+00:00),1674635134905355,,0,,0,,6856667798307864597,test,test,joe,smith,male,1998.02.22,17681120000,test@foo.bar,china,shanghai,shanghai,somewhere,my name is test
(/code)
```
Failure
```xcodeblock
(code Fail JSON)
{
    "message": "failed to download.",
    "code": 40000,
    "data": {}
}
(/code)
```
