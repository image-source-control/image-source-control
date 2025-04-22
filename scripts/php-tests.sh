#!/usr/bin/env bash
# scripts/php-matrix.sh
# Iterate through PHP versions and run tests (fail‑fast)

set -euo pipefail

suite="$1"; shift                       # wpunit / functional / acceptance …
args=("$@")                              # remaining CLI tokens (optional)

# Adjust to the PHP builds you actually have in MAMP PRO
PHP_VERSIONS=( 7.4.33 8.1.31 8.2.26 8.3.14 8.4.1 )
MAMP_ROOT="/Applications/MAMP/bin/php"

##############################################################################
# Helper: resolve a user‑supplied token ("8.1", "8.1.31") to a full version #
##############################################################################
resolve_version() {
  local token="$1"
  # full patch level given? → return if it exists
  if [[ "$token" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    for v in "${PHP_VERSIONS[@]}"; do
      [[ "$v" == "$token" ]] && { echo "$v"; return 0; }
    done
  fi
  # minor given ("X.Y")? → return first matching entry
  if [[ "$token" =~ ^[0-9]+\.[0-9]+$ ]]; then
    for v in "${PHP_VERSIONS[@]}"; do
      [[ "$v" == "$token"* ]] && { echo "$v"; return 0; }
    done
  fi
  echo "⚠️  Unknown PHP version token: $token" >&2
  exit 1
}

#################################
# Build list of versions to run #
#################################
VERSIONS_TO_RUN=()

if ((${#args[@]})); then
  # explicit tokens provided
  if [[ "${args[*]}" =~ phpall ]]; then
    VERSIONS_TO_RUN=("${PHP_VERSIONS[@]}")
  else
    for tok in "${args[@]}"; do
      VERSIONS_TO_RUN+=("$(resolve_version "$tok")")
    done
  fi
else
  # no token → use current CLI interpreter once
  VERSIONS_TO_RUN=()
fi

###################
# Run the suites  #
###################
run_suite () {
  local bin="$1"
  local version="$2"

  printf "\n▶︎  %s  •  PHP %s\n" "$suite" "$version"

  # ❶ Erwartete Version für die Tests bereitstellen
  export ISC_EXPECT_PHP_VERSION="$version"

  # ❷ Tests ausführen (‑‑debug optional)
  "$bin" vendor/bin/codecept run "$suite"

  # ❸ Aufräumen, damit die Variable nicht zur nächsten Version „durchblutet“
  unset ISC_EXPECT_PHP_VERSION
}

if ((${#VERSIONS_TO_RUN[@]})); then
  for v in "${VERSIONS_TO_RUN[@]}"; do
    bin="${MAMP_ROOT}/php${v}/bin/php"
    run_suite "$bin" "$v"
  done
else
  # Single run with the PHP that’s currently on $PATH / alias from MAMP
  current_php="${PHP_BINARY:-php}"
  current_version="$($current_php -r 'echo PHP_VERSION;')"
  run_suite "$current_php" "$current_version"
fi
