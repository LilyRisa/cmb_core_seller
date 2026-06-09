---
title: "Get benchmarks for a business category"
doc_id: 1822388944791617
path: "API Reference / Accounts / Insights / Get benchmarks for a business category"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get benchmarks for a business category

Use this endpoint to retrieve the benchmarks specific to a business category.

After you obtain the benchmarks for a business category, you can use [/business/get/](https://business-api.tiktok.com/portal/docs?id=1762228399168514) to retrieve the profile data of your Business Account and evaluate its performance against industry standards.


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/business/benchmark/

**Method** GET

**Header**

```xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by TikTok creators.<br>To obtain an access token, use [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041).|
```

**Parameters**

``` xtable
|Field{30%}|Data Type{15%}|Description{55%}|
|--- |--- |--- |
| business_id {Required} | string | Application specific unique identifier for the TikTok account. <br><br>Pass the value of the `open_id` field returned in the response of [/tt_user/oauth2/token/](https://business-api.tiktok.com/portal/docs?id=1833997638479041) to this field.|
| business_category {Required} | string | Business category. <br><br>For enum values, see <a href="#item-link-List of values for business_category">List of values for `business_category`</a>. <br><br>Example: `ART_AND_CRAFTS`. |
```

### List of values for `business_category`
The following table lists the enum values for `business_category` and the corresponding categories.
```xtable
| Value{30%} | Description {60%} |
|---|---|
| `ART_AND_CRAFTS` | Arts & Crafts |
| `AUTOMOTIVE_AND_TRANSPORTATION` | Automative & Transportation |
| `BABY` | Baby |
| `BEAUTY` | Beauty |
| `CLOTHING_AND_ACCESSORIES` | Clothing & Accessories |
| `EDUCATION_AND_TRAINING` | Education & Training |
| `ELECTRONICS` | Electronics |
| `FINANCE_AND_INVESTING` | Finance & Investing |
| `FOOD_AND_BEVERAGE` | Food & Beverage |
| `GAMING` | Gaming |
| `HEALTH_AND_WELLNESS` | Health & Wellness |
| `HOME_FURNITURE_AND_APPLIANCES` | Home, Furniture & Appliances |
| `MACHINERY_AND_EQUIPMENT` | Machinery & Equipment |
| `MEDIA_AND_ENTERTAINMENT` | Media & Entertainment |
| `PERSONAL_BLOG` | Personal Blog |
| `PETS` | Pets |
| `PROFESSIONAL_SERVICES` | Professional Services |
| `PUBLIC_ADMINISTRATION` | Public Adminstration |
| `REAL_ESTATE` | Real Estate |
| `RESTAURANTS_AND_BARS` | Restaurants & Bars |
| `SHOPPING_AND_RETAIL` | Shopping & Retail |
| `SOFTWARE_AND_APPS` | Software & Apps |
| `SPORTS_FITNESS_AND_OUTDOORS` | Sports, Fitness & Outdoors |
| `TRAVEL_AND_TOURISM` | Travel & Tourism |
| `OTHERS` | Others |
```


### Example
```xcodeblock
(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/business/benchmark/?business_id={{business_id}}&business_category=ART_AND_CRAFTS' \
--header 'Access-Token: {{Access-Token}}'
(/code)
```
## Response

``` xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message|string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id |string|The log ID of a request, which uniquely identifies the request.  |
|data |object|Returned data.|
#| business_category | string | Business category.<br><br> Example: `Art & Crafts`.  <br> <br><p><span style="color:darkred"><b>Note</b></span> that the value of this field is different from the value of the `business_category` field in the request. |
#| average_likes | double | Average number of likes on owned videos across Business Accounts in the business category.<br><br> Example: 22618.7106 |
#| average_comments | double | Average number of comments on owned videos across Business Accounts in the business category. <br><br> Example: 625.6003 |
#| average_shares | double | Average number of shares on owned videos across Business Accounts in the business category. <br><br> Example: 1161.3881 |
#| average_video_count | double | Average number of videos across Business Accounts in the business category. <br><br> Example: 138.9494 |
#| average_follower_count | double | Average number of followers across Business Accounts in the business category. <br><br> Example: 1592.5344 |
#| average_follower_growth | double | Average increase in followers over the last 30 days across Business Accounts in business category.<br><br>Example: 24.9218 |
#| average_engagement_rate | double | Average engagement rate across Business Accounts in the business category. <br><br>Example: 0.0449 |
#| average_video_views | double | Average video views across Business Accounts in the business category. <br><br>Example: 353527.9227 |
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
        "average_comments": 625.6003,
        "average_engagement_rate": 0.0449,
        "average_follower_count": 1592.5344,
        "average_follower_growth": 24.9218,
        "average_likes": 22618.7106,
        "average_shares": 1161.3881,
        "average_video_count": 138.9494,
        "average_video_views": 353527.9227,
        "business_category": "Art & Crafts"
    }
}
(/code)
```

