<?php

namespace App\Console\Commands;

use App\Models\Prices;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\TimeoutException;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Facades\Storage;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Chrome\ChromeProcess;
use Symfony\Component\DomCrawler\Crawler;

class AviasalesScan extends Command
{
    //Настройки---------------------------------

    const START = '2020-10-01';
    const END = '2020-12-31';
    const REVERSE = true;
    const WOW_PRICE = 50000;
    const WOW_PRICE_ID = ['AIRPORT_FROM', 'AIRPORT_TO'];
    const DUMP_FILE_NAME = 'aviasales';
    const BROWSER_OPTIONS = [
        [true,  '--window-size=1920,1080'],
        [true,  '--headless'],
        [true,  '--disable-gpu']
    ];
    const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.134 Safari/537.36 OPR/89.0.4447.83 (Edition Yx 05)';

    //-------------------------------------------


    protected $signature = 'aviasales:scan';

    public $browser;
    public $browser_options;
    public $telegram;

    public $links = [
        'IST_UIO' => 'https://www.aviasales.ru/search/AIRPORT_FROM***AIRPORT_TO21?get_params',
        'IST_GYE' => 'https://www.aviasales.ru/search/AIRPORT_FROM***AIRPORT_TO21?get_params',
        'MOW_IST' => 'https://www.aviasales.ru/search/AIRPORT_FROM***AIRPORT_TO?get_params'
    ];

    const TELEGRAM_BOT_API_TOKEN = 'xxx';
    const TELEGRAM_CHAT_IDS = [
        'xxx',
        'xxx'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->telegram = new Client([
            'base_uri' => 'https://api.telegram.org/bot' . self::TELEGRAM_BOT_API_TOKEN . '/',
        ]);

        $this->browser_options = [];
        foreach(self::BROWSER_OPTIONS as $opt){
            if($opt[0]){
                $this->browser_options[] = $opt[1];
            }
        }

        Browser::$storeSourceAt = __DIR__ . '/source';
        Browser::$storeScreenshotsAt = __DIR__ . '/screenshot';
        $this->browser = new Browser($this->driver());
    }

    protected function driver()
    {
        $options = (new ChromeOptions)
            ->addArguments($this->browser_options)
            ->setExperimentalOption('mobileEmulation', ['userAgent' => self::USER_AGENT]);

        return RemoteWebDriver::create(
            'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                'chromeOptions', $options
            )
        );
    }

    public function send2telegram($type, $price, $link)
    {
        $message = <<<MESSAGE
        Очень хороший билетик!
        Быстрее смотри.

        Тип: {$type}
        Цена: {$price}
        Ссылка: {$link}
        MESSAGE;

        foreach(self::TELEGRAM_CHAT_IDS as $id){
            $this->telegram->post('sendMessage', [
                'form_params' => [
                    'chat_id' => $id,
                    'text' => $message
                ]
            ]);
        }

        return true;
    }


    public function handle()
    {
        $start = new \DateTime(self::START);
        $end = new \DateTime(self::END);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);
        $iteration_count = iterator_count($period) * count($this->links);

        if(self::REVERSE){
            $period = array_reverse(iterator_to_array($period));
        }

        $bar = $this->output->createProgressBar($iteration_count);
        $bar->start();

        foreach($this->links as $id => $link){

            foreach($period as $date){

                try {
                    $this->browser
                        ->visit(str_replace('***', $date->format('dm'), $link))
                        ->waitUntilMissing('.loader__stripes.--animation-started.--blue', 20);
                }catch (TimeoutException $e){
                    continue;
                }

                $this->browser->storeSource(self::DUMP_FILE_NAME)->screenshot('page');

                $crawler = new Crawler(file_get_contents(Storage::disk('public')->path('source/' . self::DUMP_FILE_NAME . '.txt')));
                $prices = $crawler->filter('div.ticket-desktop__price')->each(function (Crawler $node, $i) {
                    return (integer)preg_replace('/\D+/', '', $node->text());
                });

                $price = array_shift($prices);

                if($price != null){
                    if($price <= self::WOW_PRICE && in_array($id, self::WOW_PRICE_ID)){
                        $this->send2telegram($id, $price, str_replace('***', $date->format('dm'), $link));
                    }

                    Prices::updateOrCreate([
                        'type' => $id,
                        'date' => $date->format('Y-m-d'),
                    ], ['price' => $price]);
                }

                $bar->advance();
            }
        }

        $bar->finish();


        return 0;
    }
}
