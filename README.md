Box PHP SDK
===========

安装说明
------

1. 本地建立 composer.json 文件；

2. 加入以下内容:

    ```javascript
    {
        "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/HoopCHINA/php-box-sdk"
            }
        ],
        "require": {
            "HoopCHINA/php-box-sdk": "dev-master"
        }
    }
    ```
    _[NOTE: 也可以指定依赖 **0.1.x** 版本。]_

3. 使用 php composer.phar install 安装依赖包。 

[TODO...]

License
-------

>>> PHP-Box-SDK is Copyright 2013 Hupu Co., Ltd.

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at
    
       http://www.apache.org/licenses/LICENSE-2.0
    
    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.

Credit
------

* Thanks to [Hupu.com](http://www.hupu.com) for sponsor resources to make
  this library happen.
