<?php

namespace Tokyo;

final class Server
{
    public function __construct(private readonly array $config)
    {
        //
    }

    /**
     * Extract $uri from $SERVER['REQUEST_URI'] variable.
     */
    public static function uriFromRequestUri(string $requestUri): string
    {
        return rawurldecode(
            explode('?', $requestUri)[0]
        );
    }

    /**
     * Extract site name from HTTP host, stripping www. and supporting wildcard DNS.
     */
    public function siteNameFromHttpHost(string $httpHost): string
    {
        $siteName = basename($httpHost, '.' . $this->config['domain']);

        if (str_starts_with($siteName, 'www.')) {
            $siteName = substr($siteName, 4);
        }

        return $siteName;
    }

    /**
     * Extract the domain from the site name.
     */
    public static function domainFromSiteName(string $siteName): string
    {
        return array_slice(explode('.', $siteName), -1)[0];
    }

    /**
     * Determine the fully qualified path to the site.
     * Inspects registered path directories, case-sensitive.
     */
    public function sitePath(string $siteName): ?string
    {
        $sitePath = null;
        $domain = self::domainFromSiteName($siteName);

        foreach ($this->config['paths'] as $path) {
            $handle = opendir($path);

            if ($handle === false) {
                continue;
            }

            $dirs = [];

            while (false !== ($file = readdir($handle))) {
                if (is_dir($path . '/' . $file) && !in_array($file, ['.', '..'])) {
                    $dirs[] = $file;
                }
            }

            closedir($handle);

            // Note: strtolower used below because Nginx only tells us lowercase names
            foreach ($dirs as $dir) {
                if (strtolower($dir) === $siteName) {
                    // early return when exact match for linked subdomain
                    return $path . '/' . $dir;
                }

                if (strtolower($dir) === $domain) {
                    // no early return here because the foreach may still have some subdomains to process with higher priority
                    $sitePath = $path . '/' . $dir;
                }
            }

            if ($sitePath) {
                return $sitePath;
            }
        }

        return null;
    }

    /**
     * Determine if the incoming request is for a static file.
     */
    public function isStaticFile(string $sitePath, string $uri): string|false
    {
        $candidates = [
            $sitePath . '/dist/index.html',
            $sitePath . '/public/index.php',
            $sitePath . '/public/index.html',
            $sitePath . '/index.php',
            $sitePath . '/index.html',
        ];

        foreach ($candidates as $candidate) {
            if ($this->isActualFile($candidate)) {
                break;
            }
            $candidate = null;
        }

        if (!$candidate) {
            return false;
        }

        $candidate = str_replace('/' . basename($candidate), '', $candidate) . $uri;

        return $this->isActualFile($candidate) && is_file($candidate) ?
            $candidate :
            false;
    }

    /**
     * Serve the static file at the given path.
     */
    public function serveStaticFile(string $staticFilePath): void
    {
        /**
         * Backstory...
         *
         * PHP docs *claim* you can set default_mimetype = "" to disable the default
         * Content-Type header. This works in PHP 7+, but in PHP 5.* it sends an
         * *empty* Content-Type header, which is significantly different from
         * sending *no* Content-Type header.
         *
         * However, if you explicitly set a Content-Type header, then explicitly
         * remove that Content-Type header, PHP seems to not re-add the default.
         *
         * I have a hard time believing this is by design and not coincidence.
         *
         * Burn. it. all.
         */
        header('Content-Type: text/html');
        header_remove('Content-Type');

        header('X-Accel-Redirect: /123' . $staticFilePath);
    }

    public function isActualFile(string $filePath): bool
    {
        return !is_dir($filePath) && file_exists($filePath);
    }

    public function getCandidate(string $sitePath, string $uri): ?string
    {
        $candidates = [
            $sitePath . $uri . '/public/index.php',
            $sitePath . $uri . '/index.php',
            $sitePath . '/dist/index.html',
            $sitePath . '/public/index.php',
            $sitePath . '/public/index.html',
            $sitePath . '/index.php',
            $sitePath . '/index.html',
        ];

        foreach ($candidates as $candidate) {
            if ($this->isActualFile($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Get the fully resolved path to the application's front controller.
     */
    public function frontControllerPath(string $sitePath, string $uri): ?string
    {
        $uri = rtrim($uri, '/');

        $candidate = $this->getCandidate($sitePath, $uri);

        if (str_ends_with($candidate, '.html') && $uri && $uri !== basename($candidate)) {
            return null;
        }

        if ($candidate) {
            $_SERVER['SCRIPT_FILENAME'] = $candidate;
            $_SERVER['SCRIPT_NAME'] = str_replace($sitePath, '', $candidate);
            $_SERVER['DOCUMENT_ROOT'] = $sitePath;

            return $candidate;
        }

        return null;
    }
}
