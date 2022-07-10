<?php

namespace A17\Localization\Services;

use Illuminate\Support\Str;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route as RouteFacade;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class Routing
{
    /**
     * Route front helper. Returns a clean URL.
     */
    public function routeFront(string $name, array $parameters = [], bool $absolute = true): string
    {
        // Generate URL and remove the query
        $url = Str::before($this->routeFrontWithQuery($name, $parameters, $absolute), '?');

        // Get all route parameters
        // And remove the ones needed by the route
        $parameters = (new Collection($parameters))->except($this->getRouteParameters($this->frontName($name)));

        // Add the left ones as queries
        if ($parameters->isNotEmpty()) {
            $url .= '?' . $parameters->map(fn($value, $key) => "{$key}={$value}")->implode('&');
        }

        return $url;
    }

    public function frontName(string $name): string
    {
        return "front.{$name}";
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function getRouteParameters(string $name): Collection
    {
        $route = $this->findRoute($name);

        if (blank($name) || empty($route)) {
            return new Collection();
        }

        preg_match_all('/{([^}]*)}/', $route->uri, $matches);

        return (new Collection($matches[1]))->map(fn($v) => str_replace('?', '', $v));
    }

    /**
     * Route front helper. Returns a SIGNED URL keeping the current query and adding new ones if needed.
     */
    public function routeFrontWithQuerySigned(string $name, array $parameters = [], bool $absolute = true): string
    {
        return $this->routeFrontWithQuery($name, $parameters, $absolute, true);
    }

    /**
     * Route front helper. Returns an URL keeping the current query and adding new ones if needed.
     */
    public function routeFrontWithQuery(
        string $name,
        array $parameters = [],
        bool $absolute = true,
        bool $signed = false,
    ): string {
        $name = $this->frontName($name);

        $current = request()->query();

        /** @var \Illuminate\Routing\Route $route */
        $route = RouteFacade::getRoutes()->getByName($name);

        if (blank($route)) {
            throw new RouteNotFoundException("A route '{$name}' was not found");
        }

        $combined = (new Collection(array_replace_recursive((array) $current, $parameters)))
            ->filter(fn($value) => filled($value))
            ->toArray();

        $combined['locale'] = $parameters['locale'] ?? locale();

        (new Collection($route->wheres))->each(function ($value, $parameter) use (&$combined) {
            if (!isset($combined[$parameter])) {
                $combined[$parameter] = __($parameter);
            }
        });

        URL::forceRootUrl(config('app.url'));

        unset($combined['signature']);

        try {
            return $signed ? URL::signedRoute($name, $combined) : route($name, $combined, $absolute);
        } catch (\Exception $exception) {
            \Log::error(
                'Could not generate URL for ' .
                    json_encode([
                        'name' => $name,
                        'route arguments' => $combined,
                        'is_absolute' => $absolute,
                    ]),
            );

            return url()->to('/');
        }
    }

    public function routeIsCurrent(string $name): bool
    {
        $current = Str::after(optional(RouteFacade::current())->getName(), 'front.');

        if (Str::endsWith($name, '.')) {
            return Str::startsWith($current, $name);
        }

        return $current === $name;
    }

    public function findRoute(string $name): Route|null
    {
        /** @var \Illuminate\Routing\RouteCollection $internalRoutes */
        $internalRoutes = RouteFacade::getRoutes();

        return (new Collection($internalRoutes))->first(function (\Illuminate\Routing\Route $route) use ($name) {
            return $route->getName() === $name;
        });
    }
}
