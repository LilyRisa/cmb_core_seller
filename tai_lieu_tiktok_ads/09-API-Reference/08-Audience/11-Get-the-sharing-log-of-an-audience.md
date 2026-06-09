---
title: "Get the sharing log of an audience"
doc_id: 1740245827044354
path: "API Reference / Audience / Get the sharing log of an audience"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Get the sharing log of an audience

Use this endpoint to get the sharing log of an audience. The information in the log includes the advertiser IDs and names that the audience has been shared with.


## Comparing v1.2 and v1.3 

 The following table outlines the differences between v1.2 and v1.3 endpoints. 

  ```xtable
|Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|	/v1.2/dmp/custom_audience/share/log/|	/v1.3/dmp/custom_audience/share/log/
|Request parameter data type|`advertiser_id`: number<br>`custom_audience_id`: number|`advertiser_id`: string<br>`custom_audience_id`: string|
|Response parameter data type|`shared_advertiser_id`: number<br>`custom_audience_id`: number|`shared_advertiser_id`: string<br>`custom_audience_id`: string|
  ```

## Request 

**Endpoint** https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/share/log/

**Method** GET

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
```

**Parameters**

``` xtable
|Field|Data Type|Description|
|---|---|---|
|custom_audience_id {Required}|string|Custom audiences that you want to get sharing log for.|
|advertiser_id{Required}|string| Advertiser ID.|
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
    private static final String PATH = "/open_api/v1.3/dmp/custom_audience/share/log/";
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
        Long custom_audience_id = CUSTOM_AUDIENCE_ID;
        Long advertiser_id = ADVERTISER_ID;

        // Args in JSON format
        String myArgs = String.format("{\"custom_audience_id\": \"%s\", \"advertiser_id\": \"%s\"}",custom_audience_id, advertiser_id);
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
PATH = "/open_api/v1.3/dmp/custom_audience/share/log/"


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
    custom_audience_id = CUSTOM_AUDIENCE_ID
    advertiser_id = ADVERTISER_ID

    # Args in JSON format
    my_args = "{\"custom_audience_id\": \"%s\", \"advertiser_id\": \"%s\"}" % (custom_audience_id, advertiser_id)
    print(get(my_args))



(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/dmp/custom_audience/share/log/";

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


$custom_audience_id = CUSTOM_AUDIENCE_ID;
$advertiser_id = ADVERTISER_ID;

/* Args in JSON format */
$my_args = sprintf("{\"custom_audience_id\": \"%s\", \"advertiser_id\": \"%s\"}", $custom_audience_id, $advertiser_id);
echo get($my_args);


(/code)

(code curl http)
curl --get -H "Access-Token:xxx" \
--data-urlencode "custom_audience_id=CUSTOM_AUDIENCE_ID" \
--data-urlencode "advertiser_id=ADVERTISER_ID" \
https://business-api.tiktok.com/open_api/v1.3/dmp/custom_audience/share/log/


(/code)
```

## Response

``` xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Return code, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097). |
|message |string|Return message, see [Appendix-Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).  |
|data |object|Returned data.|
#|list |object[]|A list of Audience. |
##|shared_advertiser_id |string|ID of the advertiser that the audience is shared with.|
##|shared_advertiser_name|string|Name of the advertiser that the audience is shared with.|
##|custom_audience_id|string|ID of the audience that you want to get sharing log for.|
##|status |string|Audience status.|
|request_id |string|The log id of a request, which uniquely identifies the request.|
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




