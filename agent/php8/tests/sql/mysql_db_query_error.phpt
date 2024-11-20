--TEST--
hook mysql_db_query error
--SKIPIF--
<?php
if (PHP_MAJOR_VERSION >= 7) die('Skipped: no mysql extension in PHP7.');
$plugin = <<<EOF
RASP.algorithmConfig = {
    sql_exception: {
        name:      '算法3 - 记录数据库异常',
        action:    'log',
        reference: 'https://rasp.baidu.com/doc/dev/official.html#sql-exception',
        mysql: {
            error_code: [
                1045, // Access denied for user 'bae'@'10.10.1.1'
                1060, // Duplicate column name '5.5.60-0ubuntu0.14.04.1'
                1064, // You have an error in your SQL syntax
                1105, // XPATH syntax error: '~root@localhost~'
                1367, // Illegal non geometric 'user()' value found during parsing
                1690  // DOUBLE value is out of range in 'exp(~((select 'root@localhost' from dual)))'
            ]
        }
    }
}
plugin.register('sql_exception', params => {
    assert(params.server == 'mysql')
    assert(params.query == 'select exp(~(select*from(select user())x))')
    assert(params.error_code == '1690')
    return block
})
EOF;
$conf = <<<CONF
security.enforce_policy: false
CONF;
include(__DIR__.'/../skipif.inc');
if (!extension_loaded("mysql")) die("Skipped: mysql extension required.");
@$con = mysql_connect('127.0.0.1', 'root', 'rasp#2019');
if (!$con) die("Skipped: can not connect to MySQL " . mysql_error());
mysql_close($con);
?>
--INI--
openrasp.root_dir=/tmp/openrasp
--FILE--
<?php
@$con = mysql_connect('127.0.0.1', 'root', 'rasp#2019');
@mysql_db_query("information_schema", "select exp(~(select*from(select user())x))");
mysql_close($con);
?>
--EXPECTREGEX--
<\/script><script>location.href="http[s]?:\/\/.*?request_id=[0-9a-f]{32}"<\/script>