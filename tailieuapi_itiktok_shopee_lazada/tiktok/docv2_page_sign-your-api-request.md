# Sign your API request

> Source: https://partner.tiktokshop.com/docv2/page/sign-your-api-request
> Section: Developer Guide
> Scraped: 2026-05-21T00:23:34.833Z

---

## Introduction

When calling a TikTok Shop API, a signature must be included to properly authenticate your request. All requests that do not include a signature, or have an invalid signature, will be denied access.  
TikTok Shop APIs use **HMAC-SHA256** as the default algorithm for generating signatures.  
**HS256 (HMAC with SHA-256)** is a symmetric algorithm, meaning there is only one private key that is shared between the two parties. Since the same key is used to both generate and validate the signature, care must be taken to ensure that the key is not compromised.  
When TikTok Shop receives an authenticated request, it recreates the signature using the authentication information contained in the request. If the signatures match, the service processes the request. Otherwise, it rejects the request.

❗ **Important**: TikTok Shop APIs use your **app secret** as the private key for authentication. You can obtain your **app secret** from your app details page in Partner Center.

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/c720f67189004b1bafa17b9a376e372b~tplv-k9wyc2ijk0-image.image)

## Code Samples

You can find the code samples in GoLang, Java, and Node.js below. With the code samples, you do not need to dig deep into the algorithm. If you need code samples in other languages, please highlight the sentence and provide feedback for us.

### Signature algorithm (Go)

GO

Word Wrap

```
import (    
    "crypto/hmac"    
    "crypto/sha256"    
    "encoding/hex"    
    "io"    
    "net/http"    
    "sort"    
)    
    
// secret: App secret    
func CalSign(req *http.Request, secret string) string {    
    queries := req.URL.Query()    
    
    // extract all query parameters excluding sign and access_token    
    keys := make([]string, len(queries))    
    idx := 0    
    for k := range queries {    
        // params except 'sign' & 'access_token'    
        if k != "sign" && k != "access_token" {    
            keys[idx] = k    
            idx++    
        }    
    }    
        
    // reorder the parameters' key in alphabetical order    
    sort.Slice(keys, func(i, j int) bool {    
        return keys[i] < keys[j]    
    })    
    
    // Concatenate all the parameters in the format of {key}{value}    
    input := ""    
    for _, key := range keys {    
        input = input + key + queries.Get(key)    
    }    
    
    // append the request path    
    input = req.URL.Path + input    
    
    // if the request header Content-type is not multipart/form-data, append body to the end    
    mediaType, _, _ := mime.ParseMediaType(req.Header.Get("Content-type"))    
    if mediaType != "multipart/form-data" {    
        body, _ := io.ReadAll(req.Body)    
        input = input + string(body)    
            
        req.Body.Close()    
        // reset body after reading from the original    
        req.Body = io.NopCloser(bytes.NewReader(body))    
    }    
    
    // wrap the string generated in step 5 with the App secret    
    input = secret + input + secret    
    
    return generateSHA256(input, secret)    
}    
    
func generateSHA256(input, secret string) string {    
    // encode the digest byte stream in hexadecimal and use sha256 to generate sign with salt(secret)    
    h := hmac.New(sha256.New, []byte(secret))    
    
    if _, err := h.Write([]byte(input)); err != nil {    
        // TODO error log    
        return ""    
    }    
    
    return hex.EncodeToString(h.Sum(nil))    
}
```

### Signature algorithm (Java)

JAVA

Word Wrap

```
package org.example;  
  
import okhttp3.HttpUrl;  
import okhttp3.Request;  
import okhttp3.RequestBody;  
import okio.Buffer;  
  
import javax.crypto.Mac;  
import javax.crypto.spec.SecretKeySpec;  
import java.nio.charset.StandardCharsets;  
import java.util.ArrayList;  
import java.util.Collections;  
import java.util.List;  
  
public class Main {  
    public String generateSignature(Request request, String secret) {  
    HttpUrl httpUrl = request.url();  
    List<String> parameterNameList = new ArrayList<>(httpUrl.queryParameterNames());  
  
    // extract all query parameters excluding sign and access_token  
    parameterNameList.removeIf(param -> "sign".equals(param) || "access_token".equals(param));  
  
    // reorder the parameters' key in alphabetical order  
    Collections.sort(parameterNameList);  
  
    // append the request path  
    StringBuilder parameterStr = new StringBuilder(httpUrl.encodedPath());  
    for (String parameterName : parameterNameList) {  
        // Concatenate all the parameters in the format of {key}{value}  
        parameterStr.append(parameterName).append(httpUrl.queryParameter(parameterName));  
    }  
  
    // if the request header Content-type is not multipart/form-data, append body to the end  
    String contentType = request.header("Content-Type");  
    if (!"multipart/form-data".equalsIgnoreCase(contentType)) {  
        try {  
            RequestBody requestBody = request.body();  
            if (requestBody != null) {  
                Buffer bodyBuffer = new Buffer();  
                requestBody.writeTo(bodyBuffer);  
                byte[] bodyBytes = bodyBuffer.readByteArray();  
                parameterStr.append(new String(bodyBytes, StandardCharsets.UTF_8));  
            }  
        } catch (Exception e) {  
            throw new RuntimeException("failed to generate signature params", e);  
        }  
    }  
  
    // wrap the string generated in step 5 with the App secret  
    String signatureParams = secret + parameterStr + secret;  
  
    // encode wrapped string using HMAC-SHA256  
    return generateSHA256(signatureParams, secret);  
}  
  
/**  
 * generate signature by SHA256  
 * @param signatureParams signature params  
 * @return signature  
 */  
public String generateSHA256(String signatureParams, String secret) {  
    try {  
        // Get an HmacSHA256 Mac instance and initialize with the secret key  
        Mac sha256HMAC = Mac.getInstance("HmacSHA256");  
        SecretKeySpec secretKeySpec = new SecretKeySpec(secret.getBytes(StandardCharsets.UTF_8), "HmacSHA256");  
        sha256HMAC.init(secretKeySpec);  
  
        // Update with input data  
        sha256HMAC.update(signatureParams.getBytes(StandardCharsets.UTF_8));  
  
        // Compute the HMAC and get the result  
        byte[] hashBytes = sha256HMAC.doFinal();  
  
        // Convert to hex string  
        StringBuilder sb = new StringBuilder();  
        for (byte hashByte : hashBytes) {  
            sb.append(String.format("%02x", hashByte & 0xff));  
        }  
  
        return sb.toString();  
    } catch (Exception e) {  
        throw new RuntimeException("failed to generate signature result", e);  
    }  
}  
}
```

### Signature algorithm (Node.js)

TYPESCRIPT

Word Wrap

```
import crypto from "crypto";  
import localVarRequest from "request";  
const excludeKeys = ["access_token", "sign"] as const;  
export const generateSign = (  
  requestOption: localVarRequest.Options,  
  app_secret: string  
) => {  
  let signString = "";  
  // step1: Extract all query parameters excluding sign and access_token. Reorder the parameter keys in alphabetical order:  
  const params = requestOption.qs || {};  
  const sortedParams = Object.keys(params)  
    .filter((key) => !excludeKeys.includes(key as any))  
    .sort()  
    .map((key) => ({ key, value: params[key] }));  
  //step2: Concatenate all the parameters in the format {key}{value}:  
  const paramString = sortedParams  
    .map(({ key, value }) => `${key}${value}`)  
    .join("");  
  
  signString += paramString;  
  
  //step3: Append the string from Step 2 to the API request path:  
// @ts-ignore  
  const pathname = new URL(requestOption!.uri!||'').pathname;  
  
  signString = `${pathname}${paramString}`;  
  
  //step4: If the request header content-type is not multipart/form-data, append the API request body to the string from Step 3:  
  if (  
    requestOption.headers?.["content-type"] !== "multipart/form-data" &&  
    requestOption.body &&  
    Object.keys(requestOption.body).length  
  ) {  
    const body = JSON.stringify(requestOption.body);  
    signString += body;  
  }  
  
  //step5: Wrap the string generated in Step 4 with the app_secret:  
  signString = `${app_secret}${signString}${app_secret}`;  
  
  //step6: Encode your wrapped string using HMAC-SHA256:  
  const hmac = crypto.createHmac("sha256", app_secret);  
  hmac.update(signString);  
  const sign = hmac.digest("hex");  
  
  return sign;  
};
```

### Signature algorithm (Python)

PYTHON

Word Wrap

```
import hmac  
import hashlib  
from urllib.parse import urlparse  
import json  
  
def generate_sign(request_option, app_secret):  
    """  
    Generate HMAC-SHA256 signature  
    :param request_option: Request options dictionary containing qs (query params), uri (path), headers, body etc.  
    :param app_secret: Secret key for signing  
    :return: Hexadecimal signature string  
    """  
    # Step 1: Extract and filter query parameters, exclude "access_token" and "sign", sort alphabetically  
    params = request_option.get('qs', {})  
    exclude_keys = ["access_token", "sign"]  
    sorted_params = [  
        {"key": key, "value": params[key]}  
        for key in sorted(params.keys())  
        if key not in exclude_keys  
    ]  
  
    # Step 2: Concatenate parameters in {key}{value} format  
    param_string = ''.join([f"{item['key']}{item['value']}" for item in sorted_params])  
    sign_string = param_string  
  
    # Step 3: Append API request path to the signature string  
    uri = request_option.get('uri', '')  
    pathname = urlparse(uri).path if uri else ''  
    sign_string = f"{pathname}{param_string}"  
  
    # Step 4: If not multipart/form-data and request body exists, append JSON-serialized body  
    content_type = request_option.get('headers', {}).get('content-type', '')  
    body = request_option.get('body', {})  
    if content_type != 'multipart/form-data' and body:  
        body_str = json.dumps(body)  # JSON serialization ensures consistency  
        sign_string += body_str  
  
    # Step 5: Wrap signature string with app_secret  
    wrapped_string = f"{app_secret}{sign_string}{app_secret}"  
  
    # Step 6: Encode using HMAC-SHA256 and generate hexadecimal signature  
    hmac_obj = hmac.new(  
        app_secret.encode('utf-8'),  
        wrapped_string.encode('utf-8'),  
        hashlib.sha256  
    )  
    sign = hmac_obj.hexdigest()  
    return sign
```

## Step-by-step breakdown

![](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/81bed8cd51fe4913bab0c5d9979e2de0~tplv-k9wyc2ijk0-image.image)

The following is a step-by-step breakdown of how the signature algorithm works:

📌 **Note**: Feel free to skip straight to the **signature algorithm** sections if you do not need a step-by-step breakdown. We've provided sample code in [Go](<#Signature algorithm \(Go\)>), [Java](<#Signature algorithm \(Java\)>), and [Node.js](<#Signature algorithm \(Node.js\)>)

1.  As an example, let's assume you'd like to call the [Get Authorized Shops](get-authorized-shops) endpoint and your `app_secret` is **e59af819cc**:

HTTP

Word Wrap

```
curl -X GET \  
 'https://open-api.tiktokglobalshop.com/authorization/202309/shops?app_key=29a39d&sign=bc721f0e0182914e3487b81df204de37a352fc3aa96947efda6dc1e5dd0d5290&timestamp=1623812664' \  
-H 'x-tts-access-token: TTP_pwSm2AAAAABmmtFz1xlyKMnwg74T2GJ5s0uQbS8jPjb_GkdFVCxPqzQXSyuyfXdQa0AqyDsea2tYFNVf4XeqgZHFfPyv0Vs659QqyLYfsGzanZ5XZAin3_ZkcIxxS0_In6u6XDeU96k' \  
-H 'content-type: application/json'
```

1.  Extract all query parameters excluding `sign` and `access_token`. Reorder the parameter keys in alphabetical order:

GO

Word Wrap

```
keys := make([]string, len(queries))      
idx := 0      
for k := range queries {      
    // params except 'sign' & 'access_token'     
    if k != "sign" && k != "access_token" {      
       keys[idx] = k      
       idx++      
    }      
}      
sort.Slice(keys, func(i, j int) bool {      
    return keys[i] < keys[j]      
})
```

The resulting reordered query keys is:

PLAINTEXT

Word Wrap

```
keys = []string{      
    "app_key",         
    "timestamp"      
}
```

📌 **Note**: Some API endpoints will require the **shop\_cipher** query parameter, which would then be included in the reordered query keys. Please call [Get Authorized Shops](https://partner.tiktokshop.com/docv2/page/get-authorized-shops) to obtain a shop's corresponding **shop\_cipher**.

1.  Concatenate all the parameters in the format `{key}{value}`:

GO

Word Wrap

```
// Concatenate all the parameters in the format of {key}{value}    
input := ""      
for _, key := range keys {        
   input = input + key + queries[key]        
}
```

The resulting string becomes:

PLAINTEXT

Word Wrap

```
app_key29a39dtimestamp1623812664
```

1.  Append the string from Step 3 to the API request path. The path for [Get Authorized Shops](get-authorized-shops) is `/authorization/202309/shops`:

PLAINTEXT

Word Wrap

```
input = path + input
```

The resulting string becomes:

PLAINTEXT

Word Wrap

```
/authorization/202309/shopsapp_key29a39dtimestamp1623812664
```

📌 **Note**: If the request header content-type is not multipart/form-data, append the API request body to the string as well.

PLAINTEXT

Word Wrap

```
input = input + body
```

For example, the [Update Shop Webhook](https://partner.tiktokshop.com/docv2/page/update-shop-webhook) endpoint includes the address and event\_type parameters in the API request body (as well as the shop\_cipher parameter in the query parameters). This would result in the following:

PLAINTEXT

Word Wrap

```
/event/202309/webhooksapp_key68xu9ks5p4i8shop_cipherROW_xkMbgAAAeVAQra0eZWebFQq5aIKtimestamp1696909648{    "address":"https://partner.tiktokshop.com",    "event_type": "PACKAGE_UPDATE"}
```

1.  Wrap the string generated in Step 4 with your `app_secret`:

PLAINTEXT

Word Wrap

```
input = app_secret + input + app_secret
```

The final string becomes:

PLAINTEXT

Word Wrap

```
e59af819cc/authorization/202309/shopsapp_key29a39dtimestamp1623812664e59af819cc
```

1.  Encode your wrapped string using **HMAC-SHA256**:

GO

Word Wrap

```
import (        
   "crypto/hmac"        
   "crypto/sha256"        
   "encoding/hex"        
   "sort"        
)        
/**        
** input: string we created in step 5     
** secret: App secret        
**/        
func generateSHA256(input, secret string) string{        
   // encode the digest byte stream in hexadecimal and use sha256 to generate sign with salt(secret)        
   h := hmac.New(sha256.New, []byte(secret))        
    
   if _, err := h.Write([]byte(input)); err != nil{        
      // TODO error log     
      return ""        
   }        
        
   return hex.EncodeToString(h.Sum(nil))        
}
```

The resulting sign is:

PLAINTEXT

Word Wrap

```
b596b73e0cc6de07ac26f036364178ab16b0a907af13d43f0a0cd2345f582dc8
```

## Common mistakes

What is the most common reason for encountering the "signature is invalid" error during an API call?

-   Frequently, this error occurs when developers use incorrect app keys and secrets to generate the signature. It's essential to verify that the app key and secret match precisely.
    
-   Ensure `sign` and `access_token` are **not** included in the reordered query keys (Step 2).
    
-   Always ensure that you are using the **HMAC-SHA256** signature method (different from regular SHA-256).
    
-   Ensure that the timestamp is within 5 minutes of the current time when the platform receives the request. The timestamp must be represented as a 10-digit Unix timestamp.
