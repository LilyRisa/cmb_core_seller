---
title: "Release history"
doc_id: 1739584951798785
path: "Marketing API / Ad Measurement / Guides / TikTok App Events SDK / Release history"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Release history

<table><thead>
  <tr>
    <th  style="width:20%">Date</th>
    <th  style="width:20%">Version</th>
    <th  style="width:60%">Updates</th>
  </tr></thead>
<tbody>
    <tr>
      <td>4/6/2026</td>
      <td>1.6.1</td>
      <td>This version focuses on stability, accuracy, and performance improvements. Developer documentation is also restructured and rewritten for clarity.<ul><li>[Fix] Double-hashing protections for advanced matching fields.</li><li>[Fix] Crash reports are now sent with the version of the SDK related to the crash, in case the SDK dependency was updated before the crash report was sent.</li><li>[Fix] Moved event reporting off the main thread to improve performance.</li><li>[Fix] iOS: Fixed an issue where automatically logged Purchase events would be sent multiple times before getting deduplicated at server side.</li><li>[Fix] iOS: Multiple optimisations for queue management, logging, and request handling.</li><li>[Fix] Unity: Fixed a compile error in the Android bridge layer.</li></ul></td>
    </tr>
    <tr>
    <td>12/2/2025<br></td>
    <td>1.6.0<br></td>
    <td>[Deprecation] Loan-related event constants are now deprecated.<br>[Feature] Android: Installer store package reporting support<br>[Feature] Android: Play Store billing library transactions are automatically reported by default. To avoid issues with existing manual implementations, disable automatic transaction reporting before updating and releasing your app. See 2.2 Auto IAP reporting for more detail.<br>[Feature] Subscription-related parameter support for automatic transaction reporting. Server-side Play Console/App Store Connect subscription reporting is not supported at this time.<br>[Feature] Deferred deeplink requests now return an associated click timestamp where available.<br>[Fix] Stability optimisations</td>
  </tr>
   <tr>
    <td> 9/3/2025</td>
    <td>iOS: 1.5.1<br>Unity: 1.5.1</td>
    <td>[Fix] iOS: crash fixes</td>
  </tr>
  <tr>
    <td>8/1/2025</td>
    <td>iOS: 1.5.0<br>Unity: 1.5.0</td>
    <td>[Feature] Ad revenue mediation postback support, see iOS/Unity integration guide section 3.3 for details<br>[Feature] Full support for token-based test events, see iOS integration guide section 4.1 and Unity integration guide section 5.1 for details.<br>[Feature] Updates to support upcoming Advanced Predictive Measurement.</td>
  </tr>
  <tr>
    <td>7/15/2025</d>
    <td>Android: 1.5.0</dh>
    <td>[Feature] Ad revenue mediation postback support, see Android integration guide section 3.3 for details<br>[Feature] Full support for token-based test events, see Android integration guide section 4.1 for details.</d>
  </tr>
  <tr>
    <td>6/12/2025</td>
    <td>iOS: 1.4.1<br>Android: 1.4.1<br>Unity: 1.4.1@1.4.1</td>
    <td>[Fix] iOS: crash fixes<br>[Fix] Android: crash fixes</td>
  </tr>
  <tr>
    <td>5/19/2025</td>
    <td>iOS: 1.4.0<br>Android: 1.4.0<br>Unity: 1.4.0@1.4.0<br></td>
    <td>[Feature] Automatic event_id generation when none is specified<br>[Feature] session_activity support<br>[Feature] Enhance data postback support. See Android integration step 2.4 or iOS/Unity integration step 2.3 for details.<br>[Deprecation] Deprecated disableAppTrackingDialog for iOS. The SDK will no longer proactively display the ATT dialog if ATT status is not set. See iOS integration step 3.4.6 or Unity integration step 3.4.5 for details. <br>[Fix]  android: fix get gp referrer anr<br>[Fix] change "price" to string</td>
  </tr>
  <tr>
    <td>4/01/2025</td>
    <td>7.1.0.4@1.3.7</td>
    <td>Added documentation for a new combined TikTok App Events and Pangle SDK.</td>
  </tr>
  <tr>
    <td>3/10/2025</td>
    <td>iOS: 1.3.8<br>Android: 1.3.8<br></td>
    <td>[Feature] Deferred deep link support<br>[Feature] Session ID support<br>[Feature] Access token and payload encryption support<br>[Feature] Initial support for token-based test events<br>[Feature] Android: Google Play Install Referrer support<br>[Fix] iOS: Swift Package Manager support<br>[Fix] Other misc bugfixes</td>
  </tr>
  <tr>
    <td>1/3/2025</td>
    <td>Unity: 0.0.2@1.3.7</td>
    <td>Initial release</td>
  </tr>
  <tr>
    <td>12/18/2024</td>
    <td>iOS: 1.3.7-1</td>
    <td>[Fix] Updated SKAN conversion value handling to address NSCFNumber errors.</td>
  </tr>
  <tr>
    <td>11/29/2024<br></td>
    <td>Beta 1.3.7 (Android)</td>
    <td>[Feature] Support server-side control for automatically logged events<br>[Feature] SDK configuration verification &amp; improvements<br>[Feature] Support Unity SDK (closed beta)</td>
  </tr>
  <tr>
    <td>8/28/2024<br></td>
    <td>Beta 1.3.6 (Android)</td>
    <td>[Fix] Fix testing issue (add initialization status and invalid value check &amp; float precision issue)</td>
  </tr>
  <tr>
    <td>8/15/2024</td>
    <td>Beta 1.3.6 (iOS)</td>
    <td>[Fix] Fixed crashes caused by null values &amp; WKWebView allocation.</td>
  </tr>
  <tr>
    <td>7/31/2024</td>
    <td>Beta 1.3.5<br>(iOS)</td>
    <td>[Feature] Add initialization completion handler.<br>[Fix] Fixed SKAN conversion value matching strategy.</td>
  </tr>
  <tr>
    <td>7/26/2024</td>
    <td>Beta 1.3.5 (Android)</td>
    <td>[Fix] Fix WebView ANR and add initialization callback<br></td>
  </tr>
  <tr>
    <td>7/2/2024</td>
    <td>Beta 1.3.4 (iOS)</td>
    <td>[Fix] Reenabling SDK tracking would reset payment tracking config<br>[Fix] Support Apple silicon simulators in XCFramework</td>
  </tr>
  <tr>
    <td rowspan="2">6/28/2024</td>
    <td>Beta 1.3.4 (Android)</td>
    <td>[Fix] Add missing currency<br>[Feature] SDK domain migration<br>[Feature] Add 'order_id' for purchase event &amp; add 'type' for auto track event<br>[Feature] Add error message for config &amp; batch request</td>
  </tr>
  <tr>
    <td>Beta 1.3.3 (iOS)</td>
    <td>[Feature] Support SKAN 4.0<br>[Feature] SDK domain migration<br>[Feature] Add 'order_id' for purchase event &amp; add 'type' for auto track event<br>[Feature] Add error message for config &amp; batch request</td>
  </tr>
  <tr>
    <td>5/15/2024</td>
    <td>Beta 1.3.3 (Android)</td>
    <td>[Fixed] Android: Use 'price_amount_micros' instead of 'price' in <code>autoIapTrack()</code></td>
  </tr>
  <tr>
    <td>5/14/2024</td>
    <td>Beta 1.3.2</td>
    <td>[Fixed] Value parsing for amounts containing commas in <code>autoIapTrack()</code></td>
  </tr>
  <tr>
    <td>4/24/2024</td>
    <td>Beta 1.3.1</td>
    <td>[Feature] Multiple <code>tiktok_app_id</code> support (multiple ad accounts)<br>[Feature] iOS Privacy Manifest declaration added for Required Reason API</td>
  </tr>
  <tr>
    <td>4/1/2024</td>
    <td>Beta 1.3.0</td>
    <td>[Feature] AEO/VBO optimization support<br>[Feature] New standard event builder class <code>trackTTevent()</code>, old class <code>trackEvent()</code> will be deprecated at a later date<br>[Feature] Advanced Matching (email/phone) attribution<br>[Feature] Android automatic IAP tracking <code>autoIapTrack()</code></td>
  </tr>
</tbody></table>


