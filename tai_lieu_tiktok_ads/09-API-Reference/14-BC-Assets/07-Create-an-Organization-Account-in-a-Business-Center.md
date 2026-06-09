---
title: "Create an Organization Account in a Business Center"
doc_id: 1855027199571138
path: "API Reference / BC Assets / Create an Organization Account in a Business Center"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Create an Organization Account in a Business Center

Use this endpoint to create an Organization Account in a Business Center.

> <span style="color:DodgerBlue">**Note**</span><br>
> Creating Organization Accounts in a Business Center through API is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative.

## Before you start
- Learn [about Organization Accounts](https://ads.tiktok.com/help/article/about-organization-accounts).
- Learn [the list of unavailable features for Organization Accounts](https://ads.tiktok.com/help/article/list-of-unavailable-features-for-organization-accounts).
- Ensure the Business Center has passed business verification. To confirm this, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) and check whether the returned `verification_status` of your Business Center is `VERIFIED`.
- Add contact information and billing information to the Business Center on the Business Center UI if these details are missing.
- You need to be an Admin user of the Business Center. To confirm your permission to a Business Center, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) and confirm whether the returned `user_role` is `ADMIN`.
- For Agency Business Centers, ensure you have created an ad account for your client using [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721) by specifying `qualification_info` . This can generate a verified `qualification_id` that will be used to associate with the Organization Account you want to create.

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/bc/oa/create/

**Method** POST

**Header**

```xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token.<br><br> For details, see [Authentication](https://business-api.tiktok.com/portal/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type.<br><br>Allowed value: `multipart/form-data`.|
```

**Parameters**

```xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|bc_id {Required}|string|The ID of the Business Center.<br><br>To obtain the list of Business Centers that you can access, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826).|
|display_name {Required}|string|The display name (username) for the Organization Account.<br><br>Length limit: 30 characters.|
|handle|string|The handle (name) for the Organization Account.<br><br>Length limit: 24 characters.<br><br>The handle cannot consist solely of numbers.<br><br>If the handle is already taken, an error will occur.|
|profile_image|file|The file to be used as the profile image for the Organization Account.|
|operating_region_code {Required}|string|The code representing the country or region where you would like to run your business.<br><br>For enum values, see <a href="#item-link-List of values for operating_region_code">List of values for `operating_region_code`</a>.<br><br><p><span style="color:darkred"><b>Note</b></span>: If your business is based in the Chinese mainland, you must select an operating region other than China when creating an Organization Account, as `CN` is not an available option for `operating_region_code`.|
|qualification_info {+Conditional}|object|Required when the `type` of your Business Center is `AGENCY` or `SELF_SERVICE_AGENCY`.<br><br>Qualification information.<br><br>To obtain the type of your Business Center, use [/bc/get/](https://business-api.tiktok.com/portal/docs?id=1737115687501826) and check the returned `type`.<br><br>If the `type` of your Business Center is `DIRECT` or `SELF_SERVICE`, an error will occur.|
#|qualification_id|string|The ID of a verified qualification (business verification) associated with a client's ad account.<br><br>To obtain the verified qualification within your Agency Business Center, use [/bc/advertiser/qualification/get/](https://ads.tiktok.com/marketing_api/docs?id=1770118680584194) and set `filtering` to `&#123;"verified": true&#125;`.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you have not yet created an ad account for the client, you can generate a `qualification_id` by calling [/bc/advertiser/create/](https://business-api.tiktok.com/portal/docs?id=1739939020318721) and including their `qualification_info`.|
```

### List of values for `operating_region_code`
```xtable
| <code>operating_region_code</code> value{20%} | Description{80%} |
|---|---|
| `AR` | Argentina |
| `AU` | Australia |
| `AT` | Austria |
| `BD` | Bangladesh |
| `BE` | Belgium |
| `BR` | Brazil |
| `BG` | Bulgaria |
| `KH` | Cambodia |
| `CA` | Canada |
| `CL` | Chile |
| `CO` | Colombia |
| `HR` | Croatia |
| `CY` | Cyprus |
| `CZ` | Czechia |
| `DK` | Denmark |
| `EC` | Ecuador |
| `EG` | Egypt |
| `EE` | Estonia |
| `FI` | Finland |
| `FR` | France |
| `DE` | Germany |
| `GR` | Greece |
| `HU` | Hungary |
| `ID` | Indonesia |
| `IQ` | Iraq |
| `IE` | Ireland |
| `IL` | Israel |
| `IT` | Italy |
| `JP` | Japan |
| `JO` | Jordan |
| `KZ` | Kazakhstan |
| `KW` | Kuwait |
| `LV` | Latvia |
| `LT` | Lithuania |
| `LU` | Luxembourg |
| `MY` | Malaysia |
| `MX` | Mexico |
| `MA` | Morocco |
| `NL` | Netherlands |
| `NZ` | New Zealand |
| `NO` | Norway |
| `PK` | Pakistan |
| `PE` | Peru |
| `PH` | Philippines |
| `PL` | Poland |
| `PT` | Portugal |
| `RO` | Romania |
| `SA` | Saudi Arabia |
| `SG` | Singapore |
| `SK` | Slovakia |
| `SI` | Slovenia |
| `ZA` | South Africa |
| `KR` | South Korea |
| `ES` | Spain |
| `SE` | Sweden |
| `CH` | Switzerland |
| `TW` | Taiwan (Province of China) |
| `TH` | Thailand |
| `TR` | Türkiye |
| `AE` | United Arab Emirates |
| `GB` | United Kingdom |
| `US` | United States |
| `VN` | Vietnam |
```

### Example

#### Create an Organization Account in a Direct Business Center
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/bc/oa/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: multipart/form-data' \
--form 'bc_id="{{bc_id}}"' \
--form 'display_name="{{display_name}}"' \
--form 'handle="{{handle}}"' \
--form 'profile_image="@{{profile_image}}"' \
--form 'operating_region_code="VN"'
(/code)
```

#### Create an Organization Account in an Agency Business Center
```xcodeblock
(code curl http)
curl --location --request POST 'https://business-api.tiktok.com/open_api/v1.3/bc/oa/create/' \
--header 'Access-Token: {{Access-Token}}' \
--header 'Content-Type: multipart/form-data' \
--form 'bc_id="{{bc_id}}"' \
--form 'display_name="{{display_name}}"' \
--form 'profile_image="@{{profile_image}}"' \
--form 'handle="{{handle}}"' \
--form 'operating_region_code="US"' \
--form 'qualification_info="{\"qualification_id\": \"{{qualification_id}}\"}"'
(/code)
```

## Response

```xtable
|Field{30%}|Type{15%}|Description{55%}|
|---|---|---|
|code|number|Response code. For the complete list of response codes and descriptions, see [Appendix-Return Code](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|message|string|Response message. For details, see [Appendix - Return Codes](https://business-api.tiktok.com/portal/docs?id=1737172488964097).|
|request_id|string|The log ID of a request, which uniquely identifies the request.|
|data|object|Returned data.|
#|asset_id|string|The ID of the newly created Organization Account.<br><br><p><span style="color:darkred"><b>Note</b></span>: This ID corresponds to the `asset_id` of the `MANAGED_BUSINESS_ACCOUNT` asset type, which you can retrieve using [/bc/asset/get/](https://business-api.tiktok.com/portal/docs?id=1739432717798401) or [/bc/asset/admin/get/](https://business-api.tiktok.com/portal/docs?id=1739433007779841). Each new Organization Account generates two `asset_id`: one for the `MANAGED_BUSINESS_ACCOUNT` type and one for the `TT_ACCOUNT` type. You can use [/bc/asset/assign/](https://business-api.tiktok.com/portal/docs?id=1739438211077121) to assign either asset type to a member, depending on the permissions you wish to grant.|
#|asset_name|string|The display name (username) of the created Organization Account.|
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
        "asset_id": "{{asset_id}}",
        "asset_name": "{{asset_name}}"
    }
}
(/code)
```
