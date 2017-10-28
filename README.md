# gallery3-openid-azureadb2c-module
[Gallery 3](http://galleryproject.org/) is an open source photo sharing web application, based on PHP and MySQL. This is a Gallery3 module that adds OpenID, Facebook, and Microsoft Account functionality to the login page using [Azure AD B2C](https://azure.microsoft.com/en-us/services/active-directory-b2c/). It is forked from Tomek Kott ( http://kott.fm/tomek/ )'s OpenID+ module version 2.

## How to add the module to Gallery 3 manually

Suppose gallery3 root dir is /gallery3, here is how you add the module to Gallery 3
```
git clone https://github.com/yokawasa/gallery3-openid-azureadb2c-module.git && \
mv gallery3-openid-azureadb2c-module/openid /gallery3/modules/ && rm -rf gallery3-openid-azureadb2c-module
```

## Dockerfile and relevant files
https://github.com/rioriost/kd_gallery3/

## Configure Azure AD B2C

- [Create Azure AD B2C tenat](https://docs.microsoft.com/en-us/azure/active-directory-b2c/active-directory-b2c-get-started)
- [How to Setup Azure AD B2C for Microsoft App](https://docs.microsoft.com/ja-jp/azure/active-directory-b2c/active-directory-b2c-setup-msa-app)
- [How to Setup Azure AD B2C for Facebook App](https://docs.microsoft.com/ja-jp/azure/active-directory-b2c/active-directory-b2c-setup-fb-app)

## How to enable the module in Gallery 3

### 1. Login Gallery3 as Admin and navigate to Modules page
![](https://github.com/yokawasa/gallery3-openid-azureadb2c-module/raw/master/img/1_menu_modules.png)

### 2. Click checkbox for OpenID Azure AD B2C and save
![](https://github.com/yokawasa/gallery3-openid-azureadb2c-module/raw/master/img/2_check_openid.png)

### 3. OpenID+ Settings
![](https://github.com/yokawasa/gallery3-openid-azureadb2c-module/raw/master/img/3_openid_menu.png)
![](https://github.com/yokawasa/gallery3-openid-azureadb2c-module/raw/master/img/3_openid_settings.png)

- Azure AD B2C Tenant Name
- Azure AD B2C Application ID
- Azure AD B2C Application Secret
- Microsoft Application Sign-In / Sign-out Policy
- Facebook Application Sign-In / Sign-out Policy


### 5. Login page

![](https://github.com/yokawasa/gallery3-openid-azureadb2c-module/raw/master/img/4_login_page.png)


## LINKS
- http://galleryproject.org
- https://github.com/gallery
- http://codex.galleryproject.org/Gallery3:Modules:openid
- https://azure.microsoft.com/en-us/services/active-directory-b2c/
- https://docs.microsoft.com/en-us/azure/active-directory-b2c/
