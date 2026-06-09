---
title: "Run the winning ad group"
doc_id: 1742666543736834
path: "API Reference / Super Split Test / Run the winning ad group"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Run the winning ad group

Use this endpoint to run the winning ad group in your ad group level split test. Once you make this call, the other ad group in your test will be paused and become inactive, while the budget of the winning ad group will be doubled. 
Before you start, use  [/split_test/result/get/](https://ads.tiktok.com/marketing_api/docs?id=1742666557045761)  to get `metric` and its corresponding `p_value` . There will be a winning ad group when P value <=0.1.
> <span style="color: DodgerBlue">**Note**</span><br>Currently, this endpoint does not support campaign level split test.

## Request

**Endpoint**

https://business-api.tiktok.com/open_api/v1.3/split_test/promote/

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
|winning_object_id {Required} | string | The winning ad group ID. You can use  [/split_test/result/get/](https://ads.tiktok.com/marketing_api/docs?id=1742666557045761)  to get `metric` and  its corresponding`p_value`. There will be a winning ad group when P value <=0.1. <br><p><span style="color:darkred"><b>Note</b></span>: <b>Do not</b> specify this field for <b>campaign level</b> split test. Otherwise, an error will occur.|
```

### Example

```xcodeblock
(code curl http)
curl --location --request POST https://business-api.tiktok.com/open_api/v1.3/split_test/promote/' \
--header 'Content-Type: application/json' \
--header 'Access-Token: xxx' \
--data-raw '{
    "advertiser_id": "xx",
    "split_test_group_id": "xx",
    "winning_object_id": "xx"
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
