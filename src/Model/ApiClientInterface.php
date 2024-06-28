<?php

namespace Reelz222z\Cryptoexchange\Model;

interface ApiClientInterface
{
    public function fetchTopCryptocurrencies(): array;
    public function getCryptocurrencyBySymbol(string $symbol): ?Cryptocurrency;
    public function getCryptocurrencyBySymbolForUser(string $symbol, User $user): ?Cryptocurrency;
}
