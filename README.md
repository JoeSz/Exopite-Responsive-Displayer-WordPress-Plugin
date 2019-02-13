# Exopite Responsive Displayer
## WordPress Plugin

- Author: Joe Szalai
- Version: 20190213
- Plugin URL: https://joe.szalai.org/exopite/exopite-responsive-displayer/
- Demo URL: https://joe.szalai.org/exopite/exopite-responsive-displayer/
- Theme GitHub URL: https://github.com/JoeSz/Exopite-Responsive-Displayer-WordPress-Plugin
- Author URL: https://joe.szalai.org/
- License: GNU General Public License v3 or later
- License URI: http://www.gnu.org/licenses/gpl-3.0.html

Conditional display for different devices to control which content is beenig displayed via shortcodes, class names or hooks, depending on the visitor's device.

> **Note:** <br>
> This theme is a work in progress, active develpoment. I tested many times, but may containts some error. Please send me an email if you find any issue (and maybe the solution as well ;) )
> I will fix any issues as soon as I can. But because I'm working on this one alone, may take some time.
> [READ DISCLAMER](https://joe.szalai.org/disclaimer/)
>
> This plugin designed to detect Android, iPhone, iPad, Blackberry and Windows Phones and Tablet as well as Linux, Windows and Mac desktops. It is not designed to detect ALL mobile or tablet devices, may not work with older phones.
>
> `$_SERVER['HTTP_USER_AGENT']` can be faked, in this case the detection will be inaccurate.

## Description

You no longer need to rely on display:none; or other techniques to hide content for devices (mobile, desktops, etc...). Instead you can replace, display or remove content with alternatives. Hiding techniques in your posts unknowingly displayed on a visitors device and that can be considered as Non-user initiated download. In other words that visitor had no idea that he or she has to download unnecessarily content using their bandwidth.

Not to mention, hidden content is far from ideal for your Google SEO. Depending on your hidden content (like duplicate content, keyword stuffed content, hidden links, etc...) you can be penalized by Google.

Ultimately your side will load faster on the visitors device. This is especially important in mobile devices.

This plugin give to the ability to add/display content on a specific device using a `[device-name][/device-name]` shortcode or the hook and remove any content with a `remove-[device-name]` class.

<p align="center">
    <a href="https://joe.szalai.org/exopite/exopite-responsive-displayer/" rel="Plugin URL and Live Demo"><img src="https://joe.szalai.org/wp-content/uploads/2017/07/plugin_live_demo.png" alt="Plugin URL and Live Demo"></a>
</p>

## Available devices

tablet, mobile, android, android-mobile, android-tablet, ios, iphone, ipad, linux-desktop, mac, desktop, windows-desktop, windows-mobile, windows-tablet, blackberry, bot

Bots: "google", "duckduckbot", "msnbot", "bingbot", "ask", "facebook", "yahoo", "addthis"

## Features

#### Shortcodes
Use as `[device-name][/device-name]`, where device-name is a device from the list above.

Eg.: for a mobile: [mobile]This text would be olny displayed on mobile devices.[/mobile]

#### Classes
Use as `<element class="remove-[device-name]">...</element>`, where device-name is a device from the list above.

Eg.: for an Android tablet the following classes will be removed: .remove-tablet, .remove-android, .remove-android-tablet

#### Body classes
The plugin can add `device-name` to the body classes, for design purposes. It is disabled by default.

#### Hooks
Can be used to perform any action on the seleted device. Eg. 302 redirect the user to the equivalent mobile page, display or hide content, etc...

Add an action:
```php
add_action( 'exopite-responsive-displayer-is-[device-name]', 'your-function' );
```
Display an action:
```php
Exopite_Responsive_Displayer::is_[device-name]();
```

#### Functions for device detections
```php
Exopite_Device_Detector::is_[device-name]()
```
true/false

```php
if ( Exopite_Device_Detector::is_mobile() ) { // code for mobile... }
```

#### Turn functions on and off
Use can activate or deactive function via hooks. There is no admin option page in the moment and it is also not a priority for me.

| Filters                                       | Desciption              | Defaults  | Values     |
| --------------------------------------------- | ------------------------|-----------|------------|
| `exopite-responsive-displayer-add-body-classes` |  To add body classes    | false     | true/false |
| `exopite-responsive-displayer-devices`          |  To change devices list | see above | array      |
| `exopite-responsive-displayer-add-shortcodes`   |  To register shortcodes | true      | true/false |
| `exopite-responsive-displayer-remove-classes`   |  To remove classes      | true      | true/false |

## Technical details

For remove the classes the plugin uses Output Buffering and ![PHP Simple HTML DOM Parser](http://simplehtmldom.sourceforge.net/) to parse the entire page after it is rendered by WordPress to the output buffer.
To detect the devices, plugin uses information form `$_SERVER['HTTP_USER_AGENT']`.

## Installation

1. [x] Upload `exopite-multifilter` to the `/wp-content/plugins/exopite-responsive-displayer/` directory

OR

1. [ ] ~~Install plugin from WordPress repository (not yet)~~

2. [x] Activate the plugin through the 'Plugins' menu in WordPress

## Requirements

#### Server

* WordPress 4.0+ (May work with earlier versions too)
* PHP 5.3+ (Required)
* jQuery 1.9.1+

##### Browsers

* Modern Browsers such as Firefox, Chrome, Safari, Opera, IE 10+
* Andorid 4.0, iOS 7, WP 7

* Tested on Firefox, Chrome, Edge, IE 11
* Tested on Android phone 5.0+, Android tablet 5.1+, Windows 10

## Changelog

##### 20190213
* Update PHP Simple HTML DOM Parser to 1.8.1 ensure PHP 7.3 compatibility

##### 20171221
* Fix Mac detection

##### 20170725
* Check if PHP Simple HTML DOM Parser class already included.

##### 20170724
* Run shortcodes in shortcode content.

##### 20170723
* Initial release.

[![forthebadge](http://forthebadge.com/images/badges/built-by-developers.svg)](http://forthebadge.com) [![forthebadge](http://forthebadge.com/images/badges/for-you.svg)](http://forthebadge.com)
