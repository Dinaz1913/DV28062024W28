<?php

namespace Reelz222z\Cryptoexchange\Controller;

use GuzzleHttp\Client;
use Reelz222z\Cryptoexchange\Model\CoinMarketCapApiClient;
use Reelz222z\Cryptoexchange\Model\TransactionHistory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CryptoController
{
    private $client;
    private $apiUrl;
    private $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest';
        $this->apiKey = $_ENV['COINMARKETCAP_API_KEY'];
    }

    public function fetchTopCryptocurrencies()
    {
        $cryptoData = new CoinMarketCapApiClient($this->client, $this->apiUrl, $this->apiKey);
        return $cryptoData->fetchTopCryptocurrencies();
    }

    public function getCryptocurrencyBySymbol($symbol)
    {
        $cryptoData = new CoinMarketCapApiClient($this->client, $this->apiUrl, $this->apiKey);
        return $cryptoData->getCryptocurrencyBySymbol($symbol);
    }

    public static function getTransactions($userId)
    {
        return TransactionHistory::getTransactions($userId);
    }

    public static function addTransaction($userId, $symbol, $amount, $type, $price)
    {
        TransactionHistory::addTransaction($userId, $symbol, $amount, $type, $price);
        return true;
    }

    public function index(Request $request)
    {
        return $this->fetchTopCryptocurrencies();
    }

    public function detail(Request $request, $symbol): ?\Reelz222z\Cryptoexchange\Model\Cryptocurrency
    {
        return $this->getCryptocurrencyBySymbol($symbol);
    }

    public function portfolio(Request $request)
    {
        $user = $request->getSession()->get('user');
        return self::getTransactions($user->getId());
    }

    public function buy(Request $request)
    {
        $symbol = $request->request->get('symbol');
        $amount = (float) $request->request->get('amount');
        $user = $request->getSession()->get('user');
        $crypto = $this->getCryptocurrencyBySymbol($symbol);
        if ($crypto) {
            try {
                $user->getWallet()->deduct($crypto->getQuote()->getPrice() * $amount);
                self::addTransaction($user->getId(), $crypto->getSymbol(), $amount, 'buy', $crypto->getQuote()->getPrice());
                return true;
            } catch (\Exception $e) {
                return ['crypto' => $crypto, 'error' => $e->getMessage()];
            }
        }
    }

    public function sell(Request $request)
    {
        $symbol = $request->request->get('symbol');
        $amount = (float) $request->request->get('amount');
        $user = $request->getSession()->get('user');
        $crypto = $this->getCryptocurrencyBySymbol($symbol);

        $transactions = self::getTransactions($user->getId());
        $holdings = [];
        foreach ($transactions as $transaction) {
            if ($transaction['asset'] === $symbol) {
                if ($transaction['transaction_type'] === 'buy') {
                    $holdings[$symbol] = ($holdings[$symbol] ?? 0) + $transaction['amount'];
                } else if ($transaction['transaction_type'] === 'sell') {
                    $holdings[$symbol] = ($holdings[$symbol] ?? 0) - $transaction['amount'];
                }
            }
        }
        $currentQuantity = $holdings[$symbol] ?? 0;

        if ($crypto) {
            if ($amount > $currentQuantity) {
                return ['crypto' => $crypto, 'error' => 'You do not have enough of this cryptocurrency to sell.'];
            }

            $totalEarnings = $crypto->getQuote()->getPrice() * $amount;
            $user->getWallet()->add($totalEarnings);
            self::addTransaction($user->getId(), $crypto->getSymbol(), $amount, 'sell', $crypto->getQuote()->getPrice());
            return true;
        }
    }
}
