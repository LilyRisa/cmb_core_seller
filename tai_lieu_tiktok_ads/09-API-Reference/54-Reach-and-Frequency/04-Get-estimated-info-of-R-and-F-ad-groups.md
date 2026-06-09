---
title: "Get estimated info of R&F ad groups"
doc_id: 1740489551354881
path: "API Reference / Reach & Frequency / Get estimated info of R&F ad groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get estimated info of R&F ad groups

Use this endpoint to query the estimated daily cost and frequency distribution. If the adgroup does not have a valid estimate, no corresponding record will be returned.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/adgroup/rf/estimated/info/|/v1.3/adgroup/rf/estimated/info/|
|Request parameter data type |`advertiser_id`: number<br>`adgroup_ids`: number[]|`advertiser_id`: string<br>`adgroup_ids`: string[]|
|Response parameter data type|`adgroup_id`: number|`adgroup_id`: string|
```

## Request

**Endpoint**  
https://business-api.tiktok.com/open_api/v1.3/adgroup/rf/estimated/info/

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
|advertiser_id {Required}|string| Advertiser id |
|adgroup_ids {Required}|string[]| Ad group ids |
```

### Example

``` xcodeblock
(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
--data-urlencode "adgroup_ids=[\"ADGROUP_IDS\"]" \
https://business-api.tiktok.com/open_api/v1.3/adgroup/rf/estimated/info/
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
#|estimated_info |object[]| Estimated information. |
##|adgroup_id |string|Ad group ID.|
##|base_info |object| Basic information.  |
###|budget |float| Total budget. |
###|cpm |float| Cost per mille. |
###|impression |number| Total impressions, <b>in the unit of 1,000</b>. |
###|reach |number| Number of people reached, <b>in the unit of 1,000</b>. |
###|average_frequency |float| impressions per capita = total impression/ number of people reached. |
##| daily_cost | object | Daily cost. |
###| cost | float | Cost for the day. |
###| date | string | Date. |
##| frequency_per_person | object | Distribution of impressions per capita. |
###| frequency | number | Impressions per capita. |
###| percentage | float | Percentage of current impressions to total impressions. |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {
        "estimated_info": [
            {
                "adgroup_id": 123,
                "daily_cost": [
                    {
                        "date": "2020-08-07",
                        "cost": 3587.5169107559
                    },
                    {
                        "date": "2020-08-08",
                        "cost": 3565.1767601342
                    }
                ],
                "frequency_per_person": [
                    {
                        "percentage": 0.28,
                        "frequency": 1
                    },
                    {
                        "percentage": 0.72,
                        "frequency": 2
                    }
                ]
            }
        ]
    }
    "request_id": "8ED342"
}
(/code);
```
