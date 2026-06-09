---
title: "Create/Delete an audience segment"
doc_id: 1739940583739393
path: "API Reference / Audience / Audience segment / Create/Delete an audience segment"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create/Delete an audience segment

Use this endpoint to create or delete an audience segment. 

Please see [About Streaming API - Important Notes for Streaming API](https://ads.tiktok.com/marketing_api/docs?id=1739566528222210) to learn about the notes about Multiple Advertisers Associated with a Business Account and Mixed Segments with IDFA/AAID, and [Guidelines for identifier normalization](https://business-api.tiktok.com/portal/docs?id=1850030360880129) to learn about how to normalize your identifiers.

><span style="color:DodgerBlue">**Note**</span><br>To apply for access to the Streaming API (formerly known as Segment API), use [Allowlist Management](https://ads.tiktok.com/marketing_api/blog/detail/7102266224578396162) feature on the Developer Portal by selecting "Streaming API" from the drop down menu. We review applications on an ongoing basis. 

>  <span style="color:DodgerBlue">**Note**</span><br>
>  <ul><li>The rate limit is 1 QPS per advertiser account.</li><li>You can create up to a maximum of 400 audience segments for an advertiser account. Existing audiences need to be deleted prior to new audience creation if the advertiser account reaches this limit. This limit is at advertiser level.</li></ul>


## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/segment/audience/| /v1.3/segment/audience/|
|Request parameter data type|`advertiser_id`: number | `advertiser_id`: string|
|Request parameter name and data type|`delete_id`: number | `delete_audience_id`: string|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/segment/audience/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|Request message type. <br>Allowed format: `application/json`.|
```

**Parameters**

```xtable
|Field|Data Type|Description|
|---|---|---|
|advertiser_id {Required}|string| Advertiser ID. |
|action {Required}|string|Type of action to be performed. Enum values: <ul><li>`create`: To create an audience segment. You will also need to include `custom_audience_name` in the request. </li><li> `delete`: To delete an audience segment. You will also need to include `delete_audience_id` in the request.<br>If no value is specified, the default value is `create`.</li></ul> |
| custom_audience_name {+Conditional} | string  | Custom audience name of audience to be created. Please note that names over 70 characters will be truncated to 67 characters with "..." appended. This field is required when the value of `action` is `create`.|
| delete_audience_id {+Conditional}   | string | Audience ID to be deleted. This field is required when the value of `action` is `delete`. |
```

### Example

```xcodeblock
(code curl http)
curl -verbose  -i --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/segment/audience/' \
--header 'Access-Token: <put_your_access_token_here>' \
--header 'Content-Type: application/json' \
-d '{
    "custom_audience_name": "mens shoes",
    "advertiser_id": 1356789172,
    "action": "create" // create/delete (optional, default is creation)
}'
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code. For the complete list of response codes and descriptions, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message. For details, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data.|
#|audience_id |string|Audience ID. You will use this field when creating audience mappings.  |
|request_id |string|The log id of a request, which uniquely identifies the request.|
```
### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 200,
    "message": "OK",
    "request_id":"XXXXXXXXXXXXXX",
    "data": {
        "audience_id": 100008357
    }
}
(/code);
```




