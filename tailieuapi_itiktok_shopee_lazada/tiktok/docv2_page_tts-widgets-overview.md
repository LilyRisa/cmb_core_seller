# Overview

> Source: https://partner.tiktokshop.com/docv2/page/tts-widgets-overview
> Section: Developer Guide
> Scraped: 2026-05-21T00:26:23.841Z

---

# What is "widget"?

Widget is a component that consists of a TikTok Shop-based API and UI interface, The Widgets support various seller use cases on TikTok Shop, such as setting warehouse/shipping templates, optimizing products, fulfilling the order, checking the performance data...  
What's the difference between API and widget?  
In summary, ***widgets*** are the visual components that enhance the user interface and functionality of a specific feature, while APIs (Application Programming Interfaces) are sets of rules and protocols that allow different software applications to communicate with each other, which do not include UI components.

## Technical Differences

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/65d2997cf794467e898e0a5658fd7436~tplv-k9wyc2ijk0-image.image)

|  | TikTok Shop-API | TikTok Shop Widget |
| --- | --- | --- |
| 
Technical Architecture / Integration

 | 

API is a pure REST backend-to-backend integration.

 | 

The widget is an SDK that is integrated in your frontend. which take care of all the (backend) calls to the TikTok Shop.

 |
| 

Development

 | 

Requires backend development.

 | 

Requires front-end development (excluding token integration).

 |
| 

UI flexibility

 | 

You have full control over the frontend in your app. which is flexible and can be customized.

 | 

The Widget provides out-of-the-box interface for your users. which is easy to use and not customizable.

 |

# What are the benefits of integrating widgets?

-   Widget is easy to integrate, which in most cases only costs frontend resources to integrate
-   Widgets can enhance the functionality and interactivity of your APP, providing additional value to users.
-   Widgets can provide consistency with data and interface from TikTok Shop API

# How to integrate widgets?

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/ee4bd621c9fc44eb8e9209eb35e9963b~tplv-k9wyc2ijk0-image.image)

# Before you Begin

## Step 1 Browse widget options

You can browse widget options on the ++Development kits> Widget ++tab and Partner Center doc center, click learn more to check more details about this widget.  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/9aa5d767a1d24bca93609b20f3c673ba~tplv-k9wyc2ijk0-image.image)  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/95321924572a40e890542d2bfd5638f9~tplv-k9wyc2ijk0-image.image)

## Step 2 Finish widget setting

Developers can check a list of all apps that have been created and whether or not the app has enabled widgets. By clicking the configure button, the partner will jump the developer to the app details page for them to configure.  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/bdcb000f0508483ab81cd925b3f84872~tplv-k9wyc2ijk0-image.image)  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/f1f4c7b42b25489dabd2118aee2d3877~tplv-k9wyc2ijk0-image.image)  
Developers can also start widget pre-setting configuration on the APP details page when creating a new app.  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/2e4b5b8bdba1415f80ddc62ad95deedd~tplv-k9wyc2ijk0-image.image)  
Developers need to provide domain URLs for the widget. Please note:

1.  Please provide a root domain, otherwise you may encounter the error message.
    1.  URL Example: [https://demo1.tiktokshop.com](https://demo1.tiktokshop.com)
2.  The maximum number of URLs you can add is 10.

![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/6aba7fe704ff4027905ece40bdca7423~tplv-k9wyc2ijk0-image.image)

## Step 3 Start integration

After the presetting of the widget, you can now follow the widget integration guide to integrate with the widget and test your widget before launch.

## Authentication Token

We provide you with a short-term token and you can refer to [Get Widget Token.](get-widget-token)

# Widget integration Guide

| NO. | Widget name | Widget intro | Widget demo | Market | 📒 Integration Guide |
| --- | --- | --- | --- | --- | --- |
| 
1

 | 

Warehouse widget

 | 

Before listing products, the seller is required to set up warehouse info.  
  
\* Seller may have no chance to know this information or set shipping template without logging to Seller center.  
\* High failure rate of API listing products is mainly caused by not setting shipping templates  
  
Warehouse Settings widgets can enable the Seller to enter the delivery warehouse and return warehouse information, including warehouse contact person, warehouse address, and warehouse sales range.

 | 

  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/d95c979bca564e2aa3a6695787c00102~tplv-k9wyc2ijk0-image.image)  

 | 

US&UK

 | 

[https://partner.tiktokshop.com/docv2/page/66599ca675ead002e4e0c3fb](https://partner.tiktokshop.com/docv2/page/66599ca675ead002e4e0c3fb)

 |
| 

2

 | 

Shipping template widget

 | 

Before listing products, the TTS seller is required to set shipping templates if the seller chooses to ship by themselves.  
  
\* Seller may have no chance to know this information or set shipping template without logging to Seller center.  
\* High failure rate of API listing products is mainly caused by not setting shipping templates  
  
For 3PL sellers, this widget can provide shipping template(Prerequisite for listing products) for Seller

 | 

  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/ef6f928a3ada4fd6bafd0a8eb150c13c~tplv-k9wyc2ijk0-image.image)  

 | 

US&UK

 | 

[https://partner.tiktokshop.com/docv2/page/66599c6d36f34802de23344e](https://partner.tiktokshop.com/docv2/page/66599c6d36f34802de23344e)

 |
| 

3

 | 

🔥Product optimizer widget

 | 

After syncing the product, the low product info will continue to affect traffic and the seller may have no idea how to improve it.  
This widget leverages AI-powered recommendations to produce high-quality product information and improve the seller's overall product performance.  
  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/bf0e583489ea45e69af30ab2713f84ad~tplv-k9wyc2ijk0-image.image)  

 | 

  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/0edbeaf819ad4850a3efb1ccf35bbb86~tplv-k9wyc2ijk0-image.image)  

 | 

US&UK

 | 

[https://partner.tiktokshop.com/docv2/page/66599c4236f34802de23338d](https://partner.tiktokshop.com/docv2/page/66599c4236f34802de23338d)

 |
| 

4

 | 

🔥Orders by TikTok Shipping (4PL)widget

 | 

For those sellers who use 4PL delivery, they need to carry out delivery actions in the TTS seller center after they check orders in the 3P app, which is time-consuming  
This widget enables sellers to process 4PL orders individually/in batches in the 3P APP.  
(select orders in batches - > > create labels - > > print labels - > > process shipments)

 | 

  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/93e8705592a346eca864270586d12f11~tplv-k9wyc2ijk0-image.image)  

 | 

US

 | 

[https://partner.tiktokshop.com/docv2/page/665999a9e5764002e59be4e2](https://partner.tiktokshop.com/docv2/page/665999a9e5764002e59be4e2)  

 |

# Get Help

You can raise tickets to get integration technical support through the [Partner Center- Help Center.](https://partner.us.tiktokshop.com/ticket/create/diversion)  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/e425001a041d451c9725eea78b7bc102~tplv-k9wyc2ijk0-image.image)  
🎁 **Feedback** is a gift! Please let us know your feedback/questions about the widget by filling in the [Feedback Table.](https://bytedance.larkoffice.com/share/base/form/shrcnvE6AEviXJggHqAfvzvclUg)  
🔥 Hop into the TTS Widget Integration official group—Just scan the QR Code below to join!  
![Image](https://p16-arcosite-sg.ibyteimg.com/tos-alisg-i-k9wyc2ijk0-sg/3473d53b054b45ff87312b96ed5ec495~tplv-k9wyc2ijk0-image.image)
