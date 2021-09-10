<?php
use think\facade\Db;

require __DIR__.'/../bootstrap.php';
global $config;

function getMysqlDatabaseStruct($database, $table = '') {
    $sql = "select * from information_schema.TABLES where TABLE_SCHEMA='{$database}';";
    $res = Db::query($sql);
    if (!$res) {
        echo "获取失败".Db::getError();exit;
    }
    $sql = "select * from information_schema.COLUMNS where TABLE_SCHEMA='{$database}';";
    $res2 = Db::query($sql);
    $columns = [];
    foreach ($res2 as $v) {
        $columns[$v['TABLE_NAME']][] = [
            'column_name' => $v['COLUMN_NAME'],
            'column_default' => $v['COLUMN_DEFAULT'],
            'is_nullable' => $v['IS_NULLABLE'],
            'column_type' => $v['COLUMN_TYPE'],
            'column_key' => $v['COLUMN_KEY'],
            'extra' => $v['EXTRA'],
            'column_comment' => $v['COLUMN_COMMENT'],
        ];
    }
    $data = [];
    foreach ($res as $value) {
        $data[] = [
            'table_name'=>$value['TABLE_NAME'],
            'table_type' => $value['TABLE_TYPE'],
            'engine' => $value['ENGINE'],
            'table_collation' => $value['TABLE_COLLATION'],
            'table_comment' => $value['TABLE_COMMENT'] == 'VIEW' ? '' : $value['TABLE_COMMENT'],
            'columns' => $columns[$value['TABLE_NAME']],
        ];
    }

    return $data;
}

function structureToMarkdown($structure) {
    $md = '';
    foreach ($structure as $item) {
        $md .= sprintf("### %s %s\n", $item['table_name'], $item['table_comment']);
        $md .= sprintf("> 表类型：%s  表引擎：%s  表字符集: %s\n\n", $item['table_type'], $item['engine'], $item['table_collation']);
        $md .= "| 字段 | 类型 | 是否为空 | 默认值 | 扩展信息 | 字段描述 |\n";
        $md .= "| ---- | ---- | ---- | ---- | ---- | ---- |\n";
        foreach ($item['columns'] as $v) {
            $def = $v['column_default'] === null ? 'NULL' : $v['column_default'];
            $md .= "| {$v['column_name']} | {$v['column_type']} | {$v['is_nullable']} | {$def} | {$v['extra']} {$v['column_key']} | {$v['column_comment']} |\n";
        }
        $md .= "\n\n";
    }

    return trim($md);
}

function structureToHtml($structure) {
    $md = structureToMarkdown($structure);
    $parse = new Parsedown();
    $parse->setMarkupEscaped(true);

    return $parse->text($md);
}

$structure = getMysqlDatabaseStruct($config['connections']['mysql']['database']);

$type = 'markdown';
if (in_array('--type=html', $argv)) {
    $type = 'html';
}
switch ($type) {
    case 'html':
        $res = structureToHtml($structure);
        $ext = '.html';
        break;
    default:
        $res = structureToMarkdown($structure);
        $ext = '.md';
}

if (in_array('--print', $argv)) {
    echo $res;
} else {
    $file = 'runtime/output_data_structure_'.date('Ymd').$ext;
    file_put_contents(__DIR__.'/../'.$file, $res);
    echo "生成文件：".$file;
}