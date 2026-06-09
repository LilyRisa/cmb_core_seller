---
title: "Tenants"
doc_id: 1835087595419649
path: "Marketing API / Ad Measurement / Guides / Events API Gateway / Tenants"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# Tenants

## Create tenant account
**Required permissions(s)** : Tenant Admin
- Enter the name of the new tenant you want to create.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/c1963d9535be48cda907bd1a630506db" alt="Tenant Creation" width="40%" style="color:#808080" />

- The name will need to be unique. If the name is used by an existing tenant, the creation will fail.
- After tenant account creation, a pop-up will instruct you to invite users to the newly created tenant.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/97b42843bc30f62759f87aca31a70c8f" alt="Invite users under tenant" width="40%" style="color:#808080" />

- For further details on inviting users, please refer to the "<a href="#item-link-Invite new user to Tenant">Invite new user to Tenant</a>" and "<a href="#item-link-Invite existing user to Tenant">Invite existing user to Tenant</a>" sections. If you prefer not to invite any users at this time, you can skip this step by clicking **Invite later**.

## Invite new user to Tenant
**Required permissions(s) :** Tenant Admin
- Go to **Tenant List** > **View details** > **Users** > **Invite new users**, and select **New user** option and enter the email address of the user being invited.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/667a161a0703ff32576760ada08fa42b" alt="Invite New Users" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/1c42497f75d787d07465c436ff98376d" alt="Invite Users Under Tenant" width="40%" style="color:#808080" />

- This step can be **repeated** as long as the user is not activated. You can enter the same email to re-generate a new invite link if you lose the previous one or if you want to re-assign the permissions of the invited user.
- If the entered email has not been added to any other tenants or hosts, click **Invite** and an invite link will be generated.
- After the link is generated, you can see the user with **Status** of '**Pending**' in the **Users** tab.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4be876f830328b64bf1c93696ce8469b" alt="Users Tab" width="60%" style="color:#808080" />

## Invite existing user to Tenant
- Click **Existing user** and select an existing email.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/bf5f108c06b72cde41a5b910b5465c07" alt="Existing User Option" width="40%" style="color:#808080" />

  - If the entered email represents a 'Pending' user in another tenant (say tenant B, with the current tenant as tenant A), an invite link will be generated, and the assigned tenant A permissions will be appended to the existing tenant B permissions.
  - If the entered email represents a 'Pending' host admin, the invite link will be generated, but you will **NOT** see the user added to the tenant's **Users** tab because a host admin does not need a tenant admin role to manage this tenant.
  - If the entered email represents an 'Active' user in another tenant (say tenant B, with the current tenant as tenant A). No invite link will be generated and the tenant A permissions will be appended to the existing permissions directly. You could then see the user added to the 'Users Tab' of current tenant.
  - If the entered email represents an 'Active' host admin, the operation will be blocked because it would result in a downgrade of the user's permissions from host-level to tenant-level.

## User permissions
**Required permissions(s) :** Tenant Admin

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/dd7424e36433dc085ccf3b1e3fdbb8f3" alt="Tenant Admin Permissions" width="60%" style="color:#808080" />

In the tenant user management tab, you can edit tenant-level roles of users that are already added to this tenant:

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/30f7dfa2e52236375325a675aa85e904" alt="Edit Permission" width="40%" style="color:#808080" />

Also you can 'revoke' host admin role from the user, notice you can **NOT** revoke tenant role under host 'Users', please go to tenant **Users** tab to revoke tenant role one by one.

## Event statistics

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/e775e61f8bd924df6111cf794181a2f7" alt="Tenant List" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/4e974c733b606dcd645faca9e23dfd01" alt="Stats" width="60%" style="color:#808080" />

- On the tenant list tab, the total number of events is displayed for each tenant.
- On the tenant overview page, events received and published are categorized by event type. You can select different event source Pixel code via the drop down box.
- Additionally, you have the option to select a time range for the data: 1 day, 7 days, 14 days, or 28 days.

## Settings: optimized domain setup


### Using your own domain
- On the tenant settings page, click **Set up domain**. 

  <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/2397768ba4d44ffa8808289812f34546" alt="optimized domain setup" width="60%" style="color:#808080" />

- Click **Next**.

- Input your domain and the prefix, e.g.
  - Your domain：[advertiser.com](http://advertiser.com/)
  - Prefix: eapig-test
  - Then [eapig-test.advertiser.com](http://eapig-test.advertiser.com/) will be the tenant domain for the current tenant.

  <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/42a5a3d21d2269e26c524a0ab868ba38" alt="Choose Domain" width="60%" style="color:#808080" />
  
- You will need to configure 1-2 DNS records (depending on the host domain configuration method) on your DNS provider platform. **The operation should be completed in 3 days.**
 
  <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/258df571f1d5706cff0d75409d520c2f" alt="Configure DNS to Connect Domain" width="60%" style="color:#808080" />
  
- After we check you configure successfully, several minutes later, the button will be changed from **waiting** to **Done**.
  
  <image src=" https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b43b92e08bceaf0da62018e4cb525fe9" alt="Configure DNS to Connect Domain" width="60%" style="color:#808080" />
  
-  Then you will see the tenant domain is set up completed.
  
  <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/cb4054b027ffd8e85c80cd6878d236a8" alt="Tenant Domain Setup Complete" width="60%" style="color:#808080" />
  
- Any time you want to change the tenant domain, you can click the reset button. Be aware that resetting the tenant domain will stop dual sending for all the Pixels bound to the current tenant.
  
  <image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/09be0368946a9ba969c26105086c8ee1" alt="Reset Domain" width="60%" style="color:#808080" />
  
### Updating Pixel base code for optimized domain
Replacing Pixel base code is **required** once optimized domain is enabled.

Go to **Tenants > Data sources > View base code**, replace existing TikTok Pixel base code or first time installing TikTok Pixel, copying over the Events API Gateway base code to your website header.

The code should go between the &lt;head&gt;&lt;/head&gt; tags on every page of your website.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/bba51c9ae4e28e5a8a8914047513cc3e" alt="View Base Code" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/45c4314babd5d9e5ce9ae9a5006e750d" alt="Install Base Code" width="60%" style="color:#808080" />
