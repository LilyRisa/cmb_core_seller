---
title: "Deeplinks and deferred deeplinks"
doc_id: 1862348999629826
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Additional features / Deeplinks and deferred deeplinks"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Deeplinks and deferred deeplinks

## Deeplinks
Deeplinks are handled on the native app level. If you have already implemented support in line with the relevant [Android](https://developer.android.com/training/app-links) and [Apple](https://developer.apple.com/documentation/xcode/allowing-apps-and-websites-to-link-to-your-content) developer guidelines, no further work is required. The following code snippets are reference examples on how to add the relevant listeners to your app, and are provided as-is.
### Supported deeplink types
TikTok Ads Manager currently supports the following deeplink types:
- Android App Links: These links work similarly to universal links but are tailored for Android devices. For example, https://yourappdomain.com/path/to/content
- Apple Universal Links: These are links that provide a seamless experience for users on iOS devices. For example, https://yourappdomain.com/path/to/content
- URI Schema Links: These are traditional deeplinks that use custom Uniform Resource Identifier (URI) schemas to open the app. For example, yourappschema://path/to/content

## Deferred deeplinks
Deferred deeplinks allow you to direct users to specific app pages after your app is installed and launched for the first time. You can add the following code after initialising the SDK to fetch and handle the deferred deeplink.This method should be called from the initialisation success callback** **to ensure it runs after the SDK is ready. You may also want to only call this method once, on the first app launch, to avoid unexpected redirects on subsequent launches.
> <span style="color: DodgerBlue">**Note**</span><br>
> For deferred deeplinks, only URI schema links are supported.

### Android
```xcodeblock
(code curl http)
// Request a deferred deeplink using this method after successful SDK initialisation
TikTokBusinessSdk.fetchDeferredDeeplinkWithCompletion(new TikTokBusinessSdk.FetchDeferredDeeplinkCompletion() {
    @Override
    public void completion(String deepLinkUrl, ErrorData errorData) {
        if(TextUtils.isEmpty(deepLinkUrl)){
            Log.e("DeferredDeeplink","Failed to fetch deferred deeplink. Code:" + errorData.getCode() + " Msg:"+ errorData.getMsg());
        } else {
            Log.e("DeferredDeeplink","Deferred deeplink fetched:" + deepLinkUrl);
            Uri uri = Uri.parse(deepLinkUrl);
            Intent intent = new Intent(Intent.ACTION_VIEW);
            intent.setData(uri);
            getActivity().startActivity(intent);
        }
    }
});
(/code)
```

### iOS
```xcodeblock
(code curl http)
// Swift
// Request a deferred deeplink using this method after successful SDK initialisation
TikTokBusiness.fetchDeferredDeeplink { url, error **in**
    **if** error != **nil** {
        print("Failed to fetch deferred deeplink: \(String(describing: error?.localizedDescription))")
    } **else** {
        print("Deferred deeplink fetched: \(url!.absoluteString)")
        **if** url != **nil** && UIApplication.shared.canOpenURL(url!) {
            UIApplication.shared.open(url!, options: [:], completionHandler: **nil**)
        }
    }
}
(/code)
```

```xcodeblock
(code curl http)
// Objective-C
// Request a deferred deeplink using this method after successful SDK initialisation
[TikTokBusiness fetchDeferredDeeplinkWithCompletion:^(NSURL * **_Nullable** url, NSError * **_Nullable** error) {
    **if** (error) {
        NSLog(@"Failed to fetch deferred deeplink: %@", error.localizedDescription);
    } **else** {
        NSLog(@"Deferred deeplink fetched: %@", url.absoluteString);
        **if** (url && [[UIApplication sharedApplication] canOpenURL:url]) {
            [[UIApplication sharedApplication] openURL:url options:@{} completionHandler:**nil**];
        }
    }
}];
(/code)
```

### Unity
```xcodeblock
(code curl http)
// Request a deferred deeplink using this method after successful SDK initialisation
void InitHandle(bool result, int code, string message)
{
    _deeplinkHandle = DeeplinkHandle;
    TikTokBusinessSDK.FetchDeferredDeeplink(_deeplinkHandle);
}

void DeeplinkHandle(string url, int code, string message)
{
    if (code != 0) {
        Debug.Log("Failed to fetch deferred deeplink. Code: " + code + " Msg: " + message);
    } else {
        Debug.Log("Deferred deeplink fetched:"+ url);
    }
}
(/code)
```



