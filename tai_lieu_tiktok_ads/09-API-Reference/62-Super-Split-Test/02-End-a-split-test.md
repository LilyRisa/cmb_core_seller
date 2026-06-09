---
title: "End a split test"
doc_id: 1742666522399746
path: "API Reference / Super Split Test / End a split test"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# End a split test

Use this endpoint to stop an ad group level or campaign level split test. <ul><li>In an <b>ad group</b> level split test, once the test is stopped, the status for both ad groups will be inactive. </li><li>In a <b>campaign level</b> split test, once the test is stopped, campaigns and ad groups will still follow their own schedule, which is independent from the split test schedule.  If you stop a campaign level split test before start time, `split_test_status` will be changed to `CANCELED`.</li></ul>

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/split_test/end/

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
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/v1.3/split_test/end/' \
--header 'Access-Token: xxx' \
--header 'Content-Type: application/json' \
--data-raw '{
    "advertiser_id": "xxx",
    "split_test_group_id": "xx"
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
    "request_id": "xx",
    "data": {}
}
(/code)
```
