---
title: "User management"
doc_id: 1835087602015234
path: "Marketing API / Ad Measurement / Guides / Events API Gateway / User management"
source: "https://business-api.tiktok.com/portal/docs (Marketing API v1.3)"
---

# User management

**Required permissions(s)** : host admin<br>
## Invite new user to host admin
- Enter a new email.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/5d1cb23ce5d7112efea4a26bacf1013d" alt="Invite New Host Admin" width="60%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/65fdc1464758d9cb4e4bdf75c18e0279" alt="Invite Users to Host List" width="40%" style="color:#808080" />

## Invite existing user to host
- Enter an existing email, then:
  - If the entered email represents a 'Pending' tenant user, the permission of this user will be upgraded to the host admin (meanwhile removing tenant-level permissions) and a new invite link will be generated.
  - If the entered email represents a 'Pending' host admin, make no changes and re-generate a new invite link.
  - If the entered email represents an 'Active' tenant user, upgrade to host admin (without generating new invite link).
  - If the entered email represents an 'Active' host admin, make no changes (without generating new invite link).
  
## Activate users via invite link
Enter the password and click **Create account**, the user will be activated.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/08a478f564045b889fdd48ba9151cb12" alt="Create Account" width="40%" style="color:#808080" />

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/b9925c8ea153a05840a81bab7b0c8053" alt="Create Successfully" width="40%" style="color:#808080" />

## Edit permissions
**Required permissions(s)** : Host Admin
- Edit permissions in host **Users** section.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/6a3bc508fcfe9b3936d96aa15e1ef44d" alt="User Name" width="60%" style="color:#808080" /><br>
- In the host user management section, you can edit the **Host Admin** role of any user:

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/0ddf6d66571df0e9f05a62b291bfe9f6" alt="Edit Host Admin" width="40%" style="color:#808080" />

If the user already has any tenant roles, assigning the host role will remove all tenant roles and reset the user role to host admin.
<br><br>Also you can 'revoke' host admin role from the user, notice you can **NOT** revoke tenant role under 'Users', please go to 'Users' tab to revoke tenant role one by one.

## Reset passwords
**Required permissions(s) :** Host/Tenant Admin
- Reset password in host/tenant user management. Click **Change password**, then a reset password link will be generated.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/af0d97c7e3316abd61b40eec98c30339" alt="User Name" width="60%" style="color:#808080" />

- Set a new password via the generated reset password link. Go to the reset password link and enter a new password.

<image src="https://sf16-adcdn-sg.ibytedtos.com/obj/open-api-file-public-i18n/670201d265d42d254c266909af896dd6" alt="Set New Password" width="40%" style="color:#808080" />
