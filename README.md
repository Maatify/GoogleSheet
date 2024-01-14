[![Current version](https://img.shields.io/packagist/v/maatify/google-sheet)][pkg]
[![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/maatify/google-sheet)][pkg]
[![Monthly Downloads](https://img.shields.io/packagist/dm/maatify/google-sheet)][pkg-stats]
[![Total Downloads](https://img.shields.io/packagist/dt/maatify/google-sheet)][pkg-stats]
[![Stars](https://img.shields.io/packagist/stars/maatify/google-sheet)](https://github.com/maatify/google-sheet/stargazers)

[pkg]: <https://packagist.org/packages/maatify/google-sheet>
[pkg-stats]: <https://packagist.org/packages/maatify/google-sheet/stats>

# GoogleSheet

maatify.dev Google Sheet handler, read and write to google sheet, known by our team

# Installation

```shell
composer require maatify/google-sheet
```

Usage 
1- Create Class 
```PHP
    <?php

    namespace GoogleSheet;

    use Maatify\GoogleSheet\SheetHandler;

    class GSheet extends SheetHandler
    {
        protected string $credentials_file = __DIR__ . 'Json credintals location',
            $spread_sheet_Id = 'sheet id 1uxGes9CR1mvdshgsdfhg',
            $spread_sheet_range = 'sheet!A:Z';

        private static self $instance;

        public static function obj(): self
        {
            if(empty(self::$instance))
            {
                self::$instance = new self();
            }
            return self::$instance;
        }
    }
```

* to read sheet as array
```PHP
    GoogleSheet\GSheet::obj()->ReadAll();
```
* to insert array to row 
```PHP
    GoogleSheet\GSheet::obj()->WriteRow();
```