<?php

$repo = "/Users/anatawa12/IdeaProjects/files.anatawa12.com";
// php source root
$src = "$repo/php";
// resources root
$res = "$repo/src";
// destination root
$dst = "$repo/dst";

function run_php(string $php, array $args, string $output) {
    global $res;
    $descriptorspec = [
        ["file", "/dev/null", "r"],
        ["file", $output, "w"],
        STDERR,
    ];
    $pipes = [];
    $proc = proc_open(
        command: ["php", $php, ...$args],
        descriptor_spec: $descriptorspec,
        pipes: $pipes,
        cwd: "$res"
    );
    if ($proc === false) throw new Exception("run index.php failed");
    $exit = proc_close($proc);
    if ($exit !== 0) throw new Exception("run index.php failed: exit: $exit");
}

function build_index(string $dir) {
    global $src, $dst, $res;
    if (!is_dir("$res/$dir"))
        throw new Exception("not a dir: $dir");

    @mkdir(directory: "$dst/$dir", recursive: true);

    run_php("$src/index.php", [$dir], "$dst/$dir/index.html");
}

function make_iterator(): RecursiveIteratorIterator {
    global $res;
    $iterator = new RecursiveDirectoryIterator($res,FilesystemIterator::SKIP_DOTS);
    return new RecursiveIteratorIterator($iterator,RecursiveIteratorIterator::SELF_FIRST);
}

$ignored = [
    ".htaccess",
    "info.yml",
];

@mkdir(directory: "$dst", recursive: true);
run_php("$src/404.php", [], "$dst/404.html");

build_index("");

/** @var SplFileInfo $file_info */
foreach (make_iterator() as $key => $file_info) {
    $relative = substr($key, strlen($res) + 1);
    if (in_array(basename($relative), $ignored))
        continue;
    echo $relative . PHP_EOL;
    if ($file_info->isDir()) {
        build_index($relative);
    } else if ($file_info->isFile()) {
        copy("$res/$relative", "$dst/$relative");
    }
}

