# Requesting Access to Sensitive Data

> Source: https://open.shopee.com/developer-guide/718
> Category: 
> Scraped: 2026-05-20T20:37:26.767Z

---

Shopee Open Platform safeguards sellers’ business data and users’ personal data considered sensitive (including customer name, phone number, email address, and address).

  

By default, sensitive data is masked. Developers must complete specific security requirements to request access to unmasked sensitive data.

### Eligibility Requirements

To request access to sensitive business data, developers must meet the following conditions:

1.  Submit Penetration Test Report

-   Required for Third-party Partner Platform (ISV) Developers who are serving, or planning to serve, Thailand sellers only.
-   A valid penetration test report must be submitted through the Open Platform Console

3.  Whitelist IP Address(es)

-   Required for all developers
-   The IP addresses of the servers hosting your application must be declared and whitelisted.

⚠️ Note: For Third-party Partner Platform (ISV) Developers, approved sensitive data access is valid for two (2) years from the penetration test report’s issue date.

### How to Submit a Penetration Test Report

Follow the steps below to upload your penetration test report:

-   Step 1: Log in to your Open Platform console using your developer account

Note: Member accounts do not have permission to upload reports.

-   Step 2: Navigate to Personal Center → Account Information (Chinese Mainland ISVs: [Link](https://open.shopee.cn/console/person/account), Other Region ISVs: [Link](https://open.shopee.com/console/person/account))
-   Step 3: Under Security Reports & Certifications Information, click "Add"

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=3nkzhcjEysHs%2BxVIeQP%2BiarSKdEmhwKjczzYD%2FJArvxFyZzMjDJFfVJ8RFX6cNqLFAYlHxz94fUYl8ONlEFGHw%3D%3D&image_type=png)

-   Step 4: Under Security Report & Certification Type, Choose “Penetration Test Report”
-   Step 5: Upload your latest penetration test report
-   Step 6: Click “Save”

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=ukOwqouxK6zyCz8GXhJf1UXdCDjwO%2F%2BPzT61qRe4vZHYPG6QovDt2Yd1bXTHPaxy3FKwqPXsb0870c350msmzQ%3D%3D&image_type=png)

Review Timeline

-   The submission status (Approved / Rejected) will be updated in the Account Information section.
-   Review results are typically available within 10 working days.

  

📌 Please refer to the bottom of this page for best practices and guidelines on penetration test report submissions.

### How to Enable IP Address Whitelisting

To enable IP address whitelisting for your application:

-   Step 1: Log in to your Open Platform console and go to App List
-   Step 2: Select the app that requires sensitive data access
-   Step 3: Click Go Live and fill up the required information

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=%2FqddWrd%2FufgszAu0EqzuJupG12wDspo8uOp0rwhl4yXC3cHHIcNRI0w7zkUxisCvrxrLP%2BbA4iWbP9O81Ow%2FaA%3D%3D&image_type=png)

-   Step 4: Under “IP Address Whitelist”, enter the IP address(es) of the server(s) hosting your application
-   Step 5: Toggle Enable IP Address Whitelist to ON
-   Step 6: Click Submit

![](https://open.shopee.com/opservice/api/v1/image/download/?image_id=eOiVyGkoK1KQYE4yDiBl3WZ2X70hP9izMdRLceS%2BAx%2BYAbodKDHQRgvNjOHvrxGJejEGnIRDiOS9ln81sc3zZA%3D%3D&image_type=png)

⚠️ Important: Once IP Address Whitelisting is enabled, API calls can only be made from applications hosted on the declared IP address(es).

  

### Good Practices for Penetration Test Report Submission

1\. Recommended Testing Providers

To improve report quality and review efficiency, ISVs are encouraged to engage reputable, accredited penetration testers.

-   For all ISVs: Use CREST-accredited penetration testers.
-   For ISVs based in China (CN): We recommend testers accredited by:

-   奇安信: [](https://www.qianxin.com/)[Qianxin](https://www.qianxin.com/)
-   360: [](https://360.net/)[360](https://360.net/)
-   深信服: [](https://www.sangfor.com.cn/)[Sangfor](https://www.sangfor.com.cn/)
-   长亭科技: [](https://www.chaitin.cn/)[Chaitin](https://www.chaitin.cn/)

-   Reports from other penetration testers will still be reviewed and considered for approval on a case-by-case basis.

  

2\. Report Quality Requirements

A complete Penetration Test Report should:

-   Reflect the application’s external exposure
-   Assess vulnerabilities in all relevant systems or applications
-   Include a complete list of findings
-   Confirm that no critical or high-risk issues remain unresolved

  

3\. Recommended Report Issuance Date

-   ISVs are encouraged to submit a penetration test report issued within the last one (1) year.
-   Sensitive data access will be granted for two (2) years from the report issue date.
