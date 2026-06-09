---
title: "Update the time of a split test"
doc_id: 1742666501911553
path: "API Reference / Super Split Test / Update the time of a split test"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the time of a split test

Use this endpoint to update the start time and end time of a split test.


## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/split_test/update/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required} | string | Advertiser ID.  |
|split_test_group_id {Required} | string | Split test group ID. |
|start_time {Required} | string | The start time of this split test, in the format of YYYY-MM-DD HH:MM:SS (UTC+0). Example: 2022-01-01 00:00:00. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>For <b>campaign level</b> split tests, <ul><li>The time can only be set to hour level. Thus, any MM and SS input will default to 00.</li><li>The start time set for campaign level split test will not overwrite the start time of campaigns and ad groups in this test.</li></ul></ul><ul><li>For <b>ad group level</b> split tests, the start time of the test will be applied to both ad groups under this test group.</li></ul><ul><li>For both campaign and adgroup level split tests, the start time cannot be updated once the test has started. </li></ul>|
|end_time {Required} | string | The end time of this split test, in the format of YYYY-MM-DD HH:MM:SS(UTC+0). Example: 2022-01-01 00:00:00. <br><p><span style="color:darkred"><b>Note</b></span>: <ul><li>For <b>campaign level</b> split tests, <ul><li>The time can only be set to hour level. Thus, any MM and SS input will default to 00.</li><li>The end time set for campaign level split test will not overwrite the end time of campaigns and ad groups in this test.</li></ul></ul><ul><li>For <b>ad group level</b> split tests, the end time of the test will be applied to both ad groups under this test group.</li></ul>|
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/split_test/update/' \
--header 'Access-Token: xx' \
--header 'Content-Type: application/json' \
--data-raw '{
    "advertiser_id": "xx",
    "start_time": "2022-10-21 22:00:00",
    "end_time": "2022-10-22 22:00:00",
    "split_test_group_id": "xxx"
}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
|request_id|string|The log id of a request, which uniquely identifies the request. |
```


### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "xxx",
    "data": {}
}
(/code)
```
