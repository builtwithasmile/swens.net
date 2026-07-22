<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\Template;
use App\Core\HttpClient;

class CryptoWatchController
{
    public function index(Request $request, Response $response): void
    {
        $api = HttpClient::get(
            'https://api.coingecko.com/api/v3/coins/markets'
            . '?vs_currency=cad&order=market_cap_desc&per_page=10&page=1&price_change_percentage=24h',
            ['User-Agent: swens.net-cryptowatch/1.0 (personal watchlist)'],
            10
        );

        $data = $api['status'] === 200 ? HttpClient::jsonBody($api) : null;

        $html = Template::render('pages/cryptowatch', [
            'title'   => 'Crypto Watch',
            'coins'   => is_array($data) ? $data : [],
            'error'   => is_array($data) ? null : 'Prices are unavailable right now — reload in a minute.',
            'updated' => date('Y-m-d H:i:s'),
        ], 'bare');

        $response->html($html);
    }
}
