#!/usr/bin/env bash


if [[ "$1" == "--help" ]] ; then
    echo -e "usage: test.sh [--phpunit|--behat]"
    echo -e "  if --phpunit provided, execute the test of phpUnit only"
    echo -e "  if --behat provided, execute the test of behat only"
    exit 1
fi

start=`date +%s`
resultPhpUnit=0
resultBehat=0

echo "run server symfony"
php app/console server:start --force 127.0.0.1:8000 &

echo -e "--------------------------";
echo -e " load  fixtures";
php app/console doctrine:fixtures:load --append --fixtures=tests/LookBookBundle/DataFixtures/ORM

echo -e "--------------------------";
    if [[ "$1" == "--phpunit" ]]||[[ $# -eq 0 ]] ; then
    bin/phpunit -c .
    resultPhpUnit=${PIPESTATUS[0]}
else
    echo -e "phpunit test bypassed ";
fi

echo -e "--------------------------";

if [[ "$1" == "--behat" ]]||[[ $# -eq 0 ]] ; then
    if [[ "$2" != null ]]; then
        bin/behat "$2"
    else
        bin/behat
    fi
    resultBehat=${PIPESTATUS[0]}
else
    echo -e "behat test bypassed ";
fi

echo -e "--------------------------";

end=`date +%s`
diff=`expr $end - $start`
echo -e "Elapsed time = $diff seconds"

echo "stop symfony server"
php app/console server:stop 127.0.0.1:8000

if [[ "$resultPhpUnit" == 1 ]]||[[ "$resultBehat" == 1 ]] ; then
    echo -e "Ko";
    exit 1
fi

echo -e "Done";
exit