<?php

namespace A17\Localization\Services;

use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use A17\Localization\Support\Constants;
use Illuminate\Routing\Route as IlluminateRoute;

class Localization
{
    protected array $config = [];

    protected string|null $currentLocale = null;

    protected string|null $cookieName = null;

    protected string|null $routeName = null;

    protected array $availableLocales = [];

    protected string|null $queryLocale = null;

    protected string|null $urlLocale = null;

    protected array|string|null $cookieLocale = null;

    protected string|null $browserLocale = null;

    protected string|null $sessionLocale = null;

    protected string|null $loggedUserLocale = null;

    protected string|null $appLocale = null;

    protected string|null $configLocale = null;

    protected string|null $fallbackLocale = null;

    protected Request|null $request = null;

    protected array $translatableUris = [];

    protected string|null $forcedLocale = null;

    protected string|null $cacheBasePrefix = null;

    protected array|string|null $headersLocale = null;

    protected array|string|null $twillLocale = null;

    protected mixed $routeLocale = null;

    protected Routing $routing;

    public function __construct()
    {
        $this->freshLocales();

        $this->routing = new Routing();
    }

    protected function buildRedirect(string|null $url = null): RedirectResponse|null
    {
        $current = $this->getUrlForCurrentRequest();

        $next = $this->buildUrl($url);

        if ($current === $next || is_null($next)) {
            return null;
        }

        return (new RedirectResponse($next))
            ->setStatusCode(302) // TODO: Is this is a temporary redirect or permanent?
            ->withCookie($this->cookie());
    }

    protected function buildUrl(string|null $url = null): string|null
    {
        $url ??= $this->getUrlForCurrentRequest();

        if (blank($url) || empty($url)) {
            return null;
        }

        $parsed = parse_url($url);

        /** @phpstan-ignore-next-line */
        $path = preg_replace('/\/(..)\/(.*)/', "/{$this->appLocale}/\$2", $parsed['path'] ?? null);

        /** @phpstan-ignore-next-line */
        $host = $parsed['host'];

        $parameters = $this->translateParameters((string) $path, $url);

        return sprintf('%s://%s/%s', $this->getRequest()->getScheme(), $host, $parameters);
    }

    protected function cleanupLocale(string|null $locale): string|null
    {
        if (blank($locale) || empty($locale)) {
            return null;
        }

        if (strlen($locale) > 2) {
            preg_match('/([a-z]{2}([-_][a-zA-Z]{2})?)/', $locale, $matches);

            $locale = $matches[0] ?? null;
        }

        return $locale;
    }

    protected function getAvailableLocales(): array
    {
        return $this->availableLocales ?? ($this->availableLocales = config('translatable.locales', []));
    }

    protected function getBrowserLocale(): string|null
    {
        return $this->browserLocale ?? ($this->browserLocale = $this->inferBrowserLocale());
    }

    protected function getCookieLocale(): array|string|null
    {
        return $this->cookieLocale ?? ($this->cookieLocale = $this->getRequest()->cookie($this->cookieName));
    }

    protected function getHeadersLocale(): string|null
    {
        $xLang = request()->header('X-LANG');

        return is_array($xLang) ? $xLang[0] ?? '' : $xLang;
    }

    protected function getLoggedUserLocale(): string|null
    {
        return auth()->user()->locale ?? (auth()->user()->language ?? null);
    }

    protected function getQueryLocale(): string|null
    {
        $locale = $this->cleanupLocale($this->getRequest()->get('locale'));

        $locale = $this->localeIsAllowed($locale) ? $locale : null;

        return $this->queryLocale ?? ($this->queryLocale = $locale);
    }

    protected function getRouteLocale(): string|null
    {
        $uri = $this->getRequestUri();

        if (is_array($uri)) {
            throw new \Exception('getRequestUri() returned an array');
        }

        $locale = (new Collection(explode('/', (string) $uri)))->filter()->first();

        if (empty($locale)) {
            return null;
        }

        if ($this->locales()->contains($locale)) {
            return $locale;
        }

        return null;
    }

    protected function getRouteName(): string|null
    {
        return $this->routeName ?? ($this->routeName = Route::currentRouteName());
    }

    public function getAppLocale(): string|null
    {
        return app()->currentLocale();
    }

    public function getLocale(bool $refresh = false): string|null
    {
        if (!$refresh && filled($this->currentLocale)) {
            return $this->currentLocale;
        }

        foreach ($this->getLocaleMatrix() as $locale) {
            if (filled($locale) && $this->localeIsAllowed($locale)) {
                return $this->currentLocale = $locale;
            }
        }

        return null;
    }

    public function getLocaleMatrix(bool $withUrl = true): array
    {
        return (new Collection([
            'forced_locale' => $this->forcedLocale, // Forced locale will always be the first one
            'query_locale' => $this->queryLocale, // Query beats URL
            'url_locale' => $withUrl ? $this->urlLocale : null, // Usually the most important locale comes from the current URL
            'headers_locale' => $this->headersLocale, // JavaScript can define it on headers
            'twill_locale' => $this->twillLocale,
            'route_locale' => $this->routeLocale, // If getURL is not able to get it
            'session_locale' => $this->sessionLocale,
            'cookie_locale' => $this->cookieLocale,
            'user_locale' => $this->loggedUserLocale, // User is important but the "forced" ones come first
            'browser_locale' => $this->browserLocale,
            'app_locale' => $this->appLocale,
            'config_locale' => $this->configLocale,
            'fallback_locale' => $this->fallbackLocale,
        ]))->toArray();
    }

    protected function getRequest(): Request
    {
        return $this->request ?? request();
    }

    protected function getRouteByUrl(string|null $url): IlluminateRoute|null
    {
        if (blank($url) || empty($url)) {
            return null;
        }

        return $route = app('router')
            ->getRoutes()
            ->match(app('request')->create($url));
    }

    protected function getSessionLocale(): string|null
    {
        // If we have a logged user,
        // we always use the internal user selected locale
        if (filled($userLocale = $this->getLoggedUserLocale())) {
            return $userLocale;
        }

        return $this->sessionLocale ?? ($this->sessionLocale = session()->get('locale'));
    }

    protected function getTranslatableLocale(): string
    {
        return config('translatable.locale', app()->getLocale());
    }

    protected function getUrlForCurrentRequest(): string|null
    {
        if (empty($this->getRouteName())) {
            return null;
        }

        return route(
            $this->getRouteName(),
            $this->getRequest()
                ->route()
                ?->parameters(),
        );
    }

    protected function getUrlLocale(): string|null
    {
        return $this->urlLocale ?? ($this->urlLocale = $this->getRequest()->segment(1));
    }

    /**
     * @return string|null
     */
    protected function inferBrowserLocale(): string|null
    {
        if (app()->runningInConsole()) {
            return null;
        }

        $language = $this->getRequest()->getPreferredLanguage();

        if ($this->localeIsAllowed($language)) {
            return $language;
        }

        $language = Str::before((string) $language, '_');

        if ($this->localeIsAllowed($language)) {
            return $language;
        }

        return null;
    }

    protected function freshLocales(): void
    {
        $this->availableLocales = $this->getAvailableLocales();

        $this->routeName = $this->getRouteName();

        $this->cookieName = Str::slug(env('APP_NAME', 'laravel'), '_') . '_language';

        $this->queryLocale = $this->getQueryLocale();

        $this->urlLocale = $this->getUrlLocale();

        $this->routeLocale = $this->getRouteLocale();

        $this->headersLocale = $this->getHeadersLocale();

        $this->twillLocale = $this->getTwillLocale();

        $this->loggedUserLocale = $this->getLoggedUserLocale();

        $this->cookieLocale = $this->getCookieLocale();

        $this->browserLocale = $this->getBrowserLocale();

        $this->sessionLocale = $this->getSessionLocale();

        $this->configLocale = config('app.locale');

        $this->fallbackLocale = config('app.fallback_locale');

        $this->appLocale = $this->getAppLocale();

        $this->translatableUris = Constants::TRANSLATABLE_URL_PARAMETERS;

        $this->currentLocale = $this->getLocale(true); // force refresh

        $this->setLocale($this->currentLocale, $this->forcedLocale, true); /// force setting Laravel locale
    }

    protected function isTranslatableUriParameter(string $uri): bool
    {
        return (new Collection($this->translatableUris))->contains($uri);
    }

    protected function localeIsAllowed(string|null $locale): bool
    {
        return filled($locale) && (new Collection($this->availableLocales))->contains($locale);
    }

    protected function removeFirstSlash(string|null $path): string
    {
        if (is_null($path)) {
            return '';
        }

        return Str::startsWith($path, '/') ? Str::after($path, '/') : $path;
    }

    protected function replaceTranslatedParameters(string $path, IlluminateRoute|null $route): string|null
    {
        if (empty($route) || blank($path)) {
            return $path;
        }

        $uri = $route->uri;

        if (Str::startsWith($path, '/')) {
            $path = Str::after($path, '/');
        }

        if (Str::startsWith($uri, '/')) {
            $uri = Str::after($uri, '/');
        }

        $paths = new Collection(explode('/', $path));
        $uris = new Collection(explode('/', $uri));

        $optionalCount = $uris->reduce(function ($count, $uri) {
            return $count + Str::endsWith($uri, '?}') ? 1 : 0;
        }, 0);

        for ($x = 1; $x <= $optionalCount; $x++) {
            $paths[] = '';
        }

        if ($paths->count() !== $uris->count()) {
            return $path;
        }

        $uri = $paths
            ->combine($uris)
            ->map(function ($uri, $path) {
                if (!Str::startsWith($uri, '{')) {
                    return $path;
                }

                $uri = Str::beforeLast(Str::after($uri, '{'), '}');

                if ($uri === 'locale') {
                    return locale();
                }

                return $this->isTranslatableUriParameter($uri) ? __($uri) : $path;
            })
            ->filter()
            ->join('/');

        return $uri;
    }

    protected function requiresRedirect(string|null $locale): bool
    {
        return !request()->expectsJson() && ($this->appLocale !== $locale || $this->urlLocale == null);
    }

    public function setAndRedirect(string|null $locale): RedirectResponse|null
    {
        if (blank($locale)) {
            return null;
        }

        $this->setLocale($locale, true);

        if ($this->requiresRedirect($locale)) {
            return $this->buildRedirect();
        }

        return null;
    }

    public function setAndRedirectBack(string $locale): RedirectResponse|null
    {
        $this->setLocale($locale, true); /// force this locale

        return $this->buildRedirect(URL::previous());
    }

    public function setLocale(string|null $locale, string|null|bool $forced = null, bool $freshingItUp = false): bool
    {
        if (!$this->localeIsAllowed($locale)) {
            return false;
        }

        if ($forced) {
            $this->forcedLocale = is_string($forced) ? $forced : $locale;
        }

        $changed = $locale !== $this->appLocale;

        $this->appLocale = $locale;

        config([
            'app.locale' => $locale,
            'translatable.locale' => $locale,
        ]);

        if (!empty($locale)) {
            app()->setLocale($locale);
        }

        session()->put('locale', $locale);

        $this->cookie($locale);

        if (!$freshingItUp) {
            $this->freshLocales();
        }

        return $changed;
    }

    public function setFromRequest(Request $request): RedirectResponse|null
    {
        $this->request = $request;

        return $this->setAndRedirect($this->getLocale());
    }

    protected function cookie(string|null $locale = null): string|null
    {
        $locale ??= $this->appLocale;

        if (empty($locale) || empty($this->cookieName)) {
            return null;
        }

        $cookie = cookie()->forever($this->cookieName, $locale);

        Cookie::queue($cookie);

        return $cookie;
    }

    public function setTranslatableUris(array $translatableUris): void
    {
        $this->translatableUris = $translatableUris;
    }

    protected function translateParameters(string $path, string|null $url): string
    {
        return $this->removeFirstSlash($this->replaceTranslatedParameters($path, $this->getRouteByUrl($url)));
    }

    protected function getTwillLocale(): string|null
    {
        return $this->getRequest()->get('activeLanguage');
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;

        $this->freshLocales();

        return $this;
    }

    protected function getRequestUri(): array|string|null
    {
        return $this->getRequest()->server('REQUEST_URI', $_SERVER['REQUEST_URI'] ?? null);
    }

    public function getInstance(): self
    {
        return $this;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function locales(): Collection
    {
        return (new Collection(
            config('translatable.locales') ?? [config('app.locale'), config('fallback_locale.locale')],
        ))->unique();
    }

    public function locale(string|null $new = null): string|null
    {
        if (filled($new)) {
            $this->setLocale($new, true); /// force this locale for now on
        }

        return $this->getLocale();
    }

    public function routing(): Routing
    {
        return $this->routing;
    }
}
