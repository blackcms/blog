let mix = require("laravel-mix");

const path = require("path");
let directory = path.basename(path.resolve(__dirname));

const source = "addons/" + directory;
const dist = "public/vendor/core/addons/" + directory;

mix.js(source + "/resources/assets/js/blog.js", dist + "/js").copyDirectory(
    dist + "/js",
    source + "/public/js"
);
