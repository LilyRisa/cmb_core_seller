---
title: "Unlock conversations"
doc_id: 1839594049581249
path: "Business Messaging API / Guides / Unlock conversations"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Unlock conversations

This guide offers a comprehensive overview of the steps required to generate and effectively use TikTok.me Short Links to enhance customer engagement. Additionally, it introduces TikTok Direct Messaging Ads, a valuable resource for initiating new conversations.

## Introduction
The TikTok.me Short Link feature presents a robust opportunity for businesses to connect with customers on TikTok. It streamlines interactions by enabling users to initiate conversations directly with Business Accounts. By implementing these links strategically across diverse customer touchpoints and incorporating TikTok Direct Messaging Ads, businesses can enhance their communication strategies and boost user engagement on the platform.

## TikTok.me Short Links
The TikTok.me Short Link feature simplifies customer interaction with businesses by enabling direct messaging from various external points outside the TikTok ecosystem. The key benefits for businesses include:  
- **Effortless conversation starters**: Customers can start conversations without navigating through the app, as the TikTok.me Short Link streamlines the experience.  
- **Streamlined customer engagement**: The TikTok.me Short Link directs customers from all marketing channels to initiate conversations on TikTok, and to continue to interact with businesses within the dynamic TikTok ecosystem.
- **Automated and personalized messaging**: By leveraging messaging partners, businesses can create tailored message flows and use referral or pre-filled message parameters in TikTok.me Short Links to customize the user experience.
  - **Note**: The referral or pre-filled message parameters will only function on the mobile TikTok app and  does not work on TikTok Web.

### How to generate a TikTok.me Short Link
To create a TikTok.me Short Link for your Business Account, follow these steps:

1. Build the base link.

```xcodeblock
(code curl http)
tiktok.me/{uniqueusername}
(/code)
```

Replace `{uniqueusername}` with the username of your Business Account.
  
2. (Optional) Add additional parameters.

You can append the following parameters to enhance the link's functionality:

  - `ref`: A referral parameter to provide context about the conversation. You can incorporate the referral parameter in your TikTok.me Short Link to monitor various links posted on different channels. This parameter is a URL-encoded string with a maximum length of 2,083 characters. 
  > <span style="color:DodgerBlue">**Note**</span><br>
  > The `ref` parameter only supports alphanumeric characters and the special characters `-`, `_`, and `=`.

Example:
	
```xcodeblock
(code curl http)
tiktok.me/{uniqueusername}?ref=example
(/code)
```

  - `message`: A pre-filled message that users will see in the chat input box.

Example: 
	
```xcodeblock
(code curl http)
tiktok.me/{uniqueusername}?message=Hi,%20I%20want%20to%20join%20the%20membership%20program.
(/code)
```

    
### Where to implement TikTok.me Short Links
You can use TikTok.me Short Links in various scenarios, including but not limited to:
- **Website**: Integrate the TikTok.me Short Links within the "Contact Us" or "About Us" sections of your business's website. On e-commerce platforms, consider incorporating a button linked to a TikTok.me Short Link to encourage product consultations and after-sales conversations on TikTok. Additionally, businesses can feature TikTok.me Short Links on their link-in-bio pages.  
- **Printed collateral**: Leverage third-party QR code services to convert your TikTok.me Short Links into a QR code that can be added to printed materials like posters, menus, business cards, promotions, and table tent cards.  
- **Message call-to-action button**: To ensure customers are informed about your presence on TikTok, businesses can add TikTok.me Short Links to the call-to-action buttons of messages on other messaging channels, empowering customers to select their preferred channel to engage continuously with your business.
  
## TikTok Direct Messaging Ads
Businesses can also use TikTok Direct Messaging Ads to encourage customers to initiate conversations. This approach invites users to directly message businesses after seeing an ad.

For more information on these ads, see the following resources:
- [About TikTok Messaging Ads](https://ads.tiktok.com/help/article/about-tiktok-messaging-ads)
- [Create a Lead Generation Ad with optimization location as TikTok direct messages](https://business-api.tiktok.com/portal/docs?id=1774482992153601)
