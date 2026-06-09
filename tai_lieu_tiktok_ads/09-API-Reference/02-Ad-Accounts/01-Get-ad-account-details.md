---
title: "Get ad account details"
doc_id: 1739593083610113
path: "API Reference / Ad Accounts / Get ad account details"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get ad account details

Use this endpoint to obtain the details of an advertiser's ad account. 

To add a partner and assign ad accounts to this partner, use the [/bc/partner/add/](https://ads.tiktok.com/marketing_api/docs?id=1739662756510721) endpoint. Set `asset_type` to `ADVERTISER` and pass in advertiser IDs to the `asset_ids` field. You can get the advertiser IDs in the response field `advertiser_id` of `/advertiser/info/`.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path |/v1.2/advertiser/info/|/v1.3/advertiser/info/  |
|Request parameter data type| `advertiser_ids`: number[] |  `advertiser_ids`: string[] |
|Request parameter value name|`telephone`<br>`phonenumber`<br> `reason` <br>  `id`<br>(all are values in `fields`) |`telephone_number`<br> `cellphone_number` <br>`rejection_reason`  <br>`advertiser_id`<br>(all are values in `fields`) |
|Response parameter name|`reason` <br>`phonenumber` <br> `telephone` <br>(in the `data` object)| `rejection_reason` <br>`cellphone_number` <br> `telephone_number` <br>(in the `list` object)</li></ul>|
|Response parameter data type| `data`: object[] | `data`: object |
|Response parameter name and data type|  `id`: number| `advertiser_id`: string|
|New response parameter|/|  `list` (in the `data` object)<br>`advertiser_account_type`<br>`company_name_editable`<br>`can_use_custom_identity` <br>`ads_only_mode`|
```


## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/advertiser/info/

**Method** GET

**Header**

```xtable
|Field{40%}|Data Type{10%}|Description{50%}|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162). |

```

**Parameters**

```xtable
|Field{40%}|Data Type{10%}|Description{50%}|
|---|---|---|
|advertiser_ids {Required}|string[]|List of advertiser IDs to query. <br><br>You can obtain Advertiser IDs through the [/oauth2/advertiser/get/](https://business-api.tiktok.com/portal/docs?id=1738455508553729) endpoint. |
|fields |string[]|A list of information to be returned. <br><br>If not specified, all information excluding `company_name_editable`, `can_use_custom_identity`, and `ads_only_mode` is returned by default.<br><br>Supported values: `telephone_number`, `contacter`, `currency`, `cellphone_number`, `timezone`, `advertiser_id`, `role`, `company`, `status`, `description`, `rejection_reason`,  `address`, `name`, `language`,  `industry`, `license_no`, `email`, `license_url`, `country`, `balance`, `create_time`, `display_timezone`, `owner_bc_id`, `company_name_editable`, `can_use_custom_identity`, and `ads_only_mode`. <br>See the Response section for detailed information on each field.<br><br><p><span style="color:darkred"><b>Note</b></span>: If you include `company_name_editable`, `can_use_custom_identity`, or `ads_only_mode` in the value of this field, only `company_name_editable`, `can_use_custom_identity`, or `ads_only_mode` along with `advertiser_id` will be returned and other values will be ignored. |
```

### Example

```xcodeblock
(code Java java)
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import okhttp3.*;
import org.apache.http.client.utils.URIBuilder;

import java.io.IOException;
import java.net.URI;
import java.net.URISyntaxException;
import java.net.URL;
import java.util.Map;

public class Demo {
    private static final String ACCESS_TOKEN = "xxx";
    private static final String PATH = "/open_api/v1.3/advertiser/info/";

    /**
     * Build request URL
     *
     * @param path Request path
     * @return Request URL
     */
    private static String buildUrl(String path) throws URISyntaxException {
        URI uri = new URI("https", "business-api.tiktok.com", path, "", "");
        return uri.toString();
    }


    /**
     * Send GET request
     *
     * @param jsonStr:Args in JSON format
     * @return Response in JSON format
     */
    private static String get(String jsonStr) throws IOException, URISyntaxException {
        OkHttpClient client = new OkHttpClient().newBuilder().build();
        URIBuilder ub = new URIBuilder(buildUrl(PATH));
        ObjectMapper mapper = new ObjectMapper();
        Map< string, object="" > map = mapper.readValue(jsonStr, Map.class);
        map.forEach((k, v) -> {
            try {
                ub.addParameter(k, v instanceof String ? (String) v : mapper.writeValueAsString(v));
            } catch (JsonProcessingException e) {
                e.printStackTrace();
            }
        });
        URL url = ub.build().toURL();

        Request request = new Request.Builder()
                .url(url)
                .method("GET", null)
                .addHeader("Access-Token", ACCESS_TOKEN)
                .build();
        Response response = client.newCall(request).execute();
        return response.body().string();
    }

    public static void main(String[] args) throws IOException, URISyntaxException {
        String fields = FIELDS;
        String advertiser_ids = ADVERTISER_IDS;

        // Args in JSON format
        String myArgs = String.format("{\"fields\": [\"%s\"], \"advertiser_ids\": [\"%s\"]}",fields, advertiser_ids);
        System.out.println(get(myArgs));
    }
}

(/code)

(code Python python)
# coding=utf-8
import json

from six import string_types
from six.moves.urllib.parse import urlencode, urlunparse  # noqa

import requests

ACCESS_TOKEN = "xxx"
PATH = "/open_api/v1.3/advertiser/info/"


def build_url(path, query=""):
    # type: (str, str) -> str
    """
    Build request URL
    :param path: Request path
    :param query: Querystring
    :return: Request URL
    """
    scheme, netloc = "https", "business-api.tiktok.com"
    return urlunparse((scheme, netloc, path, "", query, ""))

def get(json_str):
    # type: (str) -> dict
    """
    Send GET request
    :param json_str: Args in JSON format
    :return: Response in JSON format
    """
    args = json.loads(json_str)
    query_string = urlencode({k: v if isinstance(v, string_types) else json.dumps(v) for k, v in args.items()})
    url = build_url(PATH, query_string)
    headers = {
        "Access-Token": ACCESS_TOKEN,
    }
    rsp = requests.get(url, headers=headers)
    return rsp.json()

if __name__ == '__main__':
    fields = FIELDS
    advertiser_ids = ADVERTISER_IDS

    # Args in JSON format
    my_args = "{\"fields\": [\"%s\"], \"advertiser_ids\": [\"%s\"]}" % (fields, advertiser_ids)
    print(get(my_args))

(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/advertiser/info/";

/**
 * Build request URL
 * @param $path : Request path
 * @return string
 */
function build_url($path)
{
    return "https://business-api.tiktok.com" . $path;
}

/**
 * Send GET request
 * @param $json_str : Args in JSON format
 * @return bool|string : Response in JSON format
 */
function get($json_str)
{
    global $ACCESS_TOKEN, $PATH;
    $curl = curl_init();

    $args = json_decode($json_str, true);

    /* Values of querystring is also in JSON format */
    foreach ($args as $key => $value) {
        $args[$key] = is_string($value) ? $value : json_encode($value);
    }

    $url = build_url($PATH) . "?" . http_build_query(
            $args
        );

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Access-Token: " . $ACCESS_TOKEN,
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}


$fields = FIELDS;
$advertiser_ids = ADVERTISER_IDS;

/* Args in JSON format */
$my_args = sprintf("{\"fields\": [\"%s\"], \"advertiser_ids\": [\"%s\"]}", $fields, $advertiser_ids);
echo get($my_args);
(/code)

(code curl http)
curl --location --request GET 'https://business-api.tiktok.com/open_api/v1.3/advertiser/info/?advertiser_ids={{advertiser_ids}}' \
--header 'Access-Token: {{Access-Token}}' \
(/code)

```

## Response

```xtable
|Field{40%}|Data Type{10%}|Description{50%}|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|request_id |string|The log id of the request, which uniquely identifies a request.|
|data |object|Returned data. |
#|list|object[]|Ad account (advertiser account) list.|
##|advertiser_id |string|Advertiser ID.|
##|can_use_custom_identity |boolean|Whether the ad account has custom identities that are available for creating ads.<br><br>Supported values: <ul><li>`true`: The ad account can use custom identities to create ads. </li><li>`false`: The ad account cannot use custom identities to create ads. <ul><li>However, in some cases, you can continue to use custom identities. Learn more about the unaffected scenarios in [About changes coming to Custom Identity](https://ads.tiktok.com/help/article/about-changes-coming-to-custom-identity).</li></ul> </li></ul> |
##| ads_only_mode | boolean | Whether the ad account must use "Show through ads only" for Spark Ads created using Spark Ads Push.<br><br>Supported values: `true`, `false`.<br><br>When this field is `true`, a mandatory "Show through ads only" mode is active for the ad account. As a result, you can only set `dark_post_status` to `ON` when creating Spark Ads using Spark Ads Push (through [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522), [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354), or [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362)) or updating ads (through [/smart_plus/ad/update/](https://business-api.tiktok.com/portal/docs?id=1843317411665921), [/ad/update/](https://business-api.tiktok.com/portal/docs?id=1739953405142018), or [/campaign/spc/update/](https://business-api.tiktok.com/portal/docs?id=1767334250066945)). This prevents the posts from appearing on your TikTok profile and gaining organic traffic, regardless of the identity's own `ads_only_mode` setting. It's a safeguard to ensure your Spark Ads Push content remains ads-only and avoids accidental profile posts.<br><br><p><span style="color:darkred"><b>Note</b></span>: Mandatory "Show through ads only" for an ad account is currently an allowlist-only feature. If you would like to access it, please contact your TikTok representative. </p> |
##|owner_bc_id|string|The ID of a Business Center that the ad account belongs to. <br><br><p><span style="color:darkred"><b>Note</b></span>: This field will be returned only when the below requirements are both met:<ul><li>The user has obtained admin access to the owner Business Center (The Business Center that owns the ad account), and</li><li>The user has obtained Admin/Operator/Analyst permission for the ad account from the owner Business Center, rather than a partner Business Center.</li></ul> |
##|status |string|Ad account status. <br><br>For enum values, see [Enumeration - Advertiser Status](https://business-api.tiktok.com/portal/docs?id=1737174886619138#Advertiser%20Status).  <br><br>Example: `STATUS_ENABLE`.|
##|role |string|Ad account role.  <br><br>For enum values, , see [Enumeration - Advertiser Role](https://business-api.tiktok.com/portal/docs?id=1737174886619138#Advertiser%20Roles). <br><br>Example: `ROLE_ADVERTISER`.|
##|rejection_reason |string|Reason for rejection.|
##|name |string|Ad account name.|
##|timezone |string| Ad account time zone including GMT offset, or time zone name in the format of "Region/City" (based on [the TZ database name conventions](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones)).  <br><br>Example: `Etc/GMT`, `Europe/London`.  |
##|display_timezone|string| Time zone name in the format of "Region/City" (based on the [TZ database name conventions](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones).  <br><br>Example:  `Europe/London`. |
##|company |string|Ad account's company name.|
##|company_name_editable | boolean | Whether the company name of the ad account can be updated via API. <br><ul><li> If the value of this field is `true`, you can use the `company` field in [/advertiser/update/](https://business-api.tiktok.com/portal/docs?id=1739939050770434) to update the company name. </li><li>For non-self-serve advertisers (advertisers directly managed by TikTok representatives), the value of this field will be `false`. If they want to update the company name, they need to contact their TikTok representatives.</li></ul> |
##|industry |string|Ad account industry category code. For enum values, see [Industries](https://ads.tiktok.com/marketing_api/docs?id=1739357589575681).<br><br>Example:  `290303`. |
##|address |string|Ad account address. |
##|country |string|The place of registration code of the ad account.<br><br>Example:  `US`. |
##|advertiser_account_type |string|Type of the ad account. <br><br>Enum values: `RESERVATION` (reservation ad account), `AUCTION`(auction ad account).|
##|currency |string|Type of currency used by the ad account, in [ISO 4217 code](https://en.wikipedia.org/wiki/ISO_4217) format.<br><br>Example:  `EUR`. |
##|contacter |string|Contact name, in masked format. The name of the person responsible for this account.<br><br>Example:  `Te********************nt`. |
##|email |string|Ad account contact email, in masked format. <br><br>Example:  `l***********@*************`. |
##|cellphone_number |string|Contact mobile number, in masked format. |
##|telephone_number |string|Fixed phone number, in masked format. |
##|language |string|The code of the language used by the ad account. <br><br>Example:  `en`. |
##|license_no |string|Business license number.|
##|license_url |string|Business license preview URL. <br><br>The link is valid for an hour by default.|
##| license_province {-to-be-deprecated} | string | The province where the license was issued. |
##| license_city{-to-be-deprecated} | string | The city where the license was issued. |
##| promotion_area {-to-be-deprecated} |string | The geographical area where the ad account's promotions are focused. |
##| promotion_center_province {-to-be-deprecated} | string | The primary province for the ad account's promotional activities. |
##| promotion_center_city{-to-be-deprecated} | string | The primary city for the ad account's promotional activities. |
##| brand {-to-be-deprecated} | string | The brand name of the ad account. |
##|description |string|Brand description.|
##|balance |float|Ad account available balance.<br><br>The unit is related to the `currency` of the ad account.<br><br>Example: 5325.43. |
##|create_time |datetime|The time when the ad account was created, in the format of an Epoch/Unix timestamp in seconds.<br><br>Example:  `1510740064`. |
```

### Example

```xcodeblock
(code Success-Response http)
{
    "code": 0,
    "message": "OK",
    "request_id": "{{request_id}}",
    "data": {
        "list": [
            {
                "company": "{{company}}",
                "address": null,
                "status": "STATUS_ENABLE",
                "contacter": null,
                "license_province": null,
                "license_city": null,
                "currency": "USD",
                "promotion_area": "0",
                "promotion_center_city": null,
                "cellphone_number": null,
                "timezone": "Etc/GMT+8",
                "description": "{{description}}",
                "rejection_reason": "",
                "advertiser_id": "{{advertiser_id}}",
                "license_no": "{{license_no}}",
                "promotion_center_province": null,
                "name": "{{name}}",
                "industry": "{{industry}}",
                "telephone_number": null,
                "license_url": "{{license_url}}",
                "role": "ROLE_CHILD_ADVERTISER",
                "advertiser_account_type": "AUCTION",
                "create_time": {{create_time}},
                "owner_bc_id": "{{owner_bc_id}}",
                "language": "zh",
                "email": null,
                "country": "CN",
                "balance": 0.0,
                "brand": null,
                "display_timezone": "America/Los_Angeles"
            }
        ]
    }
}
(/code);
```
