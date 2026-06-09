---
title: "(To-be-deprecated) Get recommended budgets"
doc_id: 1746463695763458
path: "API Reference / Tools / (To-be-deprecated) Get recommended budgets"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# (To-be-deprecated) Get recommended budgets

> <span style="color:DarkRed">**Important**</span><br>
> This endpoint will be deprecated in the next API version. We recommend that you migrate to [/tool/diagnosis/get/](https://business-api.tiktok.com/portal/docs?id=1738674986981378) and obtain the budget estimated delivery results through the response parameter `budget_edr_info`.

Use this endpoint to get 15 sets of recommended budgets and the corresponding estimated conversion data for each budget on the same day when the budget is adopted. See [here](https://ads.tiktok.com/marketing_api/docs?id=1746463333984258) to find out the preconditions for and the steps of getting budget estimated delivery results.

><span style="color:DodgerBlue">**Note**</span><br><ul><li>Budget estimated delivery results are given according to your ad group setting. If you evaluate multiple ad groups of the same setting via this endpoint , you can only get results for the earliest ad group.</li></ul>

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/delivery/budget/recommend/

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
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/delivery/budget/recommend/?advertiser_id={{advertiser_id}}&adgroup_ids=["{{adgroup_id}}"]' \
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
#| results |object[] | List of ad group IDs and corresponding budget recommendation results.|
##| adgroup_id | string | Ad group ID. |
##| recommendations | object[] | 15 sets of data for recommended budget raise ratios from -20% to 50% with a step of 5%, and corresponding estimated results. |
###| recommended_budget |  float| Recommended budget. Please adopt one suitable budget using [/adgroup/budget/update/](https://ads.tiktok.com/marketing_api/docs?id=1739591133130817) within the same day (advertiser account time zone). |
###| budget_increase_ratio | float | Estimated budget raise ratio. |
###| estimated_conversion | integer | Estimated conversion. |
###| conversion_uplift | integer | Estimated conversion uplift. |
###| conversion_uplift_ratio | float | Estimated conversion uplift ratio. |
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
                        "budget_increase_ratio": -0.2,
                        "conversion_uplift": 0,
                        "recommended_budget": 80.0,
                        "estimated_conversion": 39,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": -0.15,
                        "conversion_uplift": 0,
                        "recommended_budget": 85.0,
                        "estimated_conversion": 40,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": -0.1,
                        "conversion_uplift": 0,
                        "recommended_budget": 90.0,
                        "estimated_conversion": 41,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": -0.05,
                        "conversion_uplift": 0,
                        "recommended_budget": 95.0,
                        "estimated_conversion": 41,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": 0.0,
                        "conversion_uplift": 0,
                        "recommended_budget": 100.0,
                        "estimated_conversion": 41,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": 0.05,
                        "conversion_uplift": 0,
                        "recommended_budget": 105.0,
                        "estimated_conversion": 43,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": 0.1,
                        "conversion_uplift": 0,
                        "recommended_budget": 110.0,
                        "estimated_conversion": 45,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": 0.15,
                        "conversion_uplift": 0,
                        "recommended_budget": 115.0,
                        "estimated_conversion": 46,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": 0.2,
                        "conversion_uplift": 0,
                        "recommended_budget": 120.0,
                        "estimated_conversion": 48,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": 0.25,
                        "conversion_uplift": 0,
                        "recommended_budget": 125.0,
                        "estimated_conversion": 49,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": 0.3,
                        "conversion_uplift": 0,
                        "recommended_budget": 130.0,
                        "estimated_conversion": 51,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": 0.35,
                        "conversion_uplift": 0,
                        "recommended_budget": 135.0,
                        "estimated_conversion": 52,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": 0.4,
                        "conversion_uplift": 0,
                        "recommended_budget": 140.0,
                        "estimated_conversion": 53,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": 0.45,
                        "conversion_uplift": 0,
                        "recommended_budget": 145.0,
                        "estimated_conversion": 54,
                        "conversion_uplift_ratio": 0.0
                    },
                    {
                        "budget_increase_ratio": 0.5,
                        "conversion_uplift": 0,
                        "recommended_budget": 150.0,
                        "estimated_conversion": 55,
                        "conversion_uplift_ratio": 0.0
                    }
                ],
                "adgroup_id": "{{adgroup_id}}"
            }
        ]
    }
}
(/code)
```
