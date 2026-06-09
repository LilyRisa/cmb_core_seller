---
title: "Cancel the sharing of assets"
doc_id: 1739662800930817
path: "API Reference / BC Partners / Cancel the sharing of assets"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Cancel the sharing of assets

Use this endpoint to cancel the sharing of assets with a partner. You need to be an Admin of the Business Center.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path| /v1.2/bc/partner/asset/delete/|/v1.3/bc/partner/asset/delete/ |
|Request parameter data type|`bc_id`: number<br>`partner_id`: number<br>`asset_ids`: number[]|`bc_id`: string <br>`partner_id`: string<br>`asset_ids`: string[]|
```

## Request

**Endpoint**https://business-api.tiktok.com/open_api/v1.3/bc/partner/asset/delete/

**Method** POST

**Header**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token{Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type{Required}|string|The content type of the request. <br>Allowed value: `"application/json"`. |
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|bc_id {Required} | string | Business Center ID |
|partner_id {Required} | string | Partner Business Center ID |
|asset_type {Required}|string|Asset type. <br><br>Enum values:<ul><li>`ADVERTISER`: ad account (advertiser account).</li><li>`TT_ACCOUNT`: TikTok account. This asset type corresponds to the identity type `BC_AUTH_TT`. Learn more from [Identities](https://business-api.tiktok.com/portal/docs?id=1738958351620097).</li><li>`TIKTOK_SHOP`: TikTok Shop.</li><li>`CATALOG`: catalog.</li><li>`LEAD`: Leads.</li><li>`PIXEL`: Pixel.</li><li>`STOREFRANT` <font color="red">Deprecated</font></li></ul>|
|asset_ids {Required}|string[]|List of asset IDs that you want to cancel the sharing of. <br><br><ul><li>If `asset_type` is `ADVERTISER`, this field is the list of advertiser IDs.</li><li>If `asset_type` is `TT_ACCOUNT`, this field is the list of IDs of TikTok accounts in a Business Center.</li><li>If `asset_type` is `TIKTOK_SHOP`, this field is the list of TikTok Shop IDs.</li><li>If `asset_type` is `CATALOG`, this field is the list of catalog IDs.</li><li>If `asset_type` is `LEAD`, this field is the list of form library IDs. Currently, only one form library ID is allowed.<ul><li>To obtain more details about form libraries that you have access to, use [/page/library/get/](https://business-api.tiktok.com/portal/docs?id=1740029915327489).</li></ul></li><li>If `asset_type` is `PIXEL`, this field is the list of Pixel IDs.</li></ul>|
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
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

public class Demo {
    private static final String ACCESS_TOKEN = "xxx";
    private static final String PATH = "/open_api/v1.3/bc/partner/asset/delete/";
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
     * Send POST request
     *
     * @param jsonStr Args in JSON format
     * @return Response in JSON format
     */
    private static String post(String jsonStr) throws IOException, URISyntaxException {
        OkHttpClient client = new OkHttpClient().newBuilder().build();
        String url = buildUrl(PATH);

        RequestBody body = RequestBody.create(MediaType.parse("application/json"), jsonStr);
        Request request = new Request.Builder()
                .url(url)
                .method("POST", body)
                .addHeader("Content-Type", "application/json")
                .addHeader("Access-Token", ACCESS_TOKEN)
                .build();
        Response response = client.newCall(request).execute();
        return response.body().string();
    }


    public static void main(String[] args) throws IOException, URISyntaxException {
        String bc_id = BC_ID;
        String partner_id = PARTNER_ID;
        List < String > asset_ids_list = ASSET_IDS;
        String asset_ids = mapper.writeValueAsString(asset_ids_list);
        String asset_type = ASSET_TYPE;

        // Args in JSON format
        String myArgs = String.format("{\"bc_id\": \"%s\", \"partner_id\": \"%s\", \"asset_ids\": %s, \"asset_type\": \"%s\"}",bc_id, partner_id, asset_ids, asset_type);
        System.out.println(post(myArgs));
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
PATH = "/open_api/v1.3/bc/partner/asset/delete/"


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

def post(json_str):
    # type: (str) -> dict
    """
    Send POST request
    :param json_str: Args in JSON format
    :return: Response in JSON format
    """
    url = build_url(PATH)
    args = json.loads(json_str)
    headers = {
        "Access-Token": ACCESS_TOKEN,
        "Content-Type": "application/json",
    }
    rsp = requests.post(url, headers=headers, json=args)
    return rsp.json()


if __name__ == '__main__':
    bc_id = BC_ID
    partner_id = PARTNER_ID
    asset_ids_list = ASSET_IDS
    asset_ids = json.dumps(asset_ids_list)
    asset_type = ASSET_TYPE

    # Args in JSON format
    my_args = "{\"bc_id\": \"%s\", \"partner_id\": \"%s\", \"asset_ids\": %s, \"asset_type\": \"%s\"}" % (bc_id, partner_id, asset_ids, asset_type)
    print(post(my_args))
(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/bc/partner/asset/delete/";

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
 * Send POST request
 * @param $json_str : Args in JSON format
 * @return bool|string : Response in JSON format
 */
function post($json_str)
{
    global $ACCESS_TOKEN, $PATH;
    $curl = curl_init();

    $url = build_url($PATH);

    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $json_str,
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Access-Token: " . $ACCESS_TOKEN,
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}


$bc_id = BC_ID;
$partner_id = PARTNER_ID;
$asset_ids_list = ASSET_IDS;
$asset_ids = json_encode($asset_ids_list);
$asset_type = ASSET_TYPE;

/* Args in JSON format */
$my_args = sprintf("{\"bc_id\": \"%s\", \"partner_id\": \"%s\", \"asset_ids\": %s, \"asset_type\": \"%s\"}", $bc_id, $partner_id, $asset_ids, $asset_type);
echo post($my_args);
(/code)

(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "bc_id": "BC_ID",
    "partner_id": "PARTNER_ID",
    "asset_ids": [
        "ASSET_IDS"
    ],
    "asset_type": "ASSET_TYPE"
}' \
https://business-api.tiktok.com/open_api/v1.3/bc/partner/asset/delete/ 
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number | Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string | Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object| Returned data. |
|request_id|string|The log id of the request, which uniquely identifies a request. |
```


### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
} 
(/code)
```


