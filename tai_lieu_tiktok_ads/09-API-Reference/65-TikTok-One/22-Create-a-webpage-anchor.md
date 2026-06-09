---
title: "Create a webpage anchor"
doc_id: 1826564934723585
path: "API Reference / TikTok One / Create a webpage anchor"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create a webpage anchor

Use this endpoint to create a webpage anchor of the web destination link type.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/tto/tcm/anchor/create/

**Method** GET

**Header**

```xtable
|Field{28%}|Data Type{22%}|Description{50%}|
|---|---|---|
|Access-Token {Required}|string|Access token authorized by a TikTok One Creator Marketplace account. <br>For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1825117875361793).|
|Content-Type {Required}|string|Request message type.<br><ul><li>If `upload_type` is `UPLOAD_BY_FILE`, use `multipart/form-data`.</li><li>If `upload_type` is `UPLOAD_BY_URL`, use `application/json`.</li></ul> |
```


**Parameters**

``` xtable
|Field{28%}|Data Type{22%}|Description{50%}|
|--- |--- |--- |
| tto_tcm_account_id {Required} | string | The ID of the TTO Creator Marketplace account.<br><br>To obtain the IDs of the accounts that you can access via `Access-Token`, use [/tto/oauth2/tcm/](https://business-api.tiktok.com/portal/docs?id=1815693478150146). |
|anchor_type {Required}|string|The type of anchor.<br><br>Enum values:<ul><li>`WEB_ANCHOR`: Webpage anchor. A type of anchor through which you can display your website link above the creator's video description.</li></ul>|
|anchor_sub_type {+Conditional}|string|Required when `anchor_type` is `WEB_ANCHOR`.<br><br> The specific type of webpage anchor.<br><br>Enum values:<ul><li>`DESTINATION`: Web destination link. This type directs viewers to a web destination link that can be opened in TikTok. The anchor shows up both in the creator's video and the comment section.<br><br><span style="color:darkred"><b>Note</b></span>: The web destination link is available for countries where TikTok Shop is not yet available. TikTok Shop is currently available in Indonesia, Thailand, Vietnam, Philippines, Malaysia, Singapore, United States, United Kingdom, and Saudi Arabia.</li></ul>|
|category_label_id {+Conditional}|number|Required when `anchor_sub_type` is `DESTINATION`. <br><br>The ID of the category of your product or service.<br><br>To learn about the supported values, see <a href="#item-link-Supported values for category_label_id">Supported values for `category_label_id`</a>.|
|country_code {Required}|string|The code of the country or region. <br>The app information from this country or region's app store will be used. This anchor won’t be available in other countries or regions.<br><br>When `anchor_sub_type` is `DESTINATION`, the supported values vary based on `category_label_id`. To learn about the supported values for a specific category ID, see <a href="#item-link-Supported values for category_label_id">Supported values for `category_label_id`</a>. <br><br>To learn about the country or region that the code represents, see [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010).<br><br>Example: `AU`.|
|call_to_action {Required}|object|Anchor link information.|
#|landing_page_url {+Conditional}|string|Required when `anchor_sub_type` is `DESTINATION`. <br><br>The URL of the page that the anchor will redirect viewers to. This page shows more information about your product or service.<br>Ensure that the link provided is a product or service detail page. Otherwise, the anchor may be rejected during review.|
|anchor_title {Required}|string|The title of the anchor.<br><br>When `anchor_sub_type` is `DESTINATION`, the enum values are:<ul><li>`WATCH_NOW`: Watch Now.</li><li>`LISTEN_NOW`: Listen Now.</li><li>`READ_MORE`: Read More.</li><li>`SHOW_NOW`: Shop Now.</li><li>`GET_OFFER`: Get Offer.</li><li>`LEARN_MORE`: Learn More.</li><li>`CONTACT_US`: Contact Us.</li><li>`JOIN_NOW`: Join Now.</li><li>`APPLY_NOW`: Apply Now.</li></ul>|
|anchor_name {Required}|string|The name of the anchor.<br><br>Length limit: 32 characters.<br><br>This information will not be shown to users in TikTok App.|
|upload_type {Required}|string|The upload method for thumbnail.<br><br>Enum values:<ul><li>`UPLOAD_BY_FILE`: Upload by file. You need to specify `thumbnail_file` simultaneously.</li><li>`UPLOAD_BY_URL`: Upload by URL. You need to specify `thumbnail_url` simultaneously.</li></ul>|
|thumbnail_file {+Conditional}|file|Required when `upload_type` is `UPLOAD_BY_FILE`. <br><br>The thumbnail file for the anchor.<br><br>Specification requirements: <ul><li>File size: within 2 MB.</li><li>Format: JPEG, PNG, and GIF.</li><li>Resolution: 210 (width) x 375 (height) pixels.</li></ul><br>This information will not be shown to users in TikTok App.|
|thumbnail_url {+Conditional}|string|Required when `upload_type` is `UPLOAD_BY_URL`. <br><br>A publicly accessible thumbnail URL for the anchor.<br><br>Specification requirements: <ul><li>File size: within 2 MB.</li><li>Format: JPEG, PNG, and GIF.</li><li>Resolution: 210 (width) x 375 (height) pixels.</li></ul><br>This information will not be shown to users in TikTok App.|
```
### Supported values for `category_label_id`

```xtable
|Category{28%}|Corresponding <code>category_label_id</code> value{22%}|Available <code>country_code</code> value{50%}|
|---|---|---|
|Automotive & Motorcycle|`3`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Baby & Nursery|`13`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Beauty & Personal Care|`16`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Books, Magazines & Audio|`31`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Collectibles|`33`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Computers & Office Equipment|`7`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Entertainment & media|`27`|`AU`, `BR`, `CA`, `DE`, `ES`, `FR`, `GB`, `ID`, `IT`, `JP`, `KR`, `MX`, `MY`, `PH`, `SG`, `TH`, `TW`, `US`, `VN`|
|Fashion Accessories|`23`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Finance|`20`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Food & Beverages|`10`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Furniture|`1`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Health|`25`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Home Improvement|`24`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Home Supplies|`5`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Household Appliances|`14`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Jewelry Accessories & Derivatives|`32`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Kid's Fashion|`2`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Kitchenware|`21`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Luggage & Bags|`30`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Menswear & Underwear|`8`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Muslim Fashion|`34`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Others|`29`|`AU`, `BR`, `CA`, `DE`, `ES`, `FR`, `GB`, `ID`, `IT`, `JP`, `KR`, `MX`, `MY`, `PH`, `SG`, `TH`, `TW`, `US`, `VN`|
|Pet Supplies|`9`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Phones & Electronics|`22`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Pre-Owned|`11`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Restaurant & bar|`26`|`AU`, `BR`, `CA`, `DE`, `ES`, `FR`, `GB`, `ID`, `IT`, `JP`, `KR`, `MX`, `MY`, `PH`, `SG`, `TH`, `TW`, `US`, `VN`|
|Service|`12`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Shoes|`17`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Sports & Outdoor|`35`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Textiles & Soft Furnishings|`15`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Tools & Hardware|`19`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Toys & Hobbies|`18`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Travel & hotel|`28`|`AU`, `BR`, `CA`, `DE`, `ES`, `FR`, `GB`, `ID`, `IT`, `JP`, `KR`, `MX`, `MY`, `PH`, `SG`, `TH`, `TW`, `US`, `VN`|
|Virtual Products|`6`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
|Womenswear & Underwear|`4`|`AE`, `AR`, `AT`, `AU`, `BD`, `BE`, `BR`, `CA`, `CH`, `CL`, `CO`, `CZ`, `DE`, `DK`, `EC`, `EG`, `ES`, `FI`, `FR`, `GR`, `HU`, `IE`, `IL`, `IQ`, `IT`, `JP`, `KH`, `KR`, `KZ`, `MA`, `MX`, `NL`, `NO`, `NZ`, `PE`, `PK`, `PL`, `PT`, `RO`, `SE`, `TR`, `ZA`|
```

### Example
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/tto/tcm/anchor/create/' \
--header 'Access-Token: {{access_token}}' \
--header 'Content-Type: application/json' \
--data '{
    "tto_tcm_account_id": "{{tto_tcm_account_id}}",
    "anchor_type": "WEB_ANCHOR",
    "anchor_sub_type": "DESTINATION",
    "category_label_id": 3,
    "country_code": "AU",
    "call_to_action": {
        "landing_page_url": "{{landing_page_url}}"
    },
    "anchor_title": "WATCH_NOW",
    "anchor_name": "WEB_DESTINATION",
    "upload_type": "UPLOAD_BY_URL",
    "thumbnail_url": "{{thumbnail_url}}"
}'
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
#|anchor_id|string|The ID of the anchor.|
#|status|string|The status of the anchor.<br><br>Enum value: <ul><li>`IN_REVIEW`: under review.</li></ul>|
#|anchor_type|string|The type of anchor.<br><br>Enum value: <ul><li>`WEB_ANCHOR`: Webpage anchor. An anchor type through which you can display your website link above the creator's video description.</li></ul>|
#|anchor_sub_type|string|The specific type of webpage anchor.<br><br>Enum value: <ul><li>`DESTINATION`: Web destination link. This type directs viewers to a web destination link that can be opened in TikTok. The anchor shows up both in the creator's video and the comment section.</li></ul>|
#|category_label_id|number|The ID of the category of your product or service.<br><br>To learn about the supported values, see <a href="#item-link-Supported values for category_label_id">Supported values for `category_label_id`</a>.|
#|country_code|string|The code of the country or region. <br>The app information from this country or region's app store will be used. This anchor won’t be available in other countries or regions.<br><br>To learn about the country or region that the code represents, see [Location code](https://business-api.tiktok.com/portal/docs?id=1737585867307010).<br><br>Example: `AU`.|
#|call_to_action|object|Anchor link information.|
##|landing_page_url|string|The URL of the page that the anchor will redirect viewers to. This page shows more information about your product or service.|
#|anchor_title|string|The title of the anchor.|
#|anchor_name|string|The name of the anchor.|
#|anchor_sub_title|string|When `anchor_sub_type` is `DESTINATION`, this field returns an empty string (`""`). <br><br>The title of the anchor, which is used to tell TikTok users what you're promoting about your product or service.|
#|thumbnail_url|string|The thumbnail URL for the anchor. <br><br>The URL won't expire.|
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
        "anchor_id": "{{anchor_id}}",
        "anchor_name": "WEB_DESTINATION",
        "anchor_sub_title": "",
        "anchor_sub_type": "DESTINATION",
        "anchor_title": "Watch Now",
        "anchor_type": "WEB_ANCHOR",
        "call_to_action": {
            "landing_page_url": "{{landing_page_url}}"
        },
        "category_label_id": 3,
        "country_code": "AU",
        "status": "IN_REVIEW",
        "thumbnail_url": "{{thumbnail_url}}"
    }
}
(/code)
```
