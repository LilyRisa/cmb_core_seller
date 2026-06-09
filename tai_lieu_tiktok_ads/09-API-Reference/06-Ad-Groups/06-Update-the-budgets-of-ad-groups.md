---
title: "Update the budgets of ad groups"
doc_id: 1739591133130817
path: "API Reference / Ad Groups / Update the budgets of ad groups"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Update the budgets of ad groups

Use this endpoint to update the lifetime budgets of one or more ad groups or set a scheduled budget changes to the daily budgets or dynamic daily budgets of one or more ad groups. 

The scheduled budget changes will take effect starting around 00:00 a.m. the next day in the ad account's time zone. Note that the budget cannot be set or updated during the budget lock time (23:55 - 00:00) in the ad account's time zone.

See [Currency](https://ads.tiktok.com/marketing_api/docs?id=1737585839634433) to find out the advertising budget limits.

## Comparing v1.2 and v1.3 
 The following table outlines the differences between v1.2 and v1.3 endpoints. 
 ```xtable
 |Changes{30%}|v1.2{35%}|v1.3{35%}|
|---|---|---|
|Endpoint path|/v1.2/adgroup/update/budget/|/v1.3/adgroup/budget/update/|
|Request parameter data type |  `advertiser_id`: number <br> `adgroup_id`: number|   `advertiser_id`: string <br> `adgroup_id`: string |
```

## Request

**Endpoint** <https://business-api.tiktok.com/open_api/v1.3/adgroup/budget/update/>

**Method** POST

**Header**

```xtable
|Field|Data Type|Description|
|---|---|---|
|Access-Token {Required}|string|Authorized access token. For details, see [Authentication](https://ads.tiktok.com/marketing_api/docs?id=1738373164380162).|
|Content-Type {Required}|string|Request message type<br data-tomark-pass>Allowed format: `"application/json"`.  |
```

**Parameters**

```xtable
|Field{35%}|Data Type{15%}|Description{50%}|
|---|---|---|
|advertiser_id {Required}|string|Advertiser ID. |
|budget {+Conditional}|object[]| Either `budget` or `scheduled_budget` has to be set. <ul><li>To update the lifetime budgets of one or more ad groups, specify `budget`.</li><li>To update the daily budgets or dynamic daily budgets of one or more ad groups, specify `scheduled_budget`.</li></ul><br>Information about the new lifetime budgets that you want to set for one or more ad groups.<br><br>Max size: 20.<br><br><p><span style="color:darkred"><b>Note</b></span>: The changes to the lifetime budgets of one or more ad groups will take effect immediately. |
#|adgroup_id  {+Conditional}|string| Required when the object array `budget` is passed. <br><br>Ad group ID. |
#|budget  {+Conditional}|float| Required when the object array `budget` is passed. <br><br>The new lifetime budget for the ad group (`adgroup_id`). |
|scheduled_budget  {+Conditional}|object[]| Either `budget` or `scheduled_budget` has to be set. <ul><li>To update the lifetime budgets of one or more ad groups, specify `budget`.</li><li>To update the daily budgets or dynamic daily budgets of one or more ad groups, specify `scheduled_budget`.</li></ul><br>Information about the scheduled budget changes to the daily budgets or dynamic daily budgets of one or more ad groups.<br><br>Max size: 20.<br><br><p><span style="color:darkred"><b>Note</b></span>: The scheduled budget changes will take effect from 00:00 a.m. the following day in the ad account's time zone.|
#|adgroup_id  {+Conditional}|string| Required when the object array `scheduled_budget` is passed. <br><br>Ad group ID. |
#|scheduled_budget  {+Conditional} |float| Required when the object array `scheduled_budget` is passed. <br><br>The new daily budget or dynamic daily budget for the ad group (`adgroup_id`).|
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
    private static final String PATH = "/open_api/v1.3/adgroup/budget/update/";
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
        Long advertiser_id = ADVERTISER_ID;
        Double budget = BUDGET;
        Long adgroup_id = ADGROUP_ID;
        Long adgroup_id = ADGROUP_ID;
        Double scheduled_budget = SCHEDULED_BUDGET;

        // Args in JSON format
        String myArgs = String.format("{\"advertiser_id\": \"%s\", \"budget\": [{\"budget\": \"%s\", \"adgroup_id\": \"%s\"}], \"scheduled_budget\": [{\"adgroup_id\": \"%s\", \"scheduled_budget\": \"%s\"}]}",advertiser_id, budget, adgroup_id, adgroup_id, scheduled_budget);
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
PATH = "/open_api/v1.3/adgroup/budget/update/"


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
    advertiser_id = ADVERTISER_ID
    budget = BUDGET
    adgroup_id = ADGROUP_ID
    adgroup_id = ADGROUP_ID
    scheduled_budget = SCHEDULED_BUDGET

    # Args in JSON format
    my_args = "{\"advertiser_id\": \"%s\", \"budget\": [{\"budget\": \"%s\", \"adgroup_id\": \"%s\"}], \"scheduled_budget\": [{\"adgroup_id\": \"%s\", \"scheduled_budget\": \"%s\"}]}" % (advertiser_id, budget, adgroup_id, adgroup_id, scheduled_budget)
    print(post(my_args))

(/code)

(code PHP php)

$ACCESS_TOKEN = "xxx";
$PATH = "/open_api/v1.3/adgroup/budget/update/";

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


$advertiser_id = ADVERTISER_ID;
$budget = BUDGET;
$adgroup_id = ADGROUP_ID;
$adgroup_id = ADGROUP_ID;
$scheduled_budget = SCHEDULED_BUDGET;

/* Args in JSON format */
$my_args = sprintf("{\"advertiser_id\": \"%s\", \"budget\": [{\"budget\": \"%s\", \"adgroup_id\": \"%s\"}], \"scheduled_budget\": [{\"adgroup_id\": \"%s\", \"scheduled_budget\": \"%s\"}]}", $advertiser_id, $budget, $adgroup_id, $adgroup_id, $scheduled_budget);
echo post($my_args);
(/code)

(code curl http)
curl -H "Access-Token:xxx" -H "Content-Type:application/json" -X POST \
-d '{
    "advertiser_id": "ADVERTISER_ID",
    "budget": [
        {
            "budget": "BUDGET",
            "adgroup_id": "ADGROUP_ID"
        }
    ],
    "scheduled_budget": [
        {
            "adgroup_id": "ADGROUP_ID",
            "scheduled_budget": "SCHEDULED_BUDGET"
        }
    ]
}' \
https://business-api.tiktok.com/open_api/v1.3/adgroup/budget/update/
(/code)
```

## Response

```xtable
|Field|Data Type|Description|
|---|---|---|
|code |number|Response code. For the complete list of response codes and descriptions, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|message |string|Response message. For details, see [Appendix - Return Codes](https://ads.tiktok.com/marketing_api/docs?id=1737172488964097).|
|data |object|Return data|
|request_id |string|The log id of a request, which uniquely identifies the request.|
```

### Example

```xcodeblock
(code Success-Response http)
HTTPS/1.1 200 OK
{
    "message": "OK",
    "code": 0,
    "data": {},
    "request_id": "2020031110223101018904922300361F64"
}
(/code);
```

