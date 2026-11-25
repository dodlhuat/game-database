<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

/**
 * Every call the frontend makes (api.get('/…'), api.post(`/…/${id}`), …)
 * must hit a route that exists with that method. The frontend tests mock
 * the API, so a typo in a path or a wrong method would otherwise only show
 * up live.
 */
class FrontendApiPathsTest extends TestCase
{
    /** @return list<array{method: string, path: string, where: string}> */
    private function frontendCalls(): array
    {
        $root = base_path('../frontend');
        if (! is_dir($root.'/composables')) {
            // the backend container only mounts backend/
            $this->markTestSkipped('frontend/ is not available here');
        }

        $calls = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = $file->getPathname();
            if (! preg_match('#/frontend/(composables|pages|components|stores|utils|middleware)/.*\.(ts|vue)$#', $path)) {
                continue;
            }
            $source = (string) file_get_contents($path);
            preg_match_all(
                '/\bapi\.(get|post|put|patch|delete|download)\s*(?:<(?:[^<>]|<[^<>]*>)*>)?\(\s*([`\'"])(\/[^`\'"]*)\2/',
                $source,
                $matches,
                PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
            );
            foreach ($matches as $m) {
                $method = strtoupper($m[1][0] === 'download' ? 'get' : $m[1][0]);
                $url = $m[3][0];
                if (str_contains($url, '_method=PUT')) {
                    $method = 'PUT';
                }
                $line = substr_count(substr($source, 0, $m[0][1]), "\n") + 1;
                $calls[] = [
                    'method' => $method,
                    'path' => strtok($url, '?') ?: $url,
                    'where' => str_replace($root.'/', '', $path).':'.$line,
                ];
            }
        }

        return $calls;
    }

    /** "/admin/loans/${id}/hand-out" and "api/admin/loans/{loan}/hand-out" match */
    private function routeServes(Route $route, string $method, string $path): bool
    {
        if (! in_array($method, $route->methods(), true)) {
            return false;
        }
        $want = explode('/', trim($path, '/'));
        $have = explode('/', preg_replace('#^api/#', '', $route->uri()) ?? '');
        if (count($want) !== count($have)) {
            return false;
        }
        foreach ($want as $i => $segment) {
            $isParam = str_contains($segment, '${');
            $routeParam = str_starts_with($have[$i], '{');
            if ($routeParam) {
                continue; // a route parameter takes any value
            }
            if ($isParam || $segment !== $have[$i]) {
                return false;
            }
        }

        return true;
    }

    public function test_the_scan_finds_the_calls(): void
    {
        $this->assertGreaterThan(100, count($this->frontendCalls()));
    }

    public function test_every_frontend_call_has_a_matching_route(): void
    {
        $routes = collect(Router::getRoutes()->getRoutes())->filter(fn (Route $r) => str_starts_with($r->uri(), 'api/'));

        $unmatched = collect($this->frontendCalls())
            ->reject(fn (array $c) => $routes->contains(fn (Route $r) => $this->routeServes($r, $c['method'], $c['path'])))
            ->map(fn (array $c) => "{$c['method']} {$c['path']}  ({$c['where']})")
            ->values()
            ->all();

        $this->assertSame([], $unmatched, 'frontend calls without a matching backend route');
    }
}
