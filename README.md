AutoThumb, PHP library to automatically create thumbnails.

## Requirements
AutoThumb requires **PHP >= 5.4** , and one of the following image extensions for PHP: **Imagick** or **GD**.

## Installation
First make sure you have either Imagick or GD installed and enabled on your PHP server.
AutoThumb uses a <a href="https://github.com/imanee/imanee" target="_blank">Imanee</a> library that will try to use GD if Imagick is not found in the system.

You can add AutoThumb to your project using the <a href="https://getcomposer.org/" target="_blank">Composer</a> package manager:
    
    $ composer require ysaroka/autothumb

## Getting Started
Install the demo project using the <a href="https://getcomposer.org/" target="_blank">Composer</a> package manager in the root directory of the web server (in the demo used web server Apache):

    $ composer require ysaroka/autothumb-demo

The repository with the demo project is available here: <a href="https://github.com/ysaroka/autothumb-demo" target="_blank">ysaroka/autothumb-demo</a>.