---
title: "Share audiences"
doc_id: 1740245803888706
path: "API Reference / Audience / Share audiences"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Share audiences

Use this endpoint to start sharing audiences. 

Ad account owners, or users in Admin or Operator roles can share a Custom Audience (only created from [Customer file](https://ads.tiktok.com/marketing_api/docs?id=1739940501023746), [Segment](https://ads.tiktok.com/marketing_api/docs?id=1739940504185857), Engagement, App Activity or Website Traffic)  or a Lookalike Audience with specified ad accounts within the same Business Center.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
  ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/dmp/custom_audience/share/| /v1.3/dmp/custom_audience/share/
|Request parameter data type|`shared_advertiser_ids`: number[]<br>`advertiser_id`: number<br>`custom_audience_ids`: number[]|`shared_advertiser_ids`: string[]<br>`advertiser_id`: string<br>`custom_audience_ids`: string[]|
```

## Request 
**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/share/

**Method** POST

**Header**
```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
| Content-Type {Required} | string | The content type of the request. <br>Allowed value: `"application/json"`.|
```
**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|custom_audience_ids {Required}|string[]| Custom audiences that you want to share. Size: 1-10.|
|shared_advertiser_ids{Required}|string[]| Advertisers that you want to share audiences with. They must be in the same Business Center as you. Size: 1-10.|
|advertiser_id{Required}|string|Operator advertiser ID.
```

### Examples
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
    private static final String PATH = "/open_api/v1.3/dmp/custom_audience/share/";
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
        List < Long > custom_audience_ids_list = CUSTOM_AUDIENCE_IDS;
        String custom_audience_ids = mapper.writeValueAsString(custom_audience_ids_list);
        List < Long > shared_advertiser_ids_list = SHARED_ADVERTISER_IDS;
        String shared_advertiser_ids = mapper.writeValueAsString(shared_advertiser_ids_list);
        Long advertiser_id = ADVERTISER_ID;

        // Args in JSON format
        String myArgs = String.format("{\"custom_audience_ids\": %s, \"shared_advertiser_ids\": %s, \"advertiser_id\": \"%s\"}",custom_audience_ids, shared_advertiser_ids, advertiser_id);
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
PATH = "/open_api/v1.3/dmp/custom_audience/share/"


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
    custom_audience_ids_list = CUSTOM_AUDIENCE_IDS
    custom_audience_ids = json.dumps(custom_audience_ids_list)
    shared_advertiser_ids_list = SHARED_ADVERTISER_IDS
    shared_advertiser_ids = json.dumps(shared_advertiser_ids_list)
    advertiser_id = ADVERTISER_ID

    # Args in JSON format
    my_args = "{\"custom_audience_ids\": %s, \"shared_advertiser_ids\": %s, \"advertiser_id\": \"%s\"}" % (custom_audience_ids, shared_advertiser_ids, advertiser_id)
    print(post(my_args))

(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/dmp/custom_audience/share/";

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


$custom_audience_ids_list = CUSTOM_AUDIENCE_IDS;
$custom_audience_ids = json_encode($custom_audience_ids_list);
$shared_advertiser_ids_list = SHARED_ADVERTISER_IDS;
$shared_advertiser_ids = json_encode($shared_advertiser_ids_list);
$advertiser_id = ADVERTISER_ID;

/* Args in JSON format */
$my_args = sprintf("{\"custom_audience_ids\": %s, \"shared_advertiser_ids\": %s, \"advertiser_id\": \"%s\"}", $custom_audience_ids, $shared_advertiser_ids, $advertiser_id);
echo post($my_args);

(/code)

(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "custom_audience_ids": [
        "CUSTOM_AUDIENCE_IDS"
    ],
    "shared_advertiser_ids": [
        "SHARED_ADVERTISER_IDS"
    ],
    "advertiser_id": "ADVERTISER_ID"
}' \
https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/share/
(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Returned data.|
|request_id |string|The log id of the request, which uniquely identifies a request. |
```

### Example

``` xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {}
}
(/code);
```




