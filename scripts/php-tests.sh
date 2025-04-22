#!/usr/bin/env bash
set -euo pipefail

suite="$1"; shift           # wpunit / functional / acceptance …
raw_args=("$@")              # everything after the suite

PHP_VERSIONS=( 7.4.33 8.1.31 8.2.26 8.3.14 8.4.1 )
MAMP_ROOT="/Applications/MAMP/bin/php"

########################################
# Split raw_args into two collections  #
#   1) version tokens                  #
#   2) codeception arguments           #
########################################
VERSIONS_TO_RUN=()
CODECEPT_ARGS=()

is_version_token() {
  [[ "$1" == "phpall" ]] \
  || [[ "$1" =~ ^[0-9]+\.[0-9]+(\.[0-9]+)?$ ]]
}

for tok in "${raw_args[@]}"; do
  if is_version_token "$tok"; then
    if [[ "$tok" == "phpall" ]]; then
      VERSIONS_TO_RUN=("${PHP_VERSIONS[@]}")
    else
      VERSIONS_TO_RUN+=("$tok")
    fi
  else
    CODECEPT_ARGS+=("$tok")
  fi
done

# If no version token was supplied → run once with current CLI PHP
if ((${#VERSIONS_TO_RUN[@]} == 0)); then
  VERSIONS_TO_RUN=()
fi

########################################
# Helper: resolve minor to full patch  #
########################################
resolve_version() {
  local token="$1"
  [[ "$token" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] && { echo "$token"; return 0; }
  for v in "${PHP_VERSIONS[@]}"; do
    [[ "$v" == "$token"* ]] && { echo "$v"; return 0; }
  done
  echo "⚠️  Unknown PHP version token: $token" >&2
  exit 1
}

# Expand minors (8.1 → 8.1.31)
for i in "${!VERSIONS_TO_RUN[@]}"; do
  VERSIONS_TO_RUN[$i]="$(resolve_version "${VERSIONS_TO_RUN[$i]}")"
done

###################
# Run the suites  #
###################
run_suite () {
  local bin="$1"
  local version="$2"

  printf "\n▶︎  %s  •  PHP %s\n" "$suite" "$version"
  export ISC_EXPECT_PHP_VERSION="$version"

  "$bin" vendor/bin/codecept run "$suite" "${CODECEPT_ARGS[@]}"

  unset ISC_EXPECT_PHP_VERSION
}

if ((${#VERSIONS_TO_RUN[@]})); then
  for v in "${VERSIONS_TO_RUN[@]}"; do
    bin="${MAMP_ROOT}/php${v}/bin/php"
    run_suite "$bin" "$v"
  done
else
  current_php="${PHP_BINARY:-php}"
  current_version="$($current_php -r 'echo PHP_VERSION;')"
  run_suite "$current_php" "$current_version"
fi
