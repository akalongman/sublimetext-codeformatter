import os

import pytest
from unittest.mock import patch


@pytest.fixture(autouse=True)
def clear_caches():
    from codeformatter import phpfixer
    phpfixer._version_cache.clear()
    phpfixer._coverage_cache.clear()
    yield
    phpfixer._version_cache.clear()
    phpfixer._coverage_cache.clear()


def fake_run(*results):
    """Replace process.run; each result is (returncode, stdout, stderr), or
    an exception to raise."""
    return patch('codeformatter.phpfixer.process.run',
                 side_effect=list(results))


def touch(path):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text('')
    return path


def find_config(file_name, forced=None):
    from codeformatter import phpfixer
    return phpfixer.find_config(
        None if file_name is None else str(file_name), forced)


def find_executable(fixer, config_dir, fixer_path='', php_path='',
                    platform='linux'):
    from codeformatter import phpfixer
    return phpfixer.find_executable(
        fixer, None if config_dir is None else str(config_dir),
        fixer_path, php_path, platform)


def through_php(script, php='php'):
    """The command line that runs a PHP script with notices on stderr."""
    return [php, '-d', 'display_errors=stderr', str(script)]


@pytest.mark.parametrize('fixer,name', [
    ('pint', 'pint.json'),
    ('php-cs-fixer', '.php-cs-fixer.php'),
    ('php-cs-fixer', '.php-cs-fixer.dist.php'),
    ('phpcbf', 'phpcs.xml'),
    ('phpcbf', '.phpcs.xml'),
    ('phpcbf', 'phpcs.xml.dist'),
    ('phpcbf', '.phpcs.xml.dist'),
])
def test_config_file_names(tmp_path, fixer, name):
    proj = tmp_path / 'proj'
    touch(proj / name)
    php = touch(proj / 'src' / 'a.php')

    assert find_config(php) == (fixer, str(proj), str(proj / name))


def test_nearest_config_wins(tmp_path):
    proj = tmp_path / 'proj'
    touch(proj / 'phpcs.xml')
    touch(proj / 'sub' / 'pint.json')
    php = touch(proj / 'sub' / 'x' / 'a.php')

    assert find_config(php) == (
        'pint', str(proj / 'sub'), str(proj / 'sub' / 'pint.json'))


def test_tie_order(tmp_path):
    proj = tmp_path / 'proj'
    pint_json = touch(proj / 'pint.json')
    touch(proj / '.php-cs-fixer.dist.php')
    touch(proj / 'phpcs.xml')
    php = touch(proj / 'src' / 'a.php')

    assert find_config(php) == ('pint', str(proj), str(pint_json))

    pint_json.unlink()

    assert find_config(php) == (
        'php-cs-fixer', str(proj), str(proj / '.php-cs-fixer.dist.php'))


def test_pint_fallback(tmp_path):
    proj = tmp_path / 'proj'
    touch(proj / 'composer.json')
    touch(proj / 'vendor' / 'bin' / 'pint')
    php = touch(proj / 'src' / 'a.php')

    assert find_config(php) == ('pint', str(proj), None)


def test_pint_fallback_uses_nearest_composer_dir(tmp_path):
    parent = tmp_path / 'parent'
    touch(parent / 'composer.json')
    touch(parent / 'vendor' / 'bin' / 'pint')
    touch(parent / 'proj' / 'composer.json')
    php = touch(parent / 'proj' / 'src' / 'a.php')

    assert find_config(php) is None


def test_no_config(tmp_path):
    php = touch(tmp_path / 'proj' / 'src' / 'a.php')

    assert find_config(php) is None


def test_forced_uses_its_own_config(tmp_path):
    parent = tmp_path / 'parent'
    touch(parent / 'phpcs.xml')
    touch(parent / 'proj' / 'pint.json')
    php = touch(parent / 'proj' / 'src' / 'a.php')

    assert find_config(php, 'phpcbf') == (
        'phpcbf', str(parent), str(parent / 'phpcs.xml'))


def test_forced_without_config(tmp_path):
    proj = tmp_path / 'proj'
    composer_json = touch(proj / 'composer.json')
    php = touch(proj / 'src' / 'a.php')

    assert find_config(php, 'pint') == ('pint', str(proj), None)

    composer_json.unlink()

    assert find_config(php, 'pint') == ('pint', str(proj / 'src'), None)


def test_unsaved_buffer():
    assert find_config(None) is None
    assert find_config(None, 'pint') == ('pint', None, None)


def test_executable_fixer_path(tmp_path, monkeypatch):
    monkeypatch.setenv('HOME', '/home/u')
    monkeypatch.setenv('USERPROFILE', '/home/u')
    proj = tmp_path / 'proj'
    touch(proj / 'vendor' / 'bin' / 'pint')

    assert find_executable('pint', proj, fixer_path='~/bin/pint') == [
        '/home/u/bin/pint']


def test_executable_vendor_bin(tmp_path):
    proj = tmp_path / 'proj'
    script = str(touch(proj / 'vendor' / 'bin' / 'phpcbf'))

    assert find_executable('phpcbf', proj, php_path='') == through_php(script)
    assert find_executable('phpcbf', proj, php_path='/opt/php') == (
        through_php(script, '/opt/php'))


def test_executable_vendor_bin_above_config(tmp_path):
    root = tmp_path / 'root'
    touch(root / 'vendor' / 'bin' / 'phpcbf')
    config_dir = root / 'packages' / 'a'
    config_dir.mkdir(parents=True)

    assert find_executable('phpcbf', config_dir) == through_php(
        root / 'vendor' / 'bin' / 'phpcbf')


def test_executable_path_lookup(tmp_path):
    proj = tmp_path / 'proj'
    proj.mkdir()

    with patch('codeformatter.phpfixer.shutil.which',
               return_value='/usr/local/bin/pint') as which:
        assert find_executable('pint', proj) == ['/usr/local/bin/pint']
        # A never-saved buffer has no configuration directory to walk from.
        assert find_executable('pint', None) == ['/usr/local/bin/pint']

    assert which.call_args_list == [(('pint',),), (('pint',),)]


def test_executable_missing(tmp_path):
    proj = tmp_path / 'proj'
    proj.mkdir()

    with patch('codeformatter.phpfixer.shutil.which', return_value=None):
        assert find_executable('php-cs-fixer', proj) is None
        assert find_executable('php-cs-fixer', None) is None


PHP_PROXY = '<?php\n\ninclude __DIR__ . "/../laravel/pint/builds/pint";\n'
SHEBANG_PROXY = '#!/usr/bin/env php\n<?php\n\ninclude __DIR__ . "/x";\n'
SHELL_PROXY = ('#!/usr/bin/env sh\n\ndir=$(cd "${0%[/\\\\]*}" > /dev/null; '
               'cd "../laravel/pint/builds" && pwd)\n"${dir}/pint" "$@"\n')


def batch_proxy(tmp_path, name='pint.bat', sibling=None):
    """A global bin directory with a batch proxy and, when sibling is
    given, the extension-less file next to it."""
    bin_dir = tmp_path / 'Composer' / 'vendor' / 'bin'
    batch = touch(bin_dir / name)
    if sibling is not None:
        (bin_dir / name[:-4]).write_text(sibling)
    return str(batch), str(bin_dir / name[:-4])


@pytest.mark.parametrize('name', ['pint.bat', 'pint.BAT', 'pint.cmd'])
@pytest.mark.parametrize('sibling', [PHP_PROXY, SHEBANG_PROXY,
                                     '#!/usr/bin/php\n<?php\n'],
                         ids=['php-tag', 'env-php', 'php-shebang'])
def test_executable_batch_on_path_runs_through_php(tmp_path, name, sibling):
    batch, script = batch_proxy(tmp_path, name, sibling)

    with patch('codeformatter.phpfixer.shutil.which', return_value=batch):
        assert find_executable('pint', None, php_path='/opt/php',
                               platform='windows') == (
            through_php(script, '/opt/php'))


@pytest.mark.parametrize('sibling', [PHP_PROXY, SHEBANG_PROXY],
                         ids=['php-tag', 'env-php'])
def test_executable_batch_fixer_path_runs_through_php(tmp_path, sibling):
    batch, script = batch_proxy(tmp_path, 'pint.bat', sibling)

    assert find_executable('pint', None, fixer_path=batch,
                           platform='windows') == through_php(script)


@pytest.mark.parametrize('sibling', [
    SHELL_PROXY,
    '#!/bin/bash\nexec php "$0.phar" "$@"\n',
    '@ECHO OFF\r\n',
    '',
], ids=['sh-proxy', 'bash', 'not-a-script', 'empty'])
def test_executable_batch_sibling_not_php(tmp_path, sibling):
    batch, script = batch_proxy(tmp_path, 'pint.bat', sibling)

    with patch('codeformatter.phpfixer.shutil.which', return_value=batch):
        assert find_executable('pint', None, platform='windows') == [batch]
    assert find_executable('pint', None, fixer_path=batch,
                           platform='windows') == [batch]


def test_executable_batch_sibling_unreadable(tmp_path):
    batch, script = batch_proxy(tmp_path, 'pint.bat', PHP_PROXY)

    with patch('codeformatter.phpfixer.open', create=True,
               side_effect=OSError(13, 'Permission denied')):
        assert find_executable('pint', None, fixer_path=batch,
                               platform='windows') == [batch]


def test_executable_batch_without_sibling(tmp_path):
    batch, script = batch_proxy(tmp_path, 'pint.bat')

    with patch('codeformatter.phpfixer.shutil.which', return_value=batch):
        assert find_executable('pint', None, platform='windows') == [batch]


@pytest.mark.parametrize('platform', ['linux', 'osx'])
def test_executable_batch_off_windows_runs_as_given(tmp_path, platform):
    batch, script = batch_proxy(tmp_path, 'pint.bat', PHP_PROXY)

    with patch('codeformatter.phpfixer.shutil.which', return_value=batch):
        assert find_executable('pint', None, platform=platform) == [batch]
    assert find_executable('pint', None, fixer_path=batch,
                           platform=platform) == [batch]


def misread(argv, paths, platform='windows'):
    from codeformatter import phpfixer
    return phpfixer.cmd_exe_would_misread(argv, paths, platform)


@pytest.mark.parametrize('char', list('&|<>^%!'))
def test_cmd_exe_misreads_special_characters(char):
    path = 'C:\\proj\\a' + char + 'b.php'

    assert misread(['C:\\g\\pint.bat'], [None, path]) is True
    assert misread(['C:\\g\\pint.CMD'], [path, None]) is True
    # The batch file's own path counts too.
    assert misread(['C:\\R' + char + 'D\\pint.bat'], [None, None]) is True


def test_cmd_exe_clean_paths():
    assert misread(['C:\\g\\pint.bat'],
                   ['C:\\my proj\\pint.json',
                    'C:\\my proj\\src\\módulo.php']) is False


@pytest.mark.parametrize('argv,platform', [
    # No batch file, so cmd.exe never reads the command line.
    (['C:\\php\\php.exe', '-d', 'display_errors=stderr',
      'C:\\g\\pint'], 'windows'),
    (['C:\\g\\pint.exe'], 'windows'),
    (['/usr/local/bin/pint.bat'], 'linux'),
    (['/usr/local/bin/pint.bat'], 'osx'),
], ids=['php', 'exe', 'linux', 'osx'])
def test_cmd_exe_not_involved(argv, platform):
    assert misread(argv, ['C:\\R&D\\a.php'], platform) is False


def tool_version(tool, argv, cwd='/proj'):
    from codeformatter import phpfixer
    return phpfixer.tool_version(tool, argv, cwd, 'linux')


def test_version_phpcbf():
    out = (b'PHP_CodeSniffer version 4.0.4 (stable) by Squiz and '
           b'PHPCSStandards\n')
    phpcbf = ['php', '/proj/vendor/bin/phpcbf']

    with fake_run((0, out, '')) as run:
        assert tool_version('phpcbf', phpcbf) == (4, 0, 4)

    run.assert_called_once_with(
        ['php', '/proj/vendor/bin/phpcbf', '--version'], b'', '/proj',
        'linux')


def test_version_pint_cached():
    pint = ['php', '/proj/vendor/bin/pint']

    with fake_run((0, b'Pint 1.32.1\n', ''),
                  (0, b'Pint 1.26.0\n', '')) as run:
        assert tool_version('pint', pint) == (1, 32, 1)
        assert tool_version('pint', pint, cwd='/other') == (1, 32, 1)
        assert run.call_count == 1

        # Another command line is another executable, read on its own.
        assert tool_version('pint', ['/usr/local/bin/pint']) == (1, 26, 0)
        assert run.call_count == 2


@pytest.mark.parametrize('stdout,stderr,expected', [
    (b'PHP CS Fixer 3.95.27 by Fabien Potencier, Dariusz Ruminski and '
     b'contributors.\nPHP runtime: 8.5.4\n', 'Deprecated in 9.9.9',
     (3, 95, 27)),
    (b'Usage: phpcbf [options]\n', 'PHP_CodeSniffer version 3.13.6',
     (3, 13, 6)),
], ids=['stdout-first', 'stderr-when-stdout-has-none'])
def test_version_stdout_then_stderr(stdout, stderr, expected):
    with fake_run((0, stdout, stderr)):
        assert tool_version('tool', ['tool']) == expected


def test_version_unreadable():
    from codeformatter import phpfixer

    with fake_run((0, b'', ''), (0, b'', '')) as run:
        for _ in range(2):
            with pytest.raises(phpfixer.FixerError) as error:
                tool_version('phpcbf', ['phpcbf'])
            assert str(error.value) == 'could not read the phpcbf version'

    # Nothing was kept, so the second call read the version again.
    assert run.call_count == 2


@pytest.mark.parametrize('tool,result,reason', [
    # Composer's platform check, with display_errors on stdout.
    ('phpcbf', (255, b'PHP Fatal error:  Composer detected issues in your '
                b'platform: Your Composer dependencies require a PHP '
                b'version ">= 8.2.0". You are running 8.1.2.\n', ''), ''),
    # PHP-CS-Fixer's own environment check.
    ('php-cs-fixer', (1, b'', 'PHP needs to be a minimum version of PHP '
                              '7.4.0 and maximum version of PHP 8.1.*.'),
     ': PHP needs to be a minimum version of PHP 7.4.0 and maximum '
     'version of PHP 8.1.*.'),
], ids=['composer-platform-check', 'php-cs-fixer-env-check'])
def test_version_failed_run(tool, result, reason):
    from codeformatter import phpfixer

    with fake_run(result, result) as run:
        for _ in range(2):
            with pytest.raises(phpfixer.FixerError) as error:
                tool_version(tool, [tool])
            assert str(error.value) == (
                'could not read the {} version'.format(tool) + reason)

    # The number in the error text was not kept as the version.
    assert run.call_count == 2


@pytest.mark.parametrize('stderr,reason', [
    # A fixer whose #!/usr/bin/env php line finds no PHP on PATH.
    ('env: php: No such file or directory',
     'env: php: No such file or directory'),
    ('PHP Warning:  Module "mbstring" is already loaded\nPint 1.32.1',
     'PHP Warning:  Module "mbstring" is already loaded'),
    ('x' * 300, 'x' * 200),
], ids=['env-without-php', 'first-line', 'capped'])
def test_version_failed_run_gives_the_reason(stderr, reason):
    from codeformatter import phpfixer

    with fake_run((127, b'', stderr)):
        with pytest.raises(phpfixer.FixerError) as error:
            tool_version('pint', ['/usr/local/bin/pint'])
    assert str(error.value) == 'could not read the pint version: ' + reason


CS_FIXER = ['php', '/proj/vendor/bin/php-cs-fixer']
LISTED = b"'./src/a.php'\n'./src/b.php'\n"


@pytest.fixture
def proj(tmp_path):
    proj = tmp_path / 'proj'
    touch(proj / '.php-cs-fixer.dist.php')
    return proj


def covered(proj, file_name, with_config=True):
    from codeformatter import phpfixer
    config_file = str(proj / '.php-cs-fixer.dist.php') if with_config else None
    return phpfixer.covered_by_php_cs_fixer(
        CS_FIXER, str(proj), config_file, str(file_name), 'linux')


def test_covered_listed_file(proj):
    with fake_run((0, LISTED, '')) as run:
        assert covered(proj, proj / 'src' / 'a.php') is True

    run.assert_called_once_with(
        CS_FIXER + ['list-files', '--no-ansi',
                    '--config=' + str(proj / '.php-cs-fixer.dist.php')],
        b'', str(proj), 'linux')


def test_covered_without_config_file(proj):
    # Without a configuration there is no Finder that could exclude it.
    with fake_run() as run:
        assert covered(proj, proj / 'src' / 'a.php', with_config=False) is True

    run.assert_not_called()


def test_covered_excluded_refreshes_once(proj):
    # Not on disk, so the misses are not remembered.
    legacy = proj / 'legacy' / 'l.php'

    with fake_run((0, LISTED, ''), (0, LISTED, '')) as run:
        # A list read by this call is not read again.
        assert covered(proj, legacy) is False
        assert run.call_count == 1

        # Missing from the kept list, the file may be new: one refresh.
        assert covered(proj, legacy) is False
        assert run.call_count == 2


def test_covered_remembers_excluded_file_on_disk(proj):
    legacy = touch(proj / 'legacy' / 'l.php')

    with fake_run((0, LISTED, ''), (0, LISTED, '')) as run:
        assert covered(proj, proj / 'src' / 'a.php') is True
        # The one refresh misses a file that is on disk: remembered.
        assert covered(proj, legacy) is False
        assert run.call_count == 2

        assert covered(proj, legacy) is False
        assert run.call_count == 2


def test_covered_cold_read_remembers_nothing(proj):
    legacy = touch(proj / 'legacy' / 'l.php')

    with fake_run((0, LISTED, ''), (0, LISTED, '')) as run:
        # A cold read is not a refresh, so its miss is not remembered.
        assert covered(proj, legacy) is False
        assert run.call_count == 1

        # The one refresh misses it too, and it is on disk: remembered.
        assert covered(proj, legacy) is False
        assert run.call_count == 2

        assert covered(proj, legacy) is False
        assert run.call_count == 2


def test_covered_file_missing_from_disk_refreshes_again(proj):
    # on_pre_save runs before Sublime Text writes a new file.
    new = proj / 'legacy' / 'new.php'

    with fake_run((0, LISTED, ''), (0, LISTED, ''), (0, LISTED, '')) as run:
        assert covered(proj, proj / 'src' / 'a.php') is True
        assert covered(proj, new) is False
        assert run.call_count == 2

        assert covered(proj, new) is False
        assert run.call_count == 3


def remember_excluded(proj, legacy, run):
    """Warm the cache with src/a.php, then let the one refresh remember
    legacy as excluded; two runs."""
    assert covered(proj, proj / 'src' / 'a.php') is True
    assert covered(proj, legacy) is False
    assert covered(proj, legacy) is False
    assert run.call_count == 2


def test_covered_excluded_file_listed_by_a_later_refresh(proj):
    legacy = touch(proj / 'legacy' / 'l.php')
    relisted = LISTED + b"'./src/c.php'\n'./legacy/l.php'\n"

    with fake_run((0, LISTED, ''), (0, LISTED, ''),
                  (0, relisted, '')) as run:
        remember_excluded(proj, legacy, run)
        # A new file refreshes the list, which now holds legacy/l.php too.
        assert covered(proj, proj / 'src' / 'c.php') is True
        # The list is checked before the remembered exclusions.
        assert covered(proj, legacy) is True

    assert run.call_count == 3


def test_covered_exclusions_survive_a_refresh_for_another_file(proj):
    legacy = touch(proj / 'legacy' / 'l.php')
    refreshed = LISTED + b"'./src/c.php'\n"

    with fake_run((0, LISTED, ''), (0, LISTED, ''),
                  (0, refreshed, '')) as run:
        remember_excluded(proj, legacy, run)
        assert covered(proj, proj / 'src' / 'c.php') is True
        assert covered(proj, legacy) is False

    assert run.call_count == 3


def test_covered_config_change_forgets_exclusions(proj):
    config = str(proj / '.php-cs-fixer.dist.php')
    legacy = touch(proj / 'legacy' / 'l.php')

    with fake_run(*[(0, LISTED, '')] * 4) as run:
        remember_excluded(proj, legacy, run)

        mtime = os.path.getmtime(config)
        os.utime(config, (mtime + 10, mtime + 10))

        assert covered(proj, legacy) is False
        assert run.call_count == 3
        # A read after an mtime change is not a refresh: nothing kept.
        assert covered(proj, legacy) is False
        assert run.call_count == 4


def test_covered_new_file_after_refresh(proj):
    refreshed = LISTED + b"'./src/c.php'\n"

    with fake_run((0, LISTED, ''), (0, refreshed, '')) as run:
        assert covered(proj, proj / 'src' / 'a.php') is True
        assert covered(proj, proj / 'src' / 'c.php') is True
        assert covered(proj, proj / 'src' / 'c.php') is True

    assert run.call_count == 2


def test_covered_cache_and_config_change(proj):
    config = str(proj / '.php-cs-fixer.dist.php')
    a = proj / 'src' / 'a.php'

    with fake_run((0, LISTED, ''), (0, LISTED, '')) as run:
        assert covered(proj, a) is True
        assert covered(proj, a) is True
        assert run.call_count == 1

        mtime = os.path.getmtime(config)
        os.utime(config, (mtime + 10, mtime + 10))

        assert covered(proj, a) is True
        assert run.call_count == 2


def test_covered_path_with_space(proj):
    out = "'./my dir/módulo.php'\n".encode('utf-8')

    with fake_run((0, out, '')):
        assert covered(proj, proj / 'my dir' / 'módulo.php') is True


@pytest.mark.parametrize('out,relative', [
    # escapeshellarg on Windows: double quotes, and lines end with \r\n.
    (b'"./src/a.php"\r\n', ['src', 'a.php']),
    # escapeshellarg on POSIX writes an embedded ' as '\''.
    (b"'./it'\\''s.php'\n", ["it's.php"]),
], ids=['double-quoted', 'embedded-single-quote'])
def test_covered_unquotes_each_form(proj, out, relative):
    with fake_run((0, out, '')):
        assert covered(proj, proj.joinpath(*relative)) is True


def test_covered_error(proj):
    from codeformatter import phpfixer

    with fake_run((1, b'', 'boom')):
        with pytest.raises(phpfixer.FixerError) as error:
            covered(proj, proj / 'src' / 'a.php')

    assert str(error.value) == 'boom'


def test_covered_error_without_stderr(proj):
    from codeformatter import phpfixer

    with fake_run((255, b'PHP Fatal error: x\n', '')):
        with pytest.raises(phpfixer.FixerError) as error:
            covered(proj, proj / 'src' / 'a.php')

    assert str(error.value) == 'php-cs-fixer exited with code 255'


def test_covered_timeout(proj):
    from codeformatter import phpfixer, process

    with fake_run(process.Timeout()):
        with pytest.raises(process.Timeout):
            covered(proj, proj / 'src' / 'a.php')

    assert phpfixer._coverage_cache == {}


HEADERS = '--- php://stdin\n+++ php://stdin\n'


def apply_diff(text, diff):
    from codeformatter import phpfixer
    return phpfixer.apply_unified_diff(text, diff)


def test_diff_single_hunk():
    diff = (HEADERS + '@@ -1,2 +1,2 @@\n'
            ' <?php\n'
            '-$a = [ 1,2 ];\n'
            '+$a = array( 1, 2 );\n')

    assert apply_diff('<?php\n$a = [ 1,2 ];\n', diff) == (
        '<?php\n$a = array( 1, 2 );\n')


TWELVE_LINES = ''.join('$v{0} = {0};\n'.format(n) for n in range(1, 13))
TWO_HUNKS = (HEADERS + '@@ -1,5 +1,5 @@\n'
             ' $v1 = 1;\n'
             '-$v2 = 2;\n'
             '+$v2 = 20;\n'
             ' $v3 = 3;\n'
             ' $v4 = 4;\n'
             ' $v5 = 5;\n'
             '@@ -7,6 +7,7 @@\n'
             ' $v7 = 7;\n'
             ' $v8 = 8;\n'
             ' $v9 = 9;\n'
             '-$v10 = 10;\n'
             '+$v10 = 100;\n'
             '+$v10b = 101;\n'
             ' $v11 = 11;\n'
             ' $v12 = 12;\n')


def test_diff_two_hunks_and_insertions():
    assert apply_diff(TWELVE_LINES, TWO_HUNKS) == (
        '$v1 = 1;\n'
        '$v2 = 20;\n'
        '$v3 = 3;\n'
        '$v4 = 4;\n'
        '$v5 = 5;\n'
        '$v6 = 6;\n'
        '$v7 = 7;\n'
        '$v8 = 8;\n'
        '$v9 = 9;\n'
        '$v10 = 100;\n'
        '$v10b = 101;\n'
        '$v11 = 11;\n'
        '$v12 = 12;\n')


def test_diff_no_final_newline():
    new_side = (HEADERS + '@@ -1,2 +1,2 @@\n'
                ' <?php\n'
                '-$a=1;\n'
                '+$a = 1;\n'
                '\\ No newline at end of file\n')
    old_side = (HEADERS + '@@ -1,2 +1,2 @@\n'
                ' <?php\n'
                '-$a=1;\n'
                '\\ No newline at end of file\n'
                '+$a = 1;\n')

    assert apply_diff('<?php\n$a=1;\n', new_side) == '<?php\n$a = 1;'
    assert apply_diff('<?php\n$a=1;', old_side) == '<?php\n$a = 1;\n'


def test_diff_collapsed_range():
    # A range of one line is written without its count.
    diff = (HEADERS + '@@ -1 +1 @@\n'
            '-<?php echo 1 ;\n'
            '+<?php echo 1;\n')

    assert apply_diff('<?php echo 1 ;\n', diff) == '<?php echo 1;\n'


def test_diff_removed_line_like_a_header():
    diff = (HEADERS + '@@ -1,2 +1,2 @@\n'
            ' <?php\n'
            '---$i;\n'
            '+$i--;\n')

    assert apply_diff('<?php\n--$i;\n', diff) == '<?php\n$i--;\n'


@pytest.mark.parametrize('text,header,expected', [
    # sebastian/diff gives the line the hunk starts at, also for count 0.
    ('', '@@ -1,0 +1,2 @@', '<?php\n\n'),
    ('', '@@ -0,0 +1,2 @@', '<?php\n\n'),
    ('<?php\n\n$c = 1;\n', '@@ -3,0 +3,2 @@', '<?php\n\n<?php\n\n$c = 1;\n'),
], ids=['empty-text', 'empty-text-zero-start', 'within-text'])
def test_diff_hunk_that_removes_nothing(text, header, expected):
    diff = HEADERS + header + '\n+<?php\n+\n'

    assert apply_diff(text, diff) == expected


def test_diff_splits_on_newline_only():
    # \x0c, \u2028 and a lone \r end a line for str.splitlines, not for
    # the diff.
    text = '<?php\r\n$a = "x\x0cy\u2028z\rw";\r\n$b=1;\r\n'
    diff = (HEADERS + '@@ -1,3 +1,3 @@\n'
            ' <?php\r\n'
            ' $a = "x\x0cy\u2028z\rw";\r\n'
            '-$b=1;\r\n'
            '+$b = 1;\r\n')

    assert apply_diff(text, diff) == (
        '<?php\r\n$a = "x\x0cy\u2028z\rw";\r\n$b = 1;\r\n')


SINGLE_TEXT = '<?php\n$a = [ 1,2 ];\n'


@pytest.mark.parametrize('text,diff', [
    (SINGLE_TEXT, '@@ -1,2 +1,2 @@\n <?php\n-$a = [1, 2];\n+$a = 1;\n'),
    (SINGLE_TEXT, '@@ -1,2 +1,2 @@\n <?PHP\n-$a = [ 1,2 ];\n+$a = 1;\n'),
    (SINGLE_TEXT, '@@ -5,2 +5,2 @@\n <?php\n-$a = [ 1,2 ];\n+$a = 1;\n'),
    (SINGLE_TEXT, '@@ -1,2 +1,2 @@\n <?php\n-$a = [ 1,2 ];\n'),
    (SINGLE_TEXT, '@@ -1 +1 @@\n <?php\n-$a = [ 1,2 ];\n+$a = 1;\n'),
    (SINGLE_TEXT, '@@ -2 +2 @@\n-$a = [ 1,2 ];\n+$a = 1;\n'
                  '@@ -1 +1 @@\n-<?php\n+<?php \n'),
    (TWELVE_LINES, TWO_HUNKS.replace(' $v11 = 11;', ' $v11 = 0;')),
], ids=['removed-line', 'context-line', 'start-beyond-text',
        'fewer-lines-than-counts', 'more-lines-than-counts',
        'hunks-out-of-order', 'second-hunk'])
def test_diff_mismatch_raises(text, diff):
    with pytest.raises(ValueError):
        apply_diff(text, HEADERS + diff)
