#!/bin/bash
SCRIPT_DIR=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
BASEDIR=$(dirname "$(dirname $SCRIPT_DIR)")
SAILCMD=vendor/bin/sail

echo "Moving to $BASEDIR"
cd "$BASEDIR" || exit 1

printResourceRoute() {
  MODELNAME=${1}
  echo "Route::apiResource('${MODELNAME,,}', \\App\\Http\\Controllers\\${MODELNAME}Controller::class);"
}

createControllerAndResource() {
  MODELNAME=${1}
  echo Creating controller and resource for ${MODELNAME}
  $SAILCMD artisan make:controller --api -R --test --force -r -m ${MODELNAME} "${MODELNAME}Controller";
  $SAILCMD artisan make:resource "${MODELNAME}Resource"
}

makeApiResourcesArray() {
    MODELNAME=${1}
    echo "
            '${MODELNAME,,}' => [
                'model' => \\App\\Models\\${MODELNAME}::class,
                'resource' => \\App\\Http\\Resources\\${MODELNAME}Resource::class,
                'controller' => \\App\\Http\\Controllers\\${MODELNAME}Controller::class
            ],"
}

updateControllerFeatureTest(){
    MODELNAME=${1}
    FEAT_TESTS_DIR="${BASEDIR}/tests/Feature/Http/Controllers"
    TESTFILE="${FEAT_TESTS_DIR}/${MODELNAME}ControllerTest.php"
    if [ -f "${TESTFILE}" ]; then
        sed -i \
            -e "s/use Tests\\\TestCase;/use Tests\\\Feature\\\Http\\\ResourceFeaturesTestCase;/g" \
            -e "s/extends TestCase/extends ResourceFeaturesTestCase/g" \
            -e "s/\/\/route/    protected \$resourceRoute = '${MODELNAME,,}';/g" \
            ${TESTFILE}
    fi
    # replace "use Tests\TestCase;" with "use Tests\Feature\Http\ResourceFeaturesTestCase;"
    # replace "extends TestCase" with "extends ResourceFeaturesTestCase"
    # add
}

factories() {
    MODELNAME=${1}
    $SAILCMD artisan make:factory -m "${MODELNAME}" "${MODELNAME}Factory"
}

createFilter() {
    MODELNAME=${1}
    $SAILCMD artisan model:filter "${MODELNAME}"
}

makeUnitTest() {
    MODELNAME=${1}
    $SAILCMD artisan make:test --unit "${MODELNAME}Test"
}

case $1 in
    routes)
      FN=printResourceRoute
      ;;
    create)
      FN=createControllerAndResource
      ;;
    api-resources)
      FN=makeApiResourcesArray
      ;;
    feature-tests)
      FN=updateControllerFeatureTest
      ;;
    factories)
      FN=factories
      ;;
    filters)
        FN=createFilter
        ;;
    unit-tests)
        FN=makeUnitTest
        ;;
esac


for modfile in $(ls "${BASEDIR}/app/Models/"); do
  MODELNAME=${modfile%.php}
  $FN "${MODELNAME}"
done
