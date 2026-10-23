# \\compose\\  -  A lightweight web-based CMS

**\\compose\\** is a CMS (Content Management System) platform written in PHP that
provides functionalities for fast-developing web applications on Linux servers. 

Born to be modular, **\\compose\\** is built around the concept of installable
packages. The built-in Core package is responsible for managing the
third-party packages and allows us to install, remove, update, enable/disable
packages and single components such as API End-points or Pages directly from
the browser.

<!-- REF to Bootstrap v3.3.1 -->
Features:
- Built-in support for different types of users;
- 4 types of users supported by default: `guest`, `user`, `supervisor`, `administrator`;
- Web-based package manager;
- Web-based pages manager;
- Web-based RESTful API end-points manager;
- Built-in support for Google Sign-In OAuth 2.0 authentication;
- Built-in HTTP RESTful API service;
- Error handler;
- Graphics based on [Bootstrap (v3.3.1)](https://getbootstrap.com/docs/3.3/getting-started/);
- *and many many more...*


## Runtime requirements

**\\compose\\** requires a supported, patched PHP runtime, including the OpenSSL
extension. The Docker image defaults to the PHP 8.3 branch.

| PHP branch | Minimum version |
|------------|-----------------|
| 8.3 | 8.3.36 |
| 8.4 | 8.4.27 |
| 8.5 | 8.5.12 |

These releases fix both PHP2Shell vulnerabilities,
[CVE-2026-109618](https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w)
and [CVE-2026-109619](https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp).
Composer checks the actual CLI runtime after installation and updates, rather
than trusting only its configured target platform. The image build and entrypoint also
check the selected PHP-FPM binary and matching installed `fpm`/`common` packages;
they fail explicitly for outdated versions, prereleases, or unreviewed branches.
For these PHP 8 branches, the checks require the upstream fixed versions above.
The runtime guard also recognizes the independently verified PHP 7.4 backport
in Ondrej's Jammy/Noble PPA packages at revision `1:7.4.33-31` or later, checking
the full installed FPM/common package revisions rather than only `PHP_VERSION`.
That exception is for legacy deployments using the guard separately: this
application and its current authentication dependencies still require PHP 8.
Updating this guard invalidates the cached PHP installation layer.
The debug image checks its inherited runtime too. Build the matching production
base first; the obsolete external PHP 7.4 base is not used:

```sh
docker build --build-arg ARCH=arm64v8 \
  --build-arg COMPOSE_VERSION=<patched-branch-or-tag> \
  -t duckietown/compose:php8.3-arm64v8 .
docker build -f Dockerfile.debug --build-arg ARCH=arm64v8 \
  --build-arg BASE_VERSION=php8.3 -t duckietown/compose:debug-arm64v8 .
```

Use `amd64` instead of `arm64v8` on x86-64. The debug image installs the matching
PHP branch's packaged Xdebug 3 and enables debugging/profiling on request triggers.

Google Sign-In uses PHP-JWT 7 with RSA verification keys explicitly bound to
RS256 and a minimum key size of 2048 bits. The bundled Google client and its
Composer-managed dependencies must be deployed together; copying only an updated
lock file does not update the checked-in libraries.

PHP2Shell patching and the PHP 8 authentication migration are separate changes.
Legacy PHP 7.4 deployments on the reviewed Jammy/Noble PPA can receive the
verified package backport without loading this application's PHP 8-only
dependencies. That package availability does not extend to Ubuntu Focal: the
public PPA currently publishes neither PHP 7.4 nor PHP 8.3 packages for Focal.
Rebuilding with this PR's application code requires matching PHP 8 changes in
`dt-compose-commons` and `dt-device-dashboard`, including a supported PHP 8
package source for the retained legacy base, the copied security guard, PHP
configuration, and FPM socket. Do not replace the Focal/Python 3.8 environment
implicitly or bump only the compose source tag in a PHP 7.4 dashboard image.

### Authentication regression tests

With patched PHP, Composer, Bash, and Debian's `dpkg` installed:

```sh
cd public_html/system/classes/google_api_php_client
composer install
composer test
composer validate --strict
composer audit --locked
composer install --no-dev
```

The tests use generated test keys, mocked HTTP responses, and controlled FPM
package/version responses; Google credentials and network access to Google's
authentication endpoints are not required.
The test suite and PHPUnit configuration live at the repository root, outside
the web document root. The Composer command selects that configuration explicitly.
The final command restores the production-only vendor tree before committing
or deploying the bundled dependencies.

### Rebuilding after the PHP2Shell update

Rebuild the intended release with refreshed packages, without reusing the old
PHP installation cache, and select the appropriate architecture:

```sh
docker build --pull --no-cache --build-arg ARCH=amd64 \
  --build-arg COMPOSE_VERSION=<patched-branch-or-tag> \
  -t duckietown/compose:<new-tag> .
```

Recreate the running containers from the new image so that **every FPM worker**
uses the patched runtime. Restarting an old image or reloading only Nginx does
not install the fixes. Verify the serving container, rather than a separate CLI
installation:

```sh
docker exec <container> sh -c 'check-php-security "$PHP_VERSION"'
```

Externally managed PHP installations must update their FPM/common packages and
restart all FPM workers as well. Configuration workarounds, including disabling
session upload progress, do not replace installing both security fixes.


## Architecture

You can use **\\compose\\** out-of-the-box, though there is not much to see or
do until you create or install packages. **\\compose\\** is designed to be
modular with a built-in `core` package that provides all the functionalities needed for
managing additional packages and their components. A `Configuration` class provides
functionalities for managing settings for both the `core` package and other
additional packages.

The [RESTful](https://restfulapi.net/) API module in **\\compose\\** offers an easy
way for packages to export API end-points accessible via HTTP. 


## Documentation

Check the [Full Documentation](http://compose.afdaniele.com/docs/latest/) if you want to
learn more about **\\compose\\**


## \\compose\\ in Docker

**\\compose\\** is also available as a Docker image.


### Build your own image using Dockerfile

You can download our Docker files from (here)[https://github.com/duckietown/compose/tree/master/docker].
To build a Docker image from one of our Docker files run:

`docker build -t <image_name>:<image_tag> .`


### Pre-built Docker images

You can find the list of pre-built images on
(Docker Hub)[https://cloud.docker.com/repository/docker/duckietown/compose/tags].


### Run image with \\compose\\ outside the container (suggested)

Run

`docker run -itd -p <PORT>:80 -v <COMPOSE_ROOT_HOST_DIR>:/var/www/html duckietown/compose:<IMAGE_TAG>`

**NOTE:** `<COMPOSE_ROOT_HOST_DIR>` is the path to the directory on the host that contains the root of the `\compose\` repository. This path should contain a `public_html` dir.


### Run image with \\compose\\ inside the container

**NOTE:** All the changes made in **\\compose\\** will be saved inside the container. If you delete the container without committing, all the changes will be lost.

Run

`docker run -d -p <PORT>:80 duckietown/compose:<IMAGE_TAG>`
 
