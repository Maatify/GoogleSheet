# GoogleSheet

maatify.dev MySql Database Google Sheet handler, known by our team

## Installation

    composer require maatify/google-sheet


Usage Create Class 

    <?php

    namespace App\Assist\GoogleSheet;

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


to read sheet as array

    GSheet::obj()->ReadAll();

to insert array to row 

    GSheet::obj()->WriteRow();
