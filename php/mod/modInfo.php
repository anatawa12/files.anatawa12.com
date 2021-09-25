<?php
require_once __DIR__.'/vendor/autoload.php';
require_once dirname(__FILE__)."/spyc.php";
$mods = Spyc::YAMLLoad(file_get_contents($argv[1]."/mods.yml"));
$parser = new \cebe\markdown\GithubMarkdown();
foreach ($mods as &$mod) {
    $mod["info"] = $parser->parse($mod["info"] ?? "");
}
unset($mod);

function body() {
    global $mods;
    ?>
    <template id="not-found-meta">
        <meta name="robots" content="noindex"/>
    </template>
    <template id="not-found-body">
        <h1>Not Found</h1>
    </template>
    <template id="success-body">
        <h1><!-- cur_mod.name --></h1>
        <div class="mods-info"></div>

        <div class="mod_downloads">
            <h2>versions and downloads</h2>
            <table>
                <tr>
                    <th>Minecraft version</th>
                    <th>modVersion</th>
                    <th>deponds</th>
                    <th>download</th>
                </tr>
                <!-- datas -->
            </table>
        </div>
    </template>

    <script>
        function not_found() {
            document.title = "Not Found";
            document.head.appendChild(document.querySelector("#not-found-meta").content.cloneNode(true));
            return document.querySelector("#not-found-body").content.cloneNode(true);
        }
        function new_td(body) {
            const td = document.createElement("td");
            td.innerHTML = body
            return td;
        }
        function main() {
            /** @type (Array<{dir: string, name: string, jarNameTemplate: string, info: string, mcAndModVersions: {[P in string]: {var: string, depends: string[]}[]}}>) */
            const data = JSON.parse(<?php echo json_encode(json_encode($mods)) ?>);
            const params = new URLSearchParams(window.location.search);
            const mod_name = params.get("name");
            if (mod_name == null) return not_found();
            const cur_mod = data.find(v => v.dir === mod_name);
            if (cur_mod === undefined) return not_found;
            const jarNameTemplate = cur_mod.jarNameTemplate;

            // body creation
            document.title = cur_mod.name;
            /** @type DocumentFragment */
            const body = document.querySelector("#success-body").content.cloneNode(true);
            body.querySelector("h1").textContent = cur_mod.name;
            body.querySelector(".mods-info").innerHTML = `<h2>mod info</h2>${cur_mod.info}`;
            const table = body.querySelector("table");
            for (let [mcVersion, modVersions] of Object.entries(cur_mod.mcAndModVersions)) {
                for (let modVersionInfo of modVersions) {
                    const tr = document.createElement("tr");
                    tr.appendChild(new_td(mcVersion));
                    tr.appendChild(new_td(modVersionInfo.var));
                    tr.appendChild(new_td(modVersionInfo.depends.join("<br>")));
                    const jarName = jarNameTemplate.replace("${version}", modVersionInfo.var);
                    tr.appendChild(new_td(`<a href='mods/${cur_mod.dir}/${jarName}'>${jarName}</a>`));
                    table.appendChild(tr);
                }
            }
            return body;
        }
        document.currentScript.parentNode.appendChild(main());
    </script>
    <?php
}

$title = "{name}";
include dirname(__FILE__)."/private/base.php";

?>
