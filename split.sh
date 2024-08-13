#!/bin/bash

BLEEDING=0
MODULE=

while getopts ":bm:" option; do
  case $option in
    b) BLEEDING=1 ;;
    m) MODULE=${OPTARG}
  esac
done
shift $((OPTIND -1))

TAG=$(git describe --tags --abbrev=0)
LOCAL_ACTIVE_BRANCH=$(git rev-parse --abbrev-ref HEAD)

echo "tag: $TAG"
echo "branch: $LOCAL_ACTIVE_BRANCH"
if [[ "${BLEEDING}" -eq 1 ]]; then
  echo "mode: bleeding (pushing only master)"
else
  echo "mode: default (pushing master and tag)"
fi

declare -A modules_paths
modules_paths=(
  [remp-commons]="Composer/remp-commons"
  [laravel-helpers]="Composer/laravel-helpers"
  [laravel-sso]="Composer/laravel-sso"
  [laravel-widgets]="Composer/laravel-widgets"
  [js-commons]="Package/remp/"
  [mailer-module]="Mailer/extensions/mailer-module"
  [newrelic-module]="Mailer/extensions/newrelic-module"
  [beam-module]="Beam/extensions/beam-module"
  [campaign-module]="Campaign/extensions/campaign-module"
)

if [ -z $MODULE ]; then
  modules=(
    remp-commons
    laravel-helpers
    laravel-sso
    laravel-widgets
    js-commons
    mailer-module
    newrelic-module
    beam-module
    campaign-module
  )
else
  if ! [[ -v "modules_paths[${MODULE}]" ]]; then
    echo "ERROR: module ${MODULE} does not exist, quitting"
    exit 1
  fi
  modules=(
    ${MODULE}
  )
fi

for module in "${modules[@]}"
do
  echo "publishing $module"

  # extract module to branch
  splitsh-lite --prefix "${modules_paths[$module]}" --progress --target refs/heads/"$module"

  # push extracted module
  git push -f "$module" "$module":master

  if [[ "${BLEEDING}" -eq 0 ]]; then
    # tag the extracted module with same tag as was found on the local branch
    git tag -f "$TAG" "$module"

    # push extracted tag
    git push -f "$module" "$TAG"

    # force tag back to master
    git tag -f "$TAG" "$LOCAL_ACTIVE_BRANCH"
  fi

done
