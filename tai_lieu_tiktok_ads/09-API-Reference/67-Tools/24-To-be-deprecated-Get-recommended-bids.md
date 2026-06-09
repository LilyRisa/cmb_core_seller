---
title: "(To-be-deprecated) Get recommended bids"
doc_id: 1746463732779009
path: "API Reference / Tools / (To-be-deprecated) Get recommended bids"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To-be-deprecated) Get recommended bids

> <span style="color:DarkRed">**Important**</span><br>
> This endpoint will be deprecated in the next API version. We recommend that you migrate to [/tool/diagnosis/get/](https://business-api.tiktok.com/portal/docs?id=1738674986981378) and obtain the bid estimated delivery results through the response parameter `bid_edr_info`.

Use this endpoint to get 9 sets of recommended bids and the corresponding estimated cost data for each bid on the same day when the bid is adopted.

If you want to get a suggested bid value for your ad group based on basic campaign and ad group settings, see [Get suggested bid]( https://ads.tiktok.com/marketing_api/docs?id=1737107845597186).



## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/delivery/bid/recommend/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
| advertiser_id {required} |string| Advertiser ID. |
| adgroup_ids {required} | string[] | Ad group IDs. The number of IDs cannot exceed 20. |
```

### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/delivery/bid/recommend/?advertiser_id={{advertiser_id}}&adgroup_ids=["{{adgroup_id}}"]' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{35%}|Data Type{10%}|Description{55%}|
|---|---|---|
|code |number| Response code. For the complete list of response codes and descriptions, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string| Response message. For details, see [Appendix-Return codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|request_id |string| The log ID of a request, which uniquely identifies the request. |
|data |object| Returned data. |
#| results |object[] | List of ad group IDs and corresponding bid recommendation results.|
##| adgroup_id | string | Ad group ID. |
##| recommendations | object[] | 9 sets of data for recommended bid raise ratios from -10% to 30% with a step of 5% and corresponding estimated results.|
###| recommended_bid | float | Recommended bid. Please adopt one suitable bid using [/adgroup/update/](https://ads.tiktok.com/marketing_api/docs?id=1739586761631745) and pass the adopted bid price to `conversion_bid_price` within the same day (advertiser account time zone). |
###| bid_increase_ratio | float | Estimated bid raise ratio. |
###| estimated_cost | float | Estimated cost. |
###| cost_uplift | float | Estimated cost uplift. |
###| cost_uplift_ratio | float | Estimated cost uplift ratio. |
```
### Example
```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "results": [
            {
                "recommendations": [
                    {
                        "cost_uplift_ratio": 0.83,
                        "estimated_cost": 553.43,
                        "recommended_bid": 0.63,
                        "bid_increase_ratio": 0.25,
                        "cost_uplift": 251.4
                    },
                    {
                        "cost_uplift_ratio": 0.47,
                        "estimated_cost": 444.8,
                        "recommended_bid": 0.57,
                        "bid_increase_ratio": 0.15,
                        "cost_uplift": 142.36
                    },
                    {
                        "cost_uplift_ratio": -0.14,
                        "estimated_cost": 261.17,
                        "recommended_bid": 0.48,
                        "bid_increase_ratio": -0.05,
                        "cost_uplift": -42.4
                    },
                    {
                        "cost_uplift_ratio": -0.27,
                        "estimated_cost": 222.25,
                        "recommended_bid": 0.45,
                        "bid_increase_ratio": -0.1,
                        "cost_uplift": -81.78
                    },
                    {
                        "cost_uplift_ratio": 0.0,
                        "estimated_cost": 302.89,
                        "recommended_bid": 0.5,
                        "bid_increase_ratio": 0.0,
                        "cost_uplift": 0
                    },
                    {
                        "cost_uplift_ratio": 0.15,
                        "estimated_cost": 347.4,
                        "recommended_bid": 0.53,
                        "bid_increase_ratio": 0.05,
                        "cost_uplift": 45.43
                    },
                    {
                        "cost_uplift_ratio": 1.02,
                        "estimated_cost": 611.93,
                        "recommended_bid": 0.65,
                        "bid_increase_ratio": 0.3,
                        "cost_uplift": 308.95
                    },
                    {
                        "cost_uplift_ratio": 0.64,
                        "estimated_cost": 497.73,
                        "recommended_bid": 0.6,
                        "bid_increase_ratio": 0.2,
                        "cost_uplift": 193.85
                    },
                    {
                        "cost_uplift_ratio": 0.3,
                        "estimated_cost": 394.72,
                        "recommended_bid": 0.55,
                        "bid_increase_ratio": 0.1,
                        "cost_uplift": 90.87
                    }
                ],
                "adgroup_id": "adgroup_id"
            }
        ]
    }
}
(/code)
```
