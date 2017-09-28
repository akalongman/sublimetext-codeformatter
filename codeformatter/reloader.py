# @author           Avtandil Kikabidze
# @contributors Avtandil Kikabidze, Nishutosh Sharma
# @copyright        Copyright (c) 2008-2015, Avtandil Kikabidze aka LONGMAN (akalongman@gmail.com)
# @link 			http://longman.me
# @license 		The MIT License (MIT)

import sublime
import sys

st_version = 2
if sublime.version() == '' or int(sublime.version()) > 3000:
    st_version = 3
    from imp import reload

reload_mods = []
for mod in sys.modules:
    if mod[0:13].lower() == 'codeformatter' and sys.modules[mod] is not None:
        reload_mods.append(mod)


mod_prefix = 'codeformatter'
if st_version == 3:
    mod_prefix = 'CodeFormatter.' + mod_prefix

mods_load_order = [
    '',

    '.phpformatter',
    '.jsformatter',
    '.htmlformatter',
    '.cssformatter',
    '.scssformatter',
    '.pyformatter',
    '.vbscriptformatter',
    '.formatter'
]

for suffix in mods_load_order:
    mod = mod_prefix + suffix
    if mod in reload_mods:
        reload(sys.modules[mod])
