---
title: "Settings"
doc_id: 1835087607780546
path: "Marketing API / Ad Measurement / Guides / Events API Gateway / Settings"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Settings

## Primary domain setup
**Required permissions(s) :** Host admin
### **With AWS**
Only for the host owner, agency/partner admin.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c7260e671419e1b946eafc03a1ae7ed5" alt="Setup Domain" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/993769ac1f97b6d5112a9730d40bc23c" alt="AWS Certificate" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/53d16db5316c9d7abe899b1cdbdea83c" alt="Create Domain" width="60%" style="color:#808080" />

- Input your own domain with a not-used prefix as the host for the eapig host, e.g.
  - Your domain：[advertiser.com](http://advertiser.com/)
  - Prefix: eapig
  - Then [eapig.advertiser.com](http://eapig.advertiser.com/) will be the host domain for this Events API Gateway instance.
- After you confirm the host domain, we provide 2 DNS records that need to be configured in your DNS provider. **This should be completed in 3 days.**

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8ec35d851e817fa93c9db7a3e2b61ae2" alt="Configure DNS" width="60%" style="color:#808080" />

- If you find that your last step input the wrong domain, you can click the 'x' to close the window and click the **Reset domain** button. After reset is completed, you can setup the host domain from the beginning again.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c07020e619abc28da5dbe723c602423c" alt="Reset Domain" width="60%" style="color:#808080" />

- DNS records status will be shown in the tab, and after all the process is completed, the button at right bottom corner will change from **waiting** to **Done** several minutes after all the DNS records are completed.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5d9eb7c0321645f5aedf316bba2ba5fd" alt="Configure DNS to Connect Domain" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/58d0c3a3e360e0892ba8e3afbb5faa16" alt="DNS Configuration Completion" width="60%" style="color:#808080" />

- Then you can see the host domain is setup completed. Also, you can see the domain quota in this Events API Gateway.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b174c87eb394cad6b5a77fdbcc0ea8de" alt="domain quota" width="60%" style="color:#808080" />

### With Cloudflare
Only for the host owner, agency/partner admin.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c7260e671419e1b946eafc03a1ae7ed5" alt="Setup Domain" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3bfe59464c5417390440e16770c7a277" alt="Select Cloudflare" width="60%" style="color:#808080" />

**Generate a token in Cloudflare**<br>
**Scenario 1:  Use your existing domain(website) in Cloudflare**

Use the menu on the left side of the screen to navigate to the **Websites** page and select the website (domain) you want to use for Cloudflare setup.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a08cb6fb256314bccadb6174438cc63f" alt="Website CloudFlare Setup" width="60%" style="color:#808080" />

**Scenario 2:  Register a new domain with Cloudflare**

To register a new domain with Cloudflare, use the menu on the left side of the screen to navigate to the **Websites** page. Then click the blue **Add a site** link, then follow the instructions to create a new domain.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/a39c49cefb669a26a17a513bffbbb2a6" alt="Websites" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1ce7b527208492d44305934805939970" alt="Add Your Website or Application" width="60%" style="color:#808080" />

After domain registration is completed, navigate to the **Websites** page and select the website (domain) you just created.

**Scenario 3:  Use a domain from another provider with Cloudflare**

> <span style="color:DodgerBlue">**Note**</span><br>
> This scenario means if you have a domain registered with another provider (like Godaddy or AWS route 53) and want to use it with Cloudflare.

Follow Cloudflare's [instructions](https://developers.cloudflare.com/fundamentals/setup/manage-domains/add-site/) to add a site (domain) to Cloudflare. **You only need to complete steps 1 and 2.**

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/ac4de57d0c79f830b3c72032fbcc00e0" alt="Add Site to CloudFlare" width="60%" style="color:#808080" />

After the domain add is completed, navigate to the **Websites** page and select the website (domain) you just created.

- **Enable Cloudflare for SaaS**

Once a website (domain) is selected, go to `SSL/TLS > Custom Hostnames`, click [enable](https://developers.cloudflare.com/cloudflare-for-platforms/cloudflare-for-saas/start/enable/) Cloudflare for SaaS. 

**Please note that this option is shown only once, and must be enabled at this time.** If the Cloudflare for SaaS option is already enabled, you will see the options like **Add Custom Hostname** and **Add Fallback Origin**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/3490e822cfb762973cd39b1c0f9a8b95" alt="Enable Cloudflare for SaaS" width="60%" style="color:#808080" />

**Make sure there is no Fallback origin present.** Cloudflare only allows one fallback origin per domain. If Fallback Origin and Fallback Origin Status are active, you will need to delete these in order to set up Cloudflare for Events API Gateway.

- **Enable additional settings**<br>
Make sure that the following settings are enabled:
  - Automatic HTTPS Rewrites. [Learn more.](https://developers.cloudflare.com/ssl/edge-certificates/additional-options/automatic-https-rewrites/#enable-automatic-https-rewrites)
  - Always Use HTTPS. [Learn more.](https://developers.cloudflare.com/ssl/edge-certificates/additional-options/always-use-https/#encrypt-all-visitor-traffic)
  - Brotli. [Learn more.](https://developers.cloudflare.com/speed/optimization/content/brotli/enable/)

- **Generate a token**<br>
Go to the **Overview** page, and on the right side under the API tab, click **Get your API token**, then click **Create Token**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/59598ce2d4d284b552685b8f86bd59f3" alt="Overview Page" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e2dfb0ebe954a17b0f7a6d5e79a652a7" alt="Create Tokens" width="60%" style="color:#808080" />

Then navigate to the bottom of the custom token box, click **Get started**.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5871e0125a404995d6a4ab8688903a53" alt="Get Started" width="60%" style="color:#808080" />

In **Create Custom Token** page:
  - Input a token name that will be easy for you to remember
  - In the permission section, we need 3 Edit permissions
    - SSL and Certificates
    - DNS
    - Zone Settings

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5914d401f1c402f722bd1137f17ed852" alt="Create Custom Token" width="60%" style="color:#808080" />
	
Then continue and create the token.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/fe01fd4727769dd0b418b6009318b6e3" alt="User API Tokens" width="60%" style="color:#808080" />
	
- **Validate the token**<br>
Go back to Hub page and input the token. Select your domain and input a prefix. This will be the host domain for this Events API Gateway instance.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e3fd5dd9fadc9036bcdbf66e13922652" alt="CloudFlare Setup" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/cc0c8df388f28b419a896c209b21c26b" alt="Select Domain" width="60%" style="color:#808080" />

After you confirm your domain and prefix, we will auto-config the domain for this Events API Gateway. Maybe it needs 30 minutes.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/bcff469f7b456cfe4d032ef9eed3bcc8" alt="Create DNS Records" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/34e540233b908cc74a4c812f1dec7397" alt="Complete DNS Records" width="60%" style="color:#808080" />

Then you can see the host domain is setup completed. 

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/42eac58e2630f1c2a332761c7ac2565c" alt="Completion Status Page" width="60%" style="color:#808080" />

## Auto scale limits

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2c36f8123075a5d3bbee3087f9d4c244" alt="Update Max Nodes" width="60%" style="color:#808080" />

Just update the max nodes per available zone and confirm; auto scaling will be limited to the max number.

## SMTP settings

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/8f6cb18f168b9400bf56f1c04913ca77" alt="SMTP Settings" width="60%" style="color:#808080" /><br>
- Update the required info for smtp which is provided by your mail provider.
- SMTP is used by Events API Gateway to send emails based on your mail server; applied to 2FA, password reset, etc.

## Add account security: 2-step verification

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/24f6a1bda50ac538ad26afb210c9cb74" alt="2-step Verification" width="60%" style="color:#808080" /><br>
When the **Enable 2-step verification** toggle is ON, users will need to enter the email verification code when logging in. <br>
SMTP will be the prerequisite for enabling 2FA.
