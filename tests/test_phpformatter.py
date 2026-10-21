import json

import pytest
from unittest.mock import Mock, call, patch

from .scenarios import format_on_save_scenarios


REFUSAL = ('No PHP fixer is configured for this file. Add pint.json, '
           '.php-cs-fixer.dist.php or phpcs.xml to the project, or set '
           '"fixer" in codeformatter_php_options.')
UNKNOWN_FIXER = ('Unknown fixer "php_cs_fixer" in codeformatter_php_options. '
                 'Use "pint", "php-cs-fixer" or "phpcbf".')
PINT_NOT_FOUND = ('pint not found. Run "composer require --dev laravel/pint" '
                  'in the project, or set fixer_path in '
                  'codeformatter_php_options.')
CS_FIXER_NOT_FOUND = ('php-cs-fixer not found. Run "composer require --dev '
                      'friendsofphp/php-cs-fixer" in the project, or set '
                      'fixer_path in codeformatter_php_options.')
PINT_MINIMUM = ('Pint 1.26.0 or newer is needed to format from the editor. '
                'Run "composer update laravel/pint" in the project.')
BAD_DIFF = 'php-cs-fixer returned a diff that does not match the buffer'
UNREADABLE = 'php-cs-fixer printed output that is not its JSON report'

TEXT = b'<?php\n$a = [ 1,2 ];\n'
FIXED = b'<?php\nfixed\n'
PINT_VERSION = (0, b'Pint 1.32.1\n', '')
PHPCBF4_VERSION = (0, b'PHP_CodeSniffer version 4.0.4 (stable) by Squiz '
                      b'and PHPCSStandards\n', '')
PHPCBF3_VERSION = (0, b'PHP_CodeSniffer version 3.13.6 (stable) by Squiz '
                      b'and PHPCSStandards\n', '')
CS_FIXER_FIX = ['fix', '--no-ansi', '--using-cache=no', '--diff',
                '--format=json']
# The single-hunk diff of the phpfixer tests (test_diff_single_hunk).
SINGLE_DIFF = ('--- php://stdin\n+++ php://stdin\n'
               '@@ -1,2 +1,2 @@\n'
               ' <?php\n'
               '-$a = [ 1,2 ];\n'
               '+$a = array( 1, 2 );\n')
LISTED = b"'./src/a.php'\n"


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
    return patch('codeformatter.phpformatter.process.run',
                 side_effect=list(results))


def no_fixer_on_path(found=None):
    return patch('codeformatter.phpfixer.shutil.which', return_value=found)


def touch(path):
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text('')
    return path


def project(tmp_path, *files, php='src/a.php'):
    """Make tmp_path/proj holding files (empty) and the PHP file; return
    both paths."""
    proj = tmp_path / 'proj'
    for name in files:
        touch(proj / name)
    return proj, touch(proj / php)


def vendor(proj, tool, php='php'):
    return [php, str(proj / 'vendor' / 'bin' / tool)]


def php_format(file_name, text=TEXT, **opts):
    from codeformatter.phpformatter import PhpFormatter
    options = {'syntaxes': 'php'}
    options.update(opts)
    formatter = Mock()
    formatter.settings = {'codeformatter_php_options': options}
    formatter.file_name = None if file_name is None else str(file_name)
    formatter.platform = 'linux'
    return PhpFormatter(formatter).format(text)


def report(diff, ensure_ascii=True):
    """PHP-CS-Fixer's --format=json output for one fixed stdin."""
    return json.dumps({
        'about': 'PHP CS Fixer 3.95.27 by Fabien Potencier, Dariusz '
                 'Ruminski and contributors.',
        'files': [{'name': 'php://stdin', 'diff': diff}],
        'time': {'total': 0.012},
        'memory': 16,
    }, ensure_ascii=ensure_ascii).encode('utf-8')


def test_refuses_without_config(tmp_path):
    proj, php = project(tmp_path, 'composer.json')

    with fake_run() as run:
        assert php_format(php) == ('', REFUSAL)

    run.assert_not_called()


def test_refuses_unsaved_buffer():
    with fake_run() as run:
        assert php_format(None) == ('', REFUSAL)

    run.assert_not_called()


def test_unknown_fixer(tmp_path):
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run() as run:
        assert php_format(php, fixer='php_cs_fixer') == ('', UNKNOWN_FIXER)

    run.assert_not_called()


def test_fixer_not_found(tmp_path):
    proj, php = project(tmp_path, 'pint.json')

    with fake_run() as run, no_fixer_on_path() as which:
        assert php_format(php) == ('', PINT_NOT_FOUND)

    which.assert_called_once_with('pint')
    run.assert_not_called()


@pytest.mark.parametrize('results', [
    [OSError(2, 'No such file or directory')],
    [PINT_VERSION, OSError(2, 'No such file or directory')],
], ids=['version-read', 'fix'])
def test_fixer_cannot_start(tmp_path, results):
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run(*results):
        assert php_format(php) == ('', PINT_NOT_FOUND)


def test_fixer_timeout(tmp_path):
    from codeformatter import process
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run(PINT_VERSION, process.Timeout()):
        assert php_format(php) == ('', 'pint did not finish in 10 seconds')


def test_version_unreadable(tmp_path):
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run((0, b'Laravel Pint\n', '')) as run:
        assert php_format(php) == ('', 'could not read the pint version')

    assert run.call_count == 1


def test_pint_formats(tmp_path):
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')
    pint = vendor(proj, 'pint')

    with fake_run(PINT_VERSION, (0, FIXED, '')) as run:
        assert php_format(php) == (FIXED, '')

    assert run.call_args_list == [
        call(pint + ['--version'], b'', str(proj), 'linux'),
        call(pint + ['--no-ansi', '--config=' + str(proj / 'pint.json'),
                     '--stdin-filename=' + str(php), '-'],
             TEXT, str(proj), 'linux'),
    ]


def test_pint_path_with_space(tmp_path):
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint',
                        php='my dir/módulo.php')

    with fake_run(PINT_VERSION, (0, FIXED, '')) as run:
        assert php_format(php) == (FIXED, '')

    assert ' ' in str(php)
    cmd = run.call_args[0][0]
    assert cmd[-2:] == ['--stdin-filename=' + str(php), '-']
    assert [arg for arg in cmd if 'módulo' in arg] == [
        '--stdin-filename=' + str(php)]


def test_pint_too_old(tmp_path):
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run((0, b'Pint 1.25.1\n', '')) as run:
        assert php_format(php) == ('', PINT_MINIMUM)

    assert run.call_count == 1


def test_pint_oldest_supported(tmp_path):
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run((0, b'Pint 1.26.0\n', ''), (0, FIXED, '')) as run:
        assert php_format(php) == (FIXED, '')

    assert run.call_count == 2


def test_pint_fallback_has_no_config_option(tmp_path):
    proj, php = project(tmp_path, 'composer.json', 'vendor/bin/pint')

    with fake_run(PINT_VERSION, (0, FIXED, '')) as run:
        assert php_format(php) == (FIXED, '')

    cmd = run.call_args[0][0]
    assert cmd == vendor(proj, 'pint') + [
        '--no-ansi', '--stdin-filename=' + str(php), '-']
    assert not [arg for arg in cmd if arg.startswith('--config')]
    assert run.call_args[0][2] == str(proj)


@pytest.mark.parametrize('result,message', [
    ((1, b'', ''), 'pint exited with code 1'),
    ((1, b'', 'Unexpected token'), 'Unexpected token'),
], ids=['no-stderr', 'stderr'])
def test_pint_error(tmp_path, capsys, result, message):
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run(PINT_VERSION, result):
        assert php_format(php) == ('', message)

    # An error goes to the dialog, not to the console.
    assert capsys.readouterr().out == ''


def test_pint_empty_output_keeps_text(tmp_path):
    # Empty stdout and stderr would be an empty error dialog.
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run(PINT_VERSION, (0, b'', '')):
        assert php_format(php) == (TEXT, '')


def test_ignores_retired_options(tmp_path):
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run(PINT_VERSION, (0, FIXED, '')) as run:
        assert php_format(php, fixer='', psr2=True, php55_compat=True,
                          indent_with_space=4,
                          passes=['AlignEquals']) == (FIXED, '')

    assert run.call_args[0][0] == vendor(proj, 'pint') + [
        '--no-ansi', '--config=' + str(proj / 'pint.json'),
        '--stdin-filename=' + str(php), '-']


def test_phpcbf4_results(tmp_path):
    proj, php = project(tmp_path, 'phpcs.xml', 'vendor/bin/phpcbf')
    phpcbf = vendor(proj, 'phpcbf', php='/opt/php/bin/php')

    with fake_run(PHPCBF4_VERSION,
                  (0, FIXED, ''),
                  (2, FIXED, ''),
                  (16, b'', 'ERROR: No files were checked.'),
                  (16, b'', 'ERROR: Ruleset x is not valid')) as run:
        results = [php_format(php, php_path='/opt/php/bin/php')
                   for _ in range(4)]

    assert results == [
        (FIXED, ''),
        (FIXED, ''),
        (TEXT, ''),
        ('', 'ERROR: Ruleset x is not valid'),
    ]
    # The version was read once for the four formats.
    assert run.call_count == 5
    assert run.call_args_list[:2] == [
        call(phpcbf + ['--version'], b'', str(proj), 'linux'),
        call(phpcbf + ['-q', '--no-colors', '--stdin-path=' + str(php), '-'],
             TEXT, str(proj), 'linux'),
    ]


def test_phpcbf3_results(tmp_path):
    proj, php = project(tmp_path, 'phpcs.xml', 'vendor/bin/phpcbf')

    with fake_run(PHPCBF3_VERSION,
                  (1, FIXED, ''),
                  (0, b'No fixable errors were found\n\nTime: 8ms', ''),
                  (3, b'', 'boom')) as run:
        results = [php_format(php) for _ in range(3)]

    assert results == [(FIXED, ''), (TEXT, ''), ('', 'boom')]
    assert run.call_count == 4
    assert run.call_args_list[1] == call(
        vendor(proj, 'phpcbf') + ['-q', '--no-colors',
                                  '--stdin-path=' + str(php), '-'],
        TEXT, str(proj), 'linux')


def test_phpcbf_newer_major_reads_as_4(tmp_path):
    proj, php = project(tmp_path, 'phpcs.xml', 'vendor/bin/phpcbf')

    with fake_run((0, b'PHP_CodeSniffer version 5.0.0\n', ''),
                  (2, FIXED, '')):
        assert php_format(php) == (FIXED, '')


def test_php_cs_fixer_applies_diff(tmp_path):
    proj, php = project(tmp_path, '.php-cs-fixer.dist.php',
                        'vendor/bin/php-cs-fixer')
    cs_fixer = vendor(proj, 'php-cs-fixer')
    config = '--config=' + str(proj / '.php-cs-fixer.dist.php')

    with fake_run((0, LISTED, ''), (8, report(SINGLE_DIFF), '')) as run:
        assert php_format(php) == (b'<?php\n$a = array( 1, 2 );\n', '')

    assert run.call_args_list == [
        call(cs_fixer + ['list-files', '--no-ansi', config], b'', str(proj),
             'linux'),
        call(cs_fixer + CS_FIXER_FIX + [config, '-'], TEXT, str(proj),
             'linux'),
    ]


@pytest.mark.parametrize('ensure_ascii', [True, False],
                         ids=['escaped', 'raw-utf-8'])
def test_php_cs_fixer_non_ascii(tmp_path, ensure_ascii):
    proj, php = project(tmp_path, '.php-cs-fixer.dist.php',
                        'vendor/bin/php-cs-fixer')
    text = '<?php\n$a = "módulo";\n'.encode('utf-8')
    diff = ('--- php://stdin\n+++ php://stdin\n'
            '@@ -1,2 +1,2 @@\n'
            ' <?php\n'
            '-$a = "módulo";\n'
            "+$a = 'módulo';\n")

    with fake_run((0, LISTED, ''), (8, report(diff, ensure_ascii), '')):
        assert php_format(php, text=text) == (
            "<?php\n$a = 'módulo';\n".encode('utf-8'), '')


def test_php_cs_fixer_clean(tmp_path):
    proj, php = project(tmp_path, '.php-cs-fixer.dist.php',
                        'vendor/bin/php-cs-fixer')

    with fake_run((0, LISTED, ''), (0, b'{"files": []}', '')):
        assert php_format(php) == (TEXT, '')


def test_php_cs_fixer_excluded(tmp_path):
    proj, php = project(tmp_path, '.php-cs-fixer.dist.php',
                        'vendor/bin/php-cs-fixer')

    with fake_run((0, b"'./src/b.php'\n", '')) as run:
        assert php_format(php) == (TEXT, '')

    # Only list-files ran.
    assert run.call_count == 1
    assert run.call_args[0][0][2] == 'list-files'


def test_php_cs_fixer_bad_diff(tmp_path):
    proj, php = project(tmp_path, '.php-cs-fixer.dist.php',
                        'vendor/bin/php-cs-fixer')
    # The context line fits; the removed line does not.
    diff = ('--- php://stdin\n+++ php://stdin\n'
            '@@ -1,2 +1,2 @@\n'
            ' <?php\n'
            '-$a = [1, 2];\n'
            '+$a = 1;\n')

    with fake_run((0, LISTED, ''), (8, report(diff), '')):
        assert php_format(php) == ('', BAD_DIFF)


@pytest.mark.parametrize('stdout,message', [
    (b'PHP Deprecated:  Something in /p/x.php on line 3\n{"files": []}',
     UNREADABLE + ': PHP Deprecated:  Something in /p/x.php on line 3'),
    (b'', UNREADABLE),
    (b'\xff\xfe', UNREADABLE + ': ��'),
    (b'[]', UNREADABLE + ': []'),
    (b'{"files": []}', UNREADABLE + ': {"files": []}'),
    (b'{"files": [{"name": "php://stdin"}]}',
     UNREADABLE + ': {"files": [{"name": "php://stdin"}]}'),
    (b'{"files": [{"diff": null}]}',
     UNREADABLE + ': {"files": [{"diff": null}]}'),
], ids=['notice-first', 'empty', 'not-utf-8', 'not-an-object', 'no-files',
        'no-diff', 'diff-not-text'])
def test_php_cs_fixer_unreadable_report(tmp_path, stdout, message):
    proj, php = project(tmp_path, '.php-cs-fixer.dist.php',
                        'vendor/bin/php-cs-fixer')

    with fake_run((0, LISTED, ''), (8, stdout, '')):
        assert php_format(php) == ('', message)


@pytest.mark.parametrize('result,message', [
    ((16, b'', 'Configuration file is not valid'),
     'Configuration file is not valid'),
    ((64, b'', ''), 'php-cs-fixer exited with code 64'),
], ids=['stderr', 'no-stderr'])
def test_php_cs_fixer_error(tmp_path, result, message):
    proj, php = project(tmp_path, '.php-cs-fixer.dist.php',
                        'vendor/bin/php-cs-fixer')

    with fake_run((0, LISTED, ''), result):
        assert php_format(php) == ('', message)


def test_php_cs_fixer_list_files_error(tmp_path):
    proj, php = project(tmp_path, '.php-cs-fixer.dist.php',
                        'vendor/bin/php-cs-fixer')

    with fake_run((1, b'', 'The config file is invalid')) as run:
        assert php_format(php) == ('', 'The config file is invalid')

    assert run.call_count == 1


def test_php_cs_fixer_config_gone(tmp_path):
    # The configuration file was removed after the fixer was picked.
    proj, php = project(tmp_path, '.php-cs-fixer.dist.php',
                        'vendor/bin/php-cs-fixer')

    with fake_run() as run, patch('codeformatter.phpfixer.os.path.getmtime',
                                  side_effect=OSError(2, 'No such file')):
        assert php_format(php) == ('', CS_FIXER_NOT_FOUND)

    run.assert_not_called()


def test_php_cs_fixer_without_config_skips_list_files(tmp_path):
    proj, php = project(tmp_path, 'composer.json', 'vendor/bin/php-cs-fixer')

    with fake_run((8, report(SINGLE_DIFF), '')) as run:
        assert php_format(php, fixer='php-cs-fixer') == (
            b'<?php\n$a = array( 1, 2 );\n', '')

    run.assert_called_once_with(
        vendor(proj, 'php-cs-fixer') + CS_FIXER_FIX + ['-'], TEXT, str(proj),
        'linux')


def test_warnings_to_console(tmp_path, capsys):
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run(PINT_VERSION, (0, FIXED, 'a\nb')):
        assert php_format(php) == (FIXED, '')

    assert capsys.readouterr().out == 'CodeFormatter: a\nCodeFormatter: b\n'


def test_unsaved_forced_phpcbf():
    with fake_run(PHPCBF4_VERSION, (0, FIXED, '')) as run, \
            no_fixer_on_path('/usr/local/bin/phpcbf'):
        assert php_format(None, fixer='phpcbf') == (FIXED, '')

    assert run.call_args_list == [
        call(['/usr/local/bin/phpcbf', '--version'], b'', None, 'linux'),
        call(['/usr/local/bin/phpcbf', '-q', '--no-colors', '-'], TEXT, None,
             'linux'),
    ]


def test_unsaved_forced_php_cs_fixer():
    with fake_run((0, b'{"files": []}', '')) as run, \
            no_fixer_on_path('/usr/local/bin/php-cs-fixer'):
        assert php_format(None, fixer='php-cs-fixer') == (TEXT, '')

    # No list-files: a never-saved buffer has no path to look up.
    run.assert_called_once_with(
        ['/usr/local/bin/php-cs-fixer'] + CS_FIXER_FIX + ['-'], TEXT, None,
        'linux')


@pytest.mark.parametrize('options,filename,expected', format_on_save_scenarios)
def test_php_formatter_format_on_save_enabled(options, filename, expected):
    from codeformatter.phpformatter import PhpFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_php_options': options}

    assert PhpFormatter(mocked_formatter).format_on_save_enabled(
        filename) is expected
