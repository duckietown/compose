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


## PHP Runtime Security

This release keeps the legacy PHP 7.4 application and authentication dependencies.
The PHP 8/JWT migration is separate; do not copy its Composer manifest, lock file,
or bundled vendor tree into a PHP 7.4 dashboard.

The shared runtime guard checks the selected PHP-FPM binary and the installed
`fpm` and `common` packages. PHP 7.4 is accepted only for reviewed backports:

| Distribution | Minimum package revision |
|--------------|--------------------------|
| Ubuntu Focal ESM | `7.4.3-4ubuntu2.29+esm5` |
| Ondrej Jammy/Noble PPA | `1:7.4.33-31` |
| Duckietown Focal source build | Exactly `7.4.3-4ubuntu2.29+duckietown1.php2shell.712e60f9` |

Canonical lists the Focal ESM revision as fixed for both
[CVE-2026-109618](https://ubuntu.com/security/CVE-2026-109618) and
[CVE-2026-109619](https://ubuntu.com/security/CVE-2026-109619).
Unpatched Focal archive packages, older revisions, unrecognized package forms,
mismatched FPM/common revisions, and non-FPM binaries fail the guard. The build
checks immediately after installing PHP and the entrypoint checks before
starting FPM. Changing the guard invalidates the PHP installation layer.

The image also disables `session.upload_progress.enabled` in the active FPM
configuration and limits nginx request bodies to 8 MiB, including chunked
requests without `Content-Length`. Requests above this limit receive HTTP 413.
These mitigations reduce exposure and do not replace installing both fixes.
All FPM workers must be recreated from the patched image; restarting an old
container or rebuilding against Focal's public archive does not install the fix.

### Standalone and Debug Builds

The standalone image retains its Jammy base and PHP 7.4 default. Build a legacy
application commit or tag, not the PHP 8 migration branch:

```sh
docker build --pull --no-cache --build-arg ARCH=arm64v8 \
	--build-arg COMPOSE_VERSION=<legacy-commit-or-tag> \
	-t duckietown/compose:v<security-version>-arm64v8 .
make debug-build ARCH=arm64v8 COMPOSE_VERSION=<legacy-commit-or-tag>
```

Use `amd64` on x86-64. The debug target builds its matching local
`duckietown/compose:debug-base-<arch>` first and reuses it without a registry
pull. It installs the selected PHP branch's packaged Xdebug 3.

### Focal Dashboard Builds

The security-only `dt-compose-commons` build retains
`dt-legacy-py38-environment` (Focal/Python 3.8) and builds PHP 7.4 Debian packages
from source. BuildKit is required; Ubuntu Pro and a Focal Ondrej PPA are not.
The build pins:

- Signed-APT source package: `php7.4=7.4.3-4ubuntu2.29`.
- Upstream backport: [712e60f9edfd03f0960fc4fc3c2c375f42b90aa2](https://github.com/php/php-src/commit/712e60f9edfd03f0960fc4fc3c2c375f42b90aa2).
- Patch SHA-256: `5fe57ca6ee96d59b36c6e0ca842843c50884f177bef1e85234cf669b577f5e48`.
- Built package revision: `7.4.3-4ubuntu2.29+duckietown1.php2shell.712e60f9`.

Four upstream hunks already exist in Focal's source. The build proves those
with reverse dry-runs and applies the remaining hunks through Debian's patch
series, with zero fuzz. It builds CLI, FPM, common, and matching extensions
together; APCu and memcached remain Focal packages. The guard rejects other
custom revisions and checks the full Debian version reported by FPM against
both installed packages.

The custom packages are compiled in the `php-packages` stage of the
security-only `dt-compose-commons` Dockerfile. Its final stage mounts that
stage's `/build` directory and installs the generated `.deb` files directly;
it does not download this custom revision from an APT repository. The guard
alone does not build or install the backport. No custom package repository or
container image has been published yet.

To reproduce the package build, run this from the `dt-compose-commons`
security branch:

```sh
docker buildx build --load --target php-packages \
	--build-arg ARCH=arm64v8 --build-arg DISTRO=ente \
	--build-arg DOCKER_REGISTRY=docker.io \
	--build-arg BASE_REPOSITORY=dt-legacy-py38-environment \
	-t compose-php74-packages:local .
```

Pass `--build-arg COMPOSE_VERSION=<security-version>` alongside the usual
project arguments. This must resolve to the patched standalone image above.
The inherited `1.3.5` pin predates the security guard and cannot be used for this
build. A release must publish and pin its new security image before merging the
downstream change. A local build may use a matching locally tagged image.

Upstream describes this backport as a minimal mitigation for an EOL version.
This is a temporary legacy measure, not continuing PHP 7.4 security support or
a general security assurance for the legacy application dependencies. Maintaining
the custom package and reviewing future vulnerabilities remain release-owner
responsibilities. Do not replace a future security update with this exact old
revision merely because it passes the current guard.

Verify the rebuilt serving container with
`check-php-security "$PHP_VERSION"`, and confirm FPM reports upload progress Off.
ARM64 standalone, debug, retained-Focal commons, and dashboard builds have been
validated. Isolated containers passed health, complete setup/Robot/Login HTML,
legacy autoloading, the real PHP 7.4 socket with an empty `PHP_VERSION`, and
HTTP 413 for oversized declared and chunked requests. AMD64, authenticated
Files/Mission Control views, live OAuth, and robot telemetry remain unverified.
The pinned Duckiedrone package still lacks the ROS-config endpoint script;
`/api/ros-config` returns HTTP 404 through the working FPM socket. That separate
package issue and architecture validation remain release gates.

### Regression Tests

With PHP 7.4, Bash, Debian's `dpkg`, and PHPUnit 9.6 available:

```sh
php /path/to/phpunit-9.phar --configuration phpunit.xml.dist
```

Tests live outside the web root and use controlled FPM/package responses. They
do not install development packages into the bundled production vendor tree.

The Debian package build uses `nocheck`; it does not run the full historical
upstream suite. A separate ARM64 run of 3,009 Zend/session tests produced 2,927
passes, 71 skips, eight expected failures, and the same three failures as the
unmodified Focal runtime: `bug74093`, `bug74514`, and `bug74936`. No new failure
was observed in that comparison; it is not a clean full-suite result.


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
 
