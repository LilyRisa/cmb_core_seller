---
title: "Get the identity list"
doc_id: 1740218420781057
path: "API Reference / Identity / Get the identity list"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the identity list

Use this endpoint to get a list of identities under an ad account. You can filter results by identity type or display name.


## Comparing v1.2 and v1.3 
The following table outlines the differences between v1.2 and v1.3 endpoints. 
```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/identity/get/| /v1.3/identity/get/|
|Request parameter data type |`advertiser_id`: number |`advertiser_id`: string |
|Response parameter name|`list` | `identity_list`|
|New response parameters| / | `can_manage_message`<br>`is_gpppa`<br>`ads_only_mode`<br>`username`|
```

## Request

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/identity/get/

**Method** GET

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
| advertiser_id {Required} | string | Advertiser ID. |
| identity_type | string | Identity type.<br><br> Enum values: `CUSTOMIZED_USER`, `AUTH_CODE`, `TT_USER`, `BC_AUTH_TT`. See [Identities](https://ads.tiktok.com/marketing_api/docs?id=1738958351620097) for details.<br><br>If this field is not specified, all identities will be returned. <br><br><p><span style="color:darkred"><b>Note</b></span>: If you set this field to `BC_AUTH_TT` and specify `identity_authorized_bc_id`, all identities linked to the ad account (`advertiser_id`) will be returned.</p>|
| identity_authorized_bc_id {+Conditional}| string |Required when `identity_type` is `BC_AUTH_TT`. <br><br>ID of the Business Center that a TikTok Account User in Business Center identity is associated with.  |
|filtering|object|Filtering conditions.<br><br><p><span style="color:darkred"><b>Note</b></span><br>This field is valid only when `identity_type` = `CUSTOMIZED_USER` or when `identity_type` is not specified.|
#|keyword|string|Keyword to filter by. <br><br>When `identity_type` = `CUSTOMIZED_USER`, you can use fuzzy matching by specifying display name to this field.|
| page | number | Current page number.<br><br>Default value: 1.|
| page_size | number | Page size..<br><br>Value range: 1-100.<br><br>Default value: 20.|
```

### Example

```xcodeblock
(code curl http)
curl --location  --request GET 'https://business-api.tiktok.com/open_api/v1.3/identity/get/?advertiser_id={{advertiser_id}}&identity_type=TT_USER' \
--header 'Access-Token: {{Access-Token}}'
(/code)

(code Java java)
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import okhttp3.*;
import org.apache.http.client.utils.URIBuilder;

import java.io.IOException;
import java.net.URI;
import java.net.URISyntaxException;
import java.net.URL;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

public class Demo {
    private static final String ACCESS_TOKEN = "xxx";
    private static final String PATH = "/open_api/v1.3/identity/get/";
    private static final ObjectMapper mapper = new ObjectMapper();

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
        Map< String, Object > map = mapper.readValue(jsonStr, Map.class);
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
        String advertiser_id = ADVERTISER_ID;
        String identity_type = IDENTITY_TYPE;
        String identity_authorized_bc_id = IDENTITY_AUTHORIZED_BC_ID;
        Long page = PAGE;
        Long page_size = PAGE_SIZE;

        // Args in JSON format
        String myArgs = String.format("{\"advertiser_id\": \"%s\", \"identity_type\": \"%s\", \"identity_authorized_bc_id\": \"%s\", \"page\": \"%s\", \"page_size\": \"%s\"}",advertiser_id, identity_type, identity_authorized_bc_id, page, page_size);
        System.out.println(get(myArgs));
    }
}

(/code)

(code Python python)
# coding=utf-8
import json
import requests

from six import string_types
from six.moves.urllib.parse import urlencode, urlunparse  # noqa

ACCESS_TOKEN = "xxx"
PATH = "/open_api/v1.3/identity/get/"


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
    advertiser_id = ADVERTISER_ID
    identity_type = IDENTITY_TYPE
    identity_authorized_bc_id = IDENTITY_AUTHORIZED_BC_ID
    page = PAGE
    page_size = PAGE_SIZE

    # Args in JSON format
    my_args = "{\"advertiser_id\": \"%s\", \"identity_type\": \"%s\", \"identity_authorized_bc_id\": \"%s\", \"page\": \"%s\", \"page_size\": \"%s\"}" % (advertiser_id, identity_type, identity_authorized_bc_id, page, page_size)
    print(get(my_args))

(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/identity/get/";

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


$advertiser_id = ADVERTISER_ID;
$identity_type = IDENTITY_TYPE;
$identity_authorized_bc_id = IDENTITY_AUTHORIZED_BC_ID;
$page = PAGE;
$page_size = PAGE_SIZE;

/* Args in JSON format */
$my_args = sprintf("{\"advertiser_id\": \"%s\", \"identity_type\": \"%s\", \"identity_authorized_bc_id\": \"%s\", \"page\": \"%s\", \"page_size\": \"%s\"}", $advertiser_id, $identity_type, $identity_authorized_bc_id, $page, $page_size);
echo get($my_args);

(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|request_id|string|The log id of the request, which uniquely identifies a request. |
|data |object| Returned data. |
#| identity_list | object[] | List of identities. |
##| identity_id | string | Identity ID. |
##| identity_type | string | Identity type. |
##| identity_authorized_bc_id | string |ID of the Business Center that a TikTok Account User in Business Center identity is associated with.|
##| ads_only_mode | boolean | Whether the "Show through ads only" mode is enabled for the identity.<br><br>Supported values: `true`, `false`.<br>This field returns a non-null value when `identity_type` is `BC_AUTH_TT`.<br><br>When this field is `true`, you cannot set `dark_post_status` to `OFF` while creating ads (through [/smart_plus/ad/create/](https://business-api.tiktok.com/portal/docs?id=1843317390059522), [/ad/create/](https://business-api.tiktok.com/portal/docs?id=1739953377508354), or [/campaign/spc/create/](https://business-api.tiktok.com/portal/docs?id=1767334204047362)) or updating ads (through [/smart_plus/ad/update/](https://business-api.tiktok.com/portal/docs?id=1843317411665921), [/ad/update/](https://business-api.tiktok.com/portal/docs?id=1739953405142018), or [/campaign/spc/update/](https://business-api.tiktok.com/portal/docs?id=1767334250066945)) to allow your post to appear on your TikTok profile and be eligible to receive organic traffic. |
##| username | string | The username (handle name) of the identity. |
##|is_gpppa|boolean|Whether the TikTok account is a [Government, Politician, and Political Party Account](https://support.tiktok.com/en/using-tiktok/growing-your-audience/government-politician-and-political-party-accounts) (GPPPA).<br><br>Supported values: `true`, `false`.<br><br>When this field is `true`, the TikTok account cannot be used to [create Spark Ads](https://business-api.tiktok.com/portal/docs?id=1739470744631298).|
##| can_push_video | boolean | Whether the `BC_AUTH_TT` or `TT_USER` identity can create or edit videos. <br><br> Supported values: `true`, `false`.|
##| can_pull_video | boolean | Whether the `BC_AUTH_TT` or `TT_USER` identity can get all videos under the TikTok account. <br><br> Supported values: `true`, `false`.|
##| can_use_live_ads | boolean | Whether the `BC_AUTH_TT`or `TT_USER` identity can access the live room. <br><br> Supported values: `true`, `false`.|
##| can_manage_message | boolean | Whether the `BC_AUTH_TT` or `TT_USER` identity can manage direct messages.<br><br> Supported values: `true`, `false`.|
##| display_name | string | Display name. |
##| available_status | string | Availability of the identity. Only valid for `TT_USER` and `BC_AUTH_TT` type of identities. Enum values: `AVAILABLE`, `NO_VALID_BIND_ACCOUNT`, `SCOPE_UNAVAILABLE`,` IS_PRIVATE_ACCOUNT`, `NOT_BUSINESS_ACCOUNT`. |
##| profile_image | string | Profile image URL. |
#| page_info | object | Pagination information. <br><br><p><span style="color:darkred"><b>Note</b></span>: Correct pagination info is available only when you have specified `identity_type` in the request.|
##| page | number | Current page number. |
##| total_page | number | Total number of pages. |
##| total_number | number | Total number of results. |
##| page_size | number | Number of results on each page. |
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
        "page_info": {
            "total_number": 1,
            "page_size": 20,
            "total_page": 1,
            "page": 1
        },
        "identity_list": [
            {
                "ads_only_mode": null,
				"profile_image": "{{profile_image}}",
                "identity_type": "TT_USER",
                "identity_id": "{{identity_id}}",
                "can_pull_video": true,
                "display_name": "{{display_name}}",
                "identity_authorized_bc_id": null,
                "can_push_video": true,
                "can_use_live_ads": true,
                "can_manage_message": true,
                "available_status": "AVAILABLE",
                "is_gpppa": false,
                "username": "{{username}}"
            }
        ]
    }
}
(/code)
```


