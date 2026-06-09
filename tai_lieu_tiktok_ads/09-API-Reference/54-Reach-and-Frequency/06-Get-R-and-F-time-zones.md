---
title: "Get R&F time zones"
doc_id: 1740490147129345
path: "API Reference / Reach & Frequency / Get R&F time zones"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get R&F time zones

Use this endpoint to get the R&F delivery time zone according to location (country code)（for R&F advertising only).

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/rf/delivery/timezone/|/v1.3/rf/delivery/timezone/|
|Request parameter name|`country_codes`|`region_codes`|
|Request parameter data type |`advertiser_id`: number|`advertiser_id`: string|
|Response parameter name|`country_code`|`region_code`|
```

## Request

**Endpoint**  
https://business-api.tiktok.com/open_api/v1.3/rf/delivery/timezone/

**Method** GET

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
| Field | Data Type | Description |
| --- | --- | --- |
|advertiser_id {Required}|string| Advertiser ID |
|region_codes {Required}|string[]| Country codes to be queried. Example:`US` . See [Appendix - Location Code](https://ads.tiktok.com/marketing_api/docs?id=1737585867307010) |
```

### Example

``` xcodeblock
(code curl http)
curl --location --request GET \
  'https://business-api.tiktok.com/open_api/v1.3/rf/delivery/timezone/?advertiser_id={{advertiser_id}}&region_codes=[%22DE%22%2C%22FR%22%2C%22IT%22%2C%22ES%22]' \
  --header 'Access-Token: {{Access-Token}}'
(/code)
```

## Response

``` xtable
| Field | Data Type | Description |
| --- | --- | --- |
| code  | number | Return code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
| message  | string | Return messages. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). | 
| request_id | string | The log id of a request, which uniquely identifies the request. |
| data | object | Returned data. |
#|timezone_info |object[]| Time zone infomation. |
##|region_code |string| Region code. |
##|delivery_timezone |string[]| Supported targeting time zones.  |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "timezone_info": [
            {
                "region_code": "GB",
                "delivery_timezone": [
                    "Etc/GMT-0"
                ]
            },
            {
                "region_code": "US",
                "delivery_timezone": [
                    "Etc/GMT+5"
                ]
            }
        ]
    },
    "request_id": "202011181434430101151531921C36FC53"
}
(/code);
```

