# gallery3-openid-azureadb2c-module
Gallery3 OpenID Connect Module integrated with Azure AD B2C. The module is an extend the user plugin to also allow OpenID, Facebook, and Microsoft Account verification using Azure AD B2C, and the module is forked from Tomek Kott ( http://kott.fm/tomek/ )'s OpenID+ module version 2.

## How to add the module to Gallery 3 manually

Suppose gallery3 root dir is /gallery3, here is how you add the module to Gallery 3
```
git clone https://github.com/yokawasa/gallery3-openid-azureadb2c-module.git && \
mv gallery3-openid-azureadb2c-module/openid /gallery3/modules/ && rm -rf gallery3-openid-azureadb2c-module
```

## Dockerfile


## Configure Azure AD B2C

## How to enable the module in Gallery 3

### 1. Login Gallery3 as Admin and navigate to Modules page
![](https://github.com/yokawasa/gallery3-openid-azureadb2c-module/raw/master/img/1_menu_modules.png

### 2. Click checkbox for OpenID Azure AD B2C and save
![](https://github.com/yokawasa/gallery3-openid-azureadb2c-module/raw/master/img/2_check_openid.png

### 3. OpenID+ Settings
![](https://github.com/yokawasa/gallery3-openid-azureadb2c-module/raw/master/img/3_openid_menu.png
![](https://github.com/yokawasa/gallery3-openid-azureadb2c-module/raw/master/img/3_openid_settings.png

- Azure AD B2C Tenant Name
- Azure AD B2C Application ID
- Azure AD B2C Application Secret
- Microsoft Application Sign-In / Sign-out Policy
- Facebook Application Sign-In / Sign-out Policy


### 5. Login page

![](https://github.com/yokawasa/gallery3-openid-azureadb2c-module/raw/master/img/4_login_page.png
