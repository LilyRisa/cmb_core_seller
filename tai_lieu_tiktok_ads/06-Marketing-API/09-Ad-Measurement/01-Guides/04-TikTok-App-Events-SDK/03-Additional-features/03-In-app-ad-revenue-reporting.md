---
title: "In-app ad revenue reporting"
doc_id: 1862351923536898
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Additional features / In-app ad revenue reporting"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# In-app ad revenue reporting

Starting from SDK version 1.5.0, in-app advertising mediation revenue reporting is supported. Simply use the code examples below based on the mediation partner you are using.
> <span style="color:darkred">**Important**</span><br>
> For MAX and ironSource mediation, ensure all revenue is sent in USD to avoid reporting issues. 


## Android
### [MAX](https://developers.axon.ai/en/max/android/overview/advanced-settings/#impression-level-user-revenue-api)
```xcodeblock
(code curl http)
MaxRewardedAd rewardedAd = MaxRewardedAd.getInstance("YOUR_AD_UNIT_ID", this);
rewardedAd.setRevenueListener(new MaxAdRevenueListener() {
    @Override
    public void onAdRevenuePaid(MaxAd maxAd) {
        double revenue = maxAd.getRevenue();
        String countryCode = AppLovinSdk.getInstance(context).getConfiguration().getCountryCode();
        String networkName = maxAd.getNetworkName();
        String adUnitId = maxAd.getAdUnitId();
        String format = maxAd.getFormat().toString();
        String placement = maxAd.getPlacement();
        String networkPlacement = maxAd.getNetworkPlacement();

        JSONObject adRevenueJson = new JSONObject();
        try {
            adRevenueJson.put("revenue", revenue);
            adRevenueJson.put("country_code", countryCode);
            adRevenueJson.put("network_name", networkName);
            adRevenueJson.put("ad_unit_id", adUnitId);
            adRevenueJson.put("ad_format", format);
            adRevenueJson.put("placement", placement);
            adRevenueJson.put("network_placement", networkPlacement);
            adRevenueJson.put("device_ad_mediation_platform", "applovin_max_sdk");
        } catch (JSONException e) {
        }
        
        // Make sure the App Events SDK has been initialized before calling this
        TTBaseEvent adRevenueInfo = TTAdRevenueEvent.newBuilder(adRevenueJson).build();
        TikTokBusinessSdk.trackTTEvent(adRevenueInfo);
    }
});
(/code)
```

### [ironSource](https://developers.is.com/ironsource-mobile/android/ad-revenue-measurement-integration/#step-1)
```xcodeblock
(code curl http)
/**
 * If you want to add the ImpressionData listener to your application,
 * make sure you declare the listener before initializing the ironSource SDK,
 * to avoid any loss of information.
 */
IronSource.addImpressionDataListener(new ImpressionDataListener() {
    @Override
    public void onImpressionSuccess(ImpressionData impressionData) {
        String auctionId = impressionData.getAuctionId();
        String adUnit = impressionData.getAdUnit();
        String adNetwork = impressionData.getAdNetwork();
        String instanceName = impressionData.getInstanceName();
        String instanceId = impressionData.getInstanceId();
        String country = impressionData.getCountry();
        String placement = impressionData.getPlacement();
        Double revenue = impressionData.getRevenue();
        String precision = impressionData.getPrecision();
        String ab = impressionData.getAb();
        String segmentName = impressionData.getSegmentName();
        Double lifetimeRevenue = impressionData.getLifetimeRevenue();
        String encryptedCPM = impressionData.getEncryptedCPM();

        JSONObject adRevenueJson = new JSONObject();
        try {
            adRevenueJson.put("auction_id", auctionId);
            adRevenueJson.put("ad_unit", adUnit);
            adRevenueJson.put("ad_network", adNetwork);
            adRevenueJson.put("instance_name", instanceName);
            adRevenueJson.put("instance_id", instanceId);
            adRevenueJson.put("country", country);
            adRevenueJson.put("placement", placement);
            adRevenueJson.put("revenue", revenue);
            adRevenueJson.put("precision", precision);
            adRevenueJson.put("ab", ab);
            adRevenueJson.put("segment_name", segmentName);
            adRevenueJson.put("lifetime_revenue", lifetimeRevenue);
            adRevenueJson.put("encrypted_cpm", encryptedCPM);
            adRevenueJson.put("device_ad_mediation_platform", "ironsource_sdk");
        } catch (JSONException e) {
        }
        
        // Make sure the App Events SDK has been initialized before calling this
        TTBaseEvent adRevenueInfo = TTAdRevenueEvent.newBuilder(adRevenueJson).build();
        TikTokBusinessSdk.trackTTEvent(adRevenueInfo);
    }
});
(/code)
```

### [AdMob](https://developers.google.com/admob/android/impression-level-ad-revenue?hl=zh-cn)
```xcodeblock
(code curl http)
AdRequest adRequest = new AdRequest.Builder().build();
RewardedAd.load(this, "your ad unit ID", adRequest, new RewardedAdLoadCallback() {
    @Override
    public void onAdLoaded(@NonNull RewardedAd rewardedAd) {
        rewardedAd.setOnPaidEventListener(new OnPaidEventListener() {
            @Override
            public void onPaidEvent(@NonNull AdValue adValue) {
                AdapterResponseInfo loadedAdapterResponseInfo = rewardedAd.getResponseInfo().getLoadedAdapterResponseInfo();
                Bundle responseExtras = rewardedAd.getResponseInfo().getResponseExtras();
                JSONObject adRevenueJson = new JSONObject();

                long value = adValue.getValueMicros();
                String currencyCode = adValue.getCurrencyCode();
                int precisionType = adValue.getPrecisionType();
                String adUnitId = rewardedAd.getAdUnitId();
                String adSourceName = "";
                String adSourceId = "";
                String adSourceInstanceName = "";
                String adSourceInstanceId = "";
                String mediationGroupName = "";
                String mediationAbTestName = "";
                String mediationAbTestVariant = "";
                if (loadedAdapterResponseInfo != null){
                    adSourceName = loadedAdapterResponseInfo.getAdSourceName();
                    adSourceId = loadedAdapterResponseInfo.getAdSourceId();
                    adSourceInstanceName = loadedAdapterResponseInfo.getAdSourceInstanceName();
                    adSourceInstanceId = loadedAdapterResponseInfo.getAdSourceInstanceId();
                }
                if (responseExtras != null){
                    mediationGroupName = responseExtras.getString("mediation_group_name");
                    mediationAbTestName = responseExtras.getString("mediation_ab_test_name");
                    mediationAbTestVariant = responseExtras.getString("mediation_ab_test_variant");
                }
                try {
                    adRevenueJson.put("value", value);
                    adRevenueJson.put("currency_code", currencyCode);
                    adRevenueJson.put("precision", precisionType);
                    adRevenueJson.put("ad_unit_id", adUnitId);
                    adRevenueJson.put("ad_source_name", adSourceName);
                    adRevenueJson.put("ad_source_id", adSourceId);
                    adRevenueJson.put("ad_source_instance_name", adSourceInstanceName);
                    adRevenueJson.put("ad_source_instance_id", adSourceInstanceId);
                    adRevenueJson.put("mediation_group_name", mediationGroupName);
                    adRevenueJson.put("mediation_ab_test_name", mediationAbTestName);
                    adRevenueJson.put("mediation_ab_test_variant", mediationAbTestVariant);
                    adRevenueJson.put("device_ad_mediation_platform", "admob_sdk");
                    
                    //banner/interstitial/rewarded/rewarded interstitial/native/splash
                    adRevenueJson.put("ad_format", "rewarded");
                } catch (JSONException e) {
                }
                
                // Make sure the App Events SDK has been initialized before calling this
                TTBaseEvent adRevenueInfo = TTAdRevenueEvent.newBuilder(adRevenueJson).build();
                TikTokBusinessSdk.trackTTEvent(adRevenueInfo);
            }
        });
    }
});
(/code)
```

### Other mediation providers
```xcodeblock
(code curl http)
JSONObject adRevenueJson = new JSONObject();
try {
    adRevenueJson.put("device_ad_mediation_platform", "<publisher sdk>");
    adRevenueJson.put("value", "<value>");
    adRevenueJson.put("currency", "<ISO 3-letter currency code e.g. USD>");
    adRevenueJson.put("location_country", "<ISO 2-letter country code e.g. US>");
    adRevenueJson.put("ad_revenue_network", "<Ad source/network, e.g. AdMob>");
    adRevenueJson.put("ad_format", "Ad unit format, e.g. banner|interstitial|native|rewarded");
    adRevenueJson.put("precision", "Ad revenue precision level");
} catch (JSONException e) {
}
// Make sure the App Events SDK has been initialized before calling this
TTBaseEvent adRevenueInfo = TTAdRevenueEvent.newBuilder(adRevenueJson).build();
TikTokBusinessSdk.trackTTEvent(adRevenueInfo);
(/code)
```


## iOS
### [MAX](https://developers.axon.ai/en/max/ios/overview/advanced-settings#impression-level-user-revenue-api)
```xcodeblock
(code curl http)
// Obj-C

- (void)didPayRevenueForAd:(MAAd *)ad {
    
    double revenue = ad.revenue;
    NSString *countryCode = [ALSdk shared].configuration.countryCode;
    NSString *networkName = ad.networkName;
    NSString *adUnitId = ad.adUnitIdentifier;
    MAAdFormat *adFormat = ad.format;
    NSString *placement = ad.placement;
    NSString *networkPlacement = ad.networkPlacement;
    NSString *adUnitIdentifier = ad.adUnitIdentifier;
    
    NSMutableDictionary *adRevenue = NSMutableDictionary.dictionary;
    [adRevenue setObject:@"applovin_max_sdk" forKey:@"device_ad_mediation_platform"];
    [adRevenue setObject:@(revenue) forKey:@"revenue"];
    [adRevenue setObject:countryCode forKey:@"country_code"];
    [adRevenue setObject:networkName forKey:@"network_name"];
    [adRevenue setObject:adFormat.label forKey:@"ad_format"];
    [adRevenue setObject:placement forKey:@"placement"];
    [adRevenue setObject:networkPlacement forKey:@"network_placement"];
    [adRevenue setObject:adUnitIdentifier forKey:@"ad_unit_id"];
    
    // Make sure the App Events SDK has been initialized before calling this
    TikTokAdRevenueEvent *adRevenueEvent = [[TikTokAdRevenueEvent alloc] initWithAdRevenue:adRevenue];
    [TikTokBusiness trackTTEvent:adRevenueEvent];
}
(/code)
```

### [ironSource](https://developers.is.com/ironsource-mobile/ios/ad-revenue-measurement-integration/#step-1)
```xcodeblock
(code curl http)
// Obj-C

- (void)impressionDataDidSucceed:(ISImpressionData *)impressionData {
    
    NSString *auction_id = impressionData.auction_id;
    NSString *ad_unit = impressionData.ad_unit;
    NSString *ad_network = impressionData.ad_network;
    NSString *instance_name = impressionData.instance_name;
    NSString *instance_id = impressionData.instance_id;
    NSString *country = impressionData.country;
    NSString *placement = impressionData.placement;
    NSNumber *revenue = impressionData.revenue;
    NSString *precision = impressionData.precision;
    NSString *ab = impressionData.ab;
    NSString *segment_name = impressionData.segment_name;
    NSNumber *lifetime_revenue = impressionData.lifetime_revenue;
    NSString *encrypted_cpm = impressionData.encrypted_cpm;
    NSNumber *conversion_value = impressionData.conversion_value;
    
    NSMutableDictionary *adRevenue = NSMutableDictionary.dictionary;
    [adRevenue setObject:@"ironsource_sdk" forKey:@"device_ad_mediation_platform"];
    [adRevenue setObject:auction_id forKey:@"auction_id"];
    [adRevenue setObject:ad_unit forKey:@"ad_unit"];
    [adRevenue setObject:ad_network forKey:@"ad_network"];
    [adRevenue setObject:instance_name forKey:@"instance_name"];
    [adRevenue setObject:instance_id forKey:@"instance_id"];
    [adRevenue setObject:country forKey:@"country"];
    [adRevenue setObject:placement forKey:@"placement"];
    [adRevenue setObject:revenue forKey:@"revenue"];
    [adRevenue setObject:precision forKey:@"precision"];
    [adRevenue setObject:ab forKey:@"ab"];
    [adRevenue setObject:segment_name forKey:@"segment_name"];
    [adRevenue setObject:lifetime_revenue forKey:@"lifetime_revenue"];
    [adRevenue setObject:encrypted_cpm forKey:@"encrypted_cpm"];
    [adRevenue setObject:conversion_value forKey:@"conversion_value"];
    
    // Make sure the App Events SDK has been initialized before calling this
    TikTokAdRevenueEvent *adRevenueEvent = [[TikTokAdRevenueEvent alloc] initWithAdRevenue:adRevenue];
    [TikTokBusiness trackTTEvent:adRevenueEvent];
}
(/code)
```

### [AdMob](https://developers.google.com/admob/ios/impression-level-ad-revenue?hl=zh-cn)
```xcodeblock
(code curl http)
// Obj-C

@interface ViewController ()

@property(nonatomic, strong) GADRewardedAd *rewardedAd;

@end

@implementation ViewController

- (void)requestRewardedAd {
    
    __weak ViewController *weakSelf = self;
    GADRequest *request = [GADRequest request];
    
    [GADRewardedAd loadWithAdUnitID:@"ad unit ID"
                            request:request
                  completionHandler:^(GADRewardedAd *ad, NSError *error) {
        
        if (error) {
            NSLog(@"Rewarded ad failed to load with error: %@", [error localizedDescription]);
            return;
        }
        
        self.rewardedAd = ad;
        self.rewardedAd.paidEventHandler = ^void(GADAdValue *_Nonnull value) {
            
            ViewController *strongSelf = weakSelf;
            NSDecimalNumber *revenueValue = value.value;
            NSString *currencyCode = value.currencyCode;
            GADAdValuePrecision precision = value.precision;
            NSString *adUnitId = strongSelf.rewardedAd.adUnitID;
            GADAdNetworkResponseInfo *loadedAdNetworkResponseInfo = strongSelf.rewardedAd.responseInfo.loadedAdNetworkResponseInfo;
            NSString *adSourceName = loadedAdNetworkResponseInfo.adSourceName;
            NSString *adSourceID = loadedAdNetworkResponseInfo.adSourceID;
            NSString *adSourceInstanceName = loadedAdNetworkResponseInfo.adSourceInstanceName;
            NSString *adSourceInstanceID = loadedAdNetworkResponseInfo.adSourceInstanceID;
            NSDictionary<NSString *, id> *extras = strongSelf.rewardedAd.responseInfo.extrasDictionary;
            NSString *mediationGroupName = extras[@"mediation_group_name"];
            NSString *mediationABTestName = extras[@"mediation_ab_test_name"];
            NSString *mediationABTestVariant = extras[@"mediation_ab_test_variant"];
            
            NSMutableDictionary *adRevenue = NSMutableDictionary.dictionary;
            [adRevenue setObject:@"admob_sdk" forKey:@"device_ad_mediation_platform"];
            [adRevenue setObject:revenueValue forKey:@"value"];
            [adRevenue setObject:currencyCode forKey:@"currency_code"];
            [adRevenue setObject:@(precision) forKey:@"precision"];
            [adRevenue setObject:adUnitId forKey:@"ad_unit_id"];
            [adRevenue setObject:adSourceName forKey:@"ad_source_name"];
            [adRevenue setObject:adSourceID forKey:@"ad_source_id"];
            [adRevenue setObject:adSourceInstanceName forKey:@"ad_source_instance_name"];
            [adRevenue setObject:adSourceInstanceID forKey:@"ad_source_instance_id"];
            [adRevenue setObject:mediationGroupName forKey:@"mediation_group_name"];
            [adRevenue setObject:mediationABTestName forKey:@"mediation_ab_test_name"];
            [adRevenue setObject:mediationABTestVariant forKey:@"mediation_ab_test_variant"];

            // banner/interstitial/rewarded/rewarded interstitial/native/splash
            [adRevenue setObject:@"rewarded" forKey:@"ad_format"];

            // Make sure the App Events SDK has been initialized before calling this
            TikTokAdRevenueEvent *adRevenueEvent = [[TikTokAdRevenueEvent alloc] initWithAdRevenue:adRevenue];
            [TikTokBusiness trackTTEvent:adRevenueEvent];
        };
    }];
}

@end
(/code)
```

### Other mediation providers
```xcodeblock
(code curl http)
// Obj-C

NSMutableDictionary *adRevenue = NSMutableDictionary.dictionary;
[adRevenue setObject:@"<publisher sdk>" forKey:@"device_ad_mediation_platform"];
[adRevenue setObject:@"<value>" forKey:@"value"];
[adRevenue setObject:@"<ISO 3-letter currency code e.g. USD>" forKey:@"currency"];
[adRevenue setObject:@"<ISO 2-letter country code e.g. US>" forKey:@"location_country"];
[adRevenue setObject:@"<Ad source/network, e.g. AdMob>" forKey:@"ad_revenue_network"];
[adRevenue setObject:@"Ad unit format, e.g. banner|interstitial|native|rewarded" forKey:@"ad_format"];
[adRevenue setObject:@"Ad revenue precision level" forKey:@"precision"];

// Make sure the App Events SDK has been initialized before calling this
TikTokAdRevenueEvent *adRevenueEvent = [[TikTokAdRevenueEvent alloc] initWithAdRevenue:adRevenue];
[TikTokBusiness trackTTEvent:adRevenueEvent];
(/code)
```


## Unity
### [MAX](https://developers.axon.ai/en/max/unity/overview/advanced-settings/#ad-placements)
```xcodeblock
(code curl http)
private void OnAdRevenuePaidEvent(string adUnitId, MaxSdk.AdInfo adInfo)
{
  double revenue = adInfo.Revenue;

  // Miscellaneous data
  string countryCode = MaxSdk.GetSdkConfiguration().CountryCode;
  string networkName = adInfo.NetworkName; 
  string adUnitIdentifier = adInfo.AdUnitIdentifier; 
  string placement = adInfo.Placement; 
  string networkPlacement = adInfo.NetworkPlacement; 
  string format = adInfo.AdFormat;
  
  Dictionary<string, object> adRevenue = new Dictionary<string, object>();
  adRevenue.Add("device_ad_mediation_platform","applovin_max_sdk");
  adRevenue.Add("revenue",revenue);
  adRevenue.Add("country_code",countryCode);
  adRevenue.Add("network_name",networkName);
  adRevenue.Add("ad_format",format);  
  adRevenue.Add("ad_unit_id",adUnitIdentifier);
  adRevenue.Add("placement",placement);
  adRevenue.Add("network_placement",networkPlacement);
  
  // Make sure the App Events SDK has been initialized before calling this
  TikTokAdRevenueEvent adRevenueEvent = new TikTokAdRevenueEvent(adRevenue);
  TikTokBusinessSDK.TrackTTEvent(adRevenueEvent);
}
(/code)
```

### [ironSource](https://developers.is.com/ironsource-mobile/unity/ad-revenue-measurement-integration/)
```xcodeblock
(code curl http)
private void ImpressionDataReadyEvent(IronSourceImpressionData impressionData)
{
    String auction_id = impressionData.auctionId;
    String ad_unit = impressionData.adUnit;
    String ad_network = impressionData.adNetwork;
    String instance_name = impressionData.instanceName;
    String instance_id = impressionData.instanceId;
    String country = impressionData.country;
    String placement = impressionData.placement;
    Double revenue = impressionData.revenue;
    String precision = impressionData.precision;
    String ab = impressionData.ab;
    String segment_name = impressionData.segmentName;
    Double lifetime_revenue = impressionData.lifetimeRevenue;
    String encrypted_cpm = impressionData.encryptedCPM;
    String conversion_value = impressionData.creativeId;
    
    Dictionary<string, object> adRevenue = new Dictionary<string, object>();
    adRevenue.Add("device_ad_mediation_platform","ironsource_sdk");
    adRevenue.Add("auction_id",auction_id);
    adRevenue.Add("ad_unit",ad_unit);
    adRevenue.Add("ad_network",ad_network);
    adRevenue.Add("instance_name",instance_name);
    adRevenue.Add("instance_id",instance_id);
    adRevenue.Add("country",country);
    adRevenue.Add("placement",placement);
    adRevenue.Add("revenue",revenue);
    adRevenue.Add("precision",precision);
    adRevenue.Add("ab",ab);
    adRevenue.Add("segment_name",segment_name);
    adRevenue.Add("lifetime_revenue",lifetime_revenue);
    adRevenue.Add("encrypted_cpm",encrypted_cpm);
    adRevenue.Add("conversion_value",conversion_value);
    
    // Make sure the App Events SDK has been initialized before calling this
    TikTokAdRevenueEvent adRevenueEvent = new TikTokAdRevenueEvent(adRevenue);
    TikTokBusinessSDK.TrackTTEvent(adRevenueEvent);
}
(/code)
```

### [AdMob](https://developers.google.com/admob/unity/impression-level-ad-revenue?hl=zh-cn)
```xcodeblock
(code curl http)
private RewardedAd _rewardedAd;

private void LoadRewardedAd()
{
    AdRequest adRequest = new AdRequest();
    RewardedAd.Load("AD_UNIT_ID", adRequest, (RewardedAd rewardedAd, LoadAdError error) =>
   {      
       _rewardedAd = rewardedAd;
       if (error != null)      
       {         
           Debug.LogError("Rewarded ad failed to load an ad with error : " + error);              return;      
       }      
       rewardedAd.OnAdPaid += this.HandleAdPaidEvent;   
  });
}

public void HandleAdPaidEvent(AdValue adValue)
{
    long valueMicros = adValue.Value;    
    string currencyCode = adValue.CurrencyCode;    
    PrecisionType precision = adValue.Precision;    
    ResponseInfo responseInfo = rewardedAd.GetResponseInfo();    
    string responseId = responseInfo.GetResponseId();    
    AdapterResponseInfo loadedAdapterResponseInfo =  responseInfo.GetLoadedAdapterResponseInfo();    
    string adSourceId = loadedAdapterResponseInfo.AdSourceId;    
    string adSourceInstanceId = loadedAdapterResponseInfo.AdSourceInstanceId;    
    string adSourceInstanceName = loadedAdapterResponseInfo.AdSourceInstanceName;    
    string adSourceName = loadedAdapterResponseInfo.AdSourceName;    
    string adapterClassName = loadedAdapterResponseInfo.AdapterClassName;    
    long latencyMillis = loadedAdapterResponseInfo.LatencyMillis;             
    Dictionary credentials = loadedAdapterResponseInfo.AdUnitMapping; 
    Dictionary extras = responseInfo.GetResponseExtras();    
    string mediationGroupName = extras["mediation_group_name"];    
    string mediationABTestName = extras["mediation_ab_test_name"];    
    string mediationABTestVariant = extras["mediation_ab_test_variant"];
    
    Dictionary adRevenue = new Dictionary();
    adRevenue.Add("device_ad_mediation_platform","admob_sdk");
    adRevenue.Add("value",valueMicros);
    adRevenue.Add("currency_code",currencyCode);
    adRevenue.Add("precision",precision);
    adRevenue.Add("ad_unit_id",_rewardedAd.getAdUnitId());   
    adRevenue.Add("ad_source_name",adSourceName);
    adRevenue.Add("ad_source_id",adSourceId);
    adRevenue.Add("ad_source_instance_name",adSourceInstanceName);
    adRevenue.Add("ad_source_instance_id",adSourceInstanceId);
    adRevenue.Add("mediation_group_name",mediationGroupName);
    adRevenue.Add("mediation_ab_test_name",mediationABTestName);
    adRevenue.Add("mediation_ab_test_variant",mediationABTestVariant);
    *            *
    *//banner/interstitial/rewarded/rewarded interstitial/native/app open ad*
    adRevenue.Add("ad_format","rewarded");
 
    // Make sure the App Events SDK has been initialized before calling this
    TikTokAdRevenueEvent adRevenueEvent = new TikTokAdRevenueEvent(adRevenue);
    TikTokBusinessSDK.TrackTTEvent(adRevenueEvent);

}
(/code)
```

### Other mediation providers
```xcodeblock
(code curl http)
Dictionary<string, object> adRevenue = new Dictionary<string, object>();
adRevenue.Add("device_ad_mediation_platform","<publisher sdk>");
adRevenue.Add("value","<value>");
adRevenue.Add("currency","<ISO 3-letter currency code e.g. USD>");
adRevenue.Add("location_country","<ISO 2-letter country code e.g. US>");
adRevenue.Add("ad_revenue_network","<Ad source/network, e.g. AdMob>");
adRevenue.Add("ad_format","Ad unit format, e.g. banner|interstitial|native|rewarded");  
adRevenue.Add("precision","Ad revenue precision level");

// Make sure the App Events SDK has been initialized before calling this
TikTokAdRevenueEvent adRevenueEvent = new TikTokAdRevenueEvent(adRevenue);
TikTokBusinessSDK.TrackTTEvent(adRevenueEvent);
(/code)
```



