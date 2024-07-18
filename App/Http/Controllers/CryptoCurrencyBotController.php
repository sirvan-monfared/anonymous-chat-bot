<?php

namespace App\Http\Controllers;

use App\Helpers\TelegramBotService;

class CryptoCurrencyBotController extends BaseController
{
    public function inbounce(): void
    {
        $telegram = new TelegramBotService();
        $command = false;

        if ($telegram->text() === '/start') {
            $command = true;

            $loading_message = $this->showLoading($telegram);

            $data = $this->getData();

            $menu = $this->createMenuFromCryptos($data);

            $telegram->editMessage("یکی از این گزینه ها رو انتخاب کن", $menu, $loading_message);
        }

        if (str_starts_with($telegram->text(), '/show/')) {
            $command = true;

            $param = str_replace('/show/', '', $telegram->text());

            $loading_message = $this->showLoading($telegram);

            $data = $this->getData();
            $result =  array_filter($data, fn($crypto) => $crypto->key === $param);
            $found = array_shift($result);

            $output = "نام: {$found->name} ({$found->name_en}) \n\n";
            $output .= " بیشترین قیمت روزانه: {$found->daily_high_price} \n\n";
            $output .= " تغییرات قیمت روزانه: {$found->price_change_24h} \n\n";

            $menu = [
                ["بازگشت" => "/start"]
            ];


            $telegram->editMessage($output, keyboard_structure: $menu, message_to_edit: $loading_message);
        }

        if ($telegram->text() === '/search') {
            $command = true;

            $telegram->sendMessage("نام رمزارز موردنظر رو وارد کن");
        }

        if (! $command) {
            $loading_message = $this->showLoading($telegram);

            $data = $this->getData();

            $search_param = trim($telegram->text());
            $result = array_filter($data, fn($crypto) => str_contains($crypto->name, $search_param) || str_contains($crypto->name_en, $search_param));

            $menu = $this->createMenuFromCryptos($result);

            $telegram->editMessage("یکی از گزینه ها رو انتخاب کن", $menu, $loading_message);
        }

    }

    private function getData()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://one-api.ir/DigitalCurrency/?token='.env('ONE_API_TOKEN'),
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return (json_decode($response))?->result;
    }


    public function createMenuFromCryptos(array $data): array
    {
        $menu = [];
        foreach (array_slice($data, 0, 6) as $crypto) {
            $menu[] = ["{$crypto->name} ({$crypto->name_en})" => "/show/{$crypto->key}"];
        }
        $menu[] = ["جستجو 🔍" => "/search"];
        return $menu;
    }
}