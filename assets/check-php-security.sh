#!/usr/bin/env bash
set -euo pipefail

fail() {
    printf 'PHP2Shell security check failed: %s\n' "$*" >&2
    exit 1
}

if [ "$#" -ne 1 ]; then
    fail "Usage: $0 <PHP branch>"
fi

branch=$1
# PHP 7.4 requires a reviewed distribution backport below.
case "$branch" in
    7.4) minimum=7.4.3 ;;
    8.3) minimum=8.3.36 ;;
    8.4) minimum=8.4.27 ;;
    8.5) minimum=8.5.12 ;;
    *) fail "Unsupported PHP branch '$branch'; use a reviewed PHP 7.4 backport or patched PHP 8.3, 8.4, or 8.5." ;;
esac

if ! output=$("php-fpm${branch}" --version 2>&1); then
    fail "Cannot inspect php-fpm${branch}: $output"
fi
read -r name version sapi rest <<< "$output"
binary_version=$version
if [[ "$branch" == 7.4 && "$version" =~ ^7\.4\.3-4ubuntu2\.[0-9]+(\+[^[:space:]]+)?$ ]]; then
    version=7.4.3
fi
if [[ "$name" != PHP || "$sapi" != "(fpm-fcgi)" ||
    ! "$version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ || "$version" != "$branch".* ]]; then
    fail "Expected a stable PHP ${branch} FPM binary; received: $output"
fi
if ! dpkg --compare-versions "$version" ge "$minimum"; then
    fail "PHP-FPM $version is below $minimum. Rebuild the image with patched PHP packages."
fi

fpm_package_version=
for package in fpm common; do
    if ! output=$(dpkg-query --show --showformat='${db:Status-Status} ${Version}' "php${branch}-${package}" 2>&1); then
        fail "Cannot inspect php${branch}-${package}: $output"
    fi
    read -r status package_version rest <<< "$output"
    if [[ "$status" != installed || -n "$rest" ]]; then
        fail "php${branch}-${package} is not fully installed: $output"
    fi
    if [[ ! "$package_version" =~ ^([0-9]+:)?([0-9]+\.[0-9]+\.[0-9]+)([-+][^[:space:]]+)?$ ]]; then
        fail "Invalid stable package version for php${branch}-${package}: $package_version"
    fi
    # Strip Debian epochs/revisions before comparing the upstream runtime version.
    if [[ "${BASH_REMATCH[2]}" != "$version" ]]; then
        fail "php${branch}-${package} $package_version does not match FPM $version."
    fi
    if [[ "$binary_version" != "$version" && "$binary_version" != "$package_version" ]]; then
        fail "php${branch}-${package} $package_version does not match FPM $binary_version."
    fi
    if [[ "$branch" == 7.4 ]]; then
        if [[ "$package_version" =~ ^7\.4\.3-4ubuntu2\.[0-9]+\+esm[0-9]+$ ]]; then
            if ! dpkg --compare-versions "$package_version" ge '7.4.3-4ubuntu2.29+esm5'; then
                fail "php${branch}-${package} $package_version is below the reviewed ESM revision 7.4.3-4ubuntu2.29+esm5."
            fi
        elif [[ "$package_version" =~ ^1:7\.4\.33-[0-9]+\+ubuntu(22\.04|24\.04)\.[0-9]+\+deb\.sury\.org\+[0-9]+$ ]]; then
            if ! dpkg --compare-versions "$package_version" ge '1:7.4.33-31'; then
                fail "php${branch}-${package} $package_version is below the reviewed backport revision 1:7.4.33-31."
            fi
        elif [[ "$package_version" != '7.4.3-4ubuntu2.29+duckietown1.php2shell.712e60f9' ]]; then
            fail "Unreviewed PHP 7.4 package: $package_version. Use a reviewed distribution backport or the pinned Duckietown source build."
        fi
    fi
    if [[ "$package" == fpm ]]; then
        fpm_package_version=$package_version
    elif [[ "$package_version" != "$fpm_package_version" ]]; then
        fail "FPM and common package revisions do not match: $fpm_package_version / $package_version"
    fi
done

printf 'PHP2Shell security check passed: PHP-FPM %s (%s).\n' "$version" "$fpm_package_version"
