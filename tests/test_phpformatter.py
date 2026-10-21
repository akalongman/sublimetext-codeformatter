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
PHP_NOT_FOUND = ('{} not found. Set php_path in codeformatter_php_options '
                 'to your PHP executable.')
PINT_MINIMUM = ('Pint 1.26.0 or newer is needed to format from the editor. '
                'Run "composer update laravel/pint" in the project.')
BAD_DIFF = 'php-cs-fixer returned a diff that does not match the buffer'
UNREADABLE = 'php-cs-fixer printed output that is not its JSON report'
PINT_BATCH = ('pint runs through the batch file {}, and cmd.exe would '
              'misread a & | < > ^ % or ! in a path on its command line. '
              'Run "composer require --dev laravel/pint" in the project and '
              'leave fixer_path empty, so pint runs from vendor/bin.')

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
    return [php, '-d', 'display_errors=stderr',
            str(proj / 'vendor' / 'bin' / tool)]


def php_format(file_name, text=TEXT, platform='linux', **opts):
    from codeformatter.phpformatter import PhpFormatter
    options = {'syntaxes': 'php'}
    options.update(opts)
    formatter = Mock()
    formatter.settings = {'codeformatter_php_options': options}
    formatter.file_name = None if file_name is None else str(file_name)
    formatter.platform = platform
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


# How Popen names the program it could not start: Python 3.6 and newer in
# filename, Python 3.3 to 3.5 only at the end of the message.
@pytest.mark.parametrize('error', [
    OSError(2, 'No such file or directory', '/nope/php'),
    OSError(2, "No such file or directory: '/nope/php'"),
], ids=['python3.6', 'python3.3'])
@pytest.mark.parametrize('before', [[], [PINT_VERSION]],
                         ids=['version-read', 'fix'])
def test_php_cannot_start(tmp_path, before, error):
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run(*(before + [error])):
        assert php_format(php, php_path='/nope/php') == (
            '', PHP_NOT_FOUND.format('/nope/php'))


def test_php_from_path_cannot_start(tmp_path):
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run(OSError(2, 'No such file or directory', 'php')):
        assert php_format(php) == ('', PHP_NOT_FOUND.format('php'))


def without_strerror(cwd):
    error = OSError(2, 'No such file or directory')
    error.strerror = None
    return error


@pytest.mark.parametrize('make_error', [
    lambda cwd: OSError(2, 'No such file or directory', cwd),
    lambda cwd: OSError(2, 'No such file or directory: ' + repr(cwd)),
    lambda cwd: OSError(2, 'The system cannot find the file specified'),
    without_strerror,
], ids=['python3.6-cwd', 'python3.3-cwd', 'windows', 'no-strerror'])
def test_php_command_error_not_naming_php(tmp_path, make_error):
    # Only an error that names PHP itself is about PHP: a missing working
    # directory, or a host that names nothing, keeps the fixer message.
    proj, php = project(tmp_path, 'pint.json', 'vendor/bin/pint')

    with fake_run(make_error(str(proj))):
        assert php_format(php, php_path='/nope/php') == ('', PINT_NOT_FOUND)


@pytest.mark.parametrize('error', [
    OSError(2, 'No such file or directory', '/nope/pint'),
    OSError(2, "No such file or directory: '/nope/pint'"),
], ids=['python3.6', 'python3.3'])
def test_fixer_path_cannot_start(tmp_path, error):
    # The fixer runs as given, so argv[0] is the fixer, not PHP.
    proj, php = project(tmp_path, 'pint.json')

    with fake_run(error):
        assert php_format(php, fixer_path='/nope/pint',
                          php_path='/nope/php') == ('', PINT_NOT_FOUND)


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


def test_version_read_failed_with_reason(tmp_path):
    # A global fixer whose #!/usr/bin/env php line finds no PHP on PATH.
    proj, php = project(tmp_path, 'pint.json')

    with fake_run((127, b'', 'env: php: No such file or directory')):
        assert php_format(php, fixer_path='/usr/local/bin/pint') == (
            '', 'could not read the pint version: env: php: No such file '
                'or directory')


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
    ((1, b'stdout is not the reason', 'Unexpected token'),
     'Unexpected token'),
    # Without stderr, the first non-empty line of stdout is the reason.
    ((255, b'\n  \nPHP Fatal error:  Uncaught Error \nStack trace:\n', ''),
     'pint exited with code 255: PHP Fatal error:  Uncaught Error'),
    ((255, b'x' * 300 + b'\n', ''), 'pint exited with code 255: ' + 'x' * 200),
], ids=['no-output', 'stderr', 'stderr-and-stdout', 'stdout-only',
        'stdout-capped'])
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
                  (3, b'', 'boom'),
                  # 3.x prints its fatal errors on stdout.
                  (3, b'ERROR: the "X" coding standard is not installed.\n',
                   '')) as run:
        results = [php_format(php) for _ in range(4)]

    assert results == [
        (FIXED, ''),
        (TEXT, ''),
        ('', 'boom'),
        ('', 'phpcbf exited with code 3: ERROR: the "X" coding standard is '
             'not installed.'),
    ]
    assert run.call_count == 5
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


def test_php_cs_fixer_syntax_error(tmp_path, capsys):
    proj, php = project(tmp_path, '.php-cs-fixer.dist.php',
                        'vendor/bin/php-cs-fixer')
    # What PHP-CS-Fixer 3.95.27 prints for a buffer with a syntax error.
    lint = ('Files that were not fixed due to errors reported during '
            'linting before fixing:\n   1) stdin.php')

    with fake_run((0, LISTED, ''), (4, b'{"files": []}', lint)):
        assert php_format(php) == (TEXT, '')

    # As for a clean buffer, its stderr goes to the console.
    assert capsys.readouterr().out == (
        'CodeFormatter: Files that were not fixed due to errors reported '
        'during linting before fixing:\n'
        'CodeFormatter:    1) stdin.php\n')


def test_php_cs_fixer_excluded(tmp_path):
    proj, php = project(tmp_path, '.php-cs-fixer.dist.php',
                        'vendor/bin/php-cs-fixer')

    with fake_run((0, b"'./src/b.php'\n", '')) as run:
        assert php_format(php) == (TEXT, '')

    # Only list-files ran.
    run.assert_called_once_with(
        vendor(proj, 'php-cs-fixer') + [
            'list-files', '--no-ansi',
            '--config=' + str(proj / '.php-cs-fixer.dist.php')],
        b'', str(proj), 'linux')


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


def global_pint(tmp_path, sibling=None):
    """Pint installed with "composer global require": a batch proxy in a
    global bin directory, and the PHP proxy next to it when sibling is
    given."""
    bin_dir = tmp_path / 'Composer' / 'vendor' / 'bin'
    batch = touch(bin_dir / 'pint.bat')
    if sibling is not None:
        (bin_dir / 'pint').write_text(sibling)
    return str(batch), str(bin_dir / 'pint')


@pytest.mark.parametrize('where', ['R&D/proj', 'proj/x&calc&'],
                         ids=['project-dir', 'file-name'])
def test_batch_file_refused_for_cmd_exe_characters(tmp_path, where):
    proj = tmp_path / where
    touch(proj / 'pint.json')
    php = touch(proj / 'a.php')
    batch, _ = global_pint(tmp_path)

    with fake_run() as run, no_fixer_on_path(batch):
        assert php_format(php, platform='windows') == (
            '', PINT_BATCH.format(batch))

    # Refused before any process started, the version read included.
    run.assert_not_called()


def test_batch_file_refused_for_fixer_path(tmp_path):
    proj, php = project(tmp_path, 'pint.json', php='50%off/a.php')
    batch, _ = global_pint(tmp_path, '#!/usr/bin/env sh\n')

    with fake_run() as run:
        assert php_format(php, platform='windows', fixer_path=batch) == (
            '', PINT_BATCH.format(batch))

    run.assert_not_called()


def test_batch_file_runs_for_clean_paths(tmp_path):
    proj, php = project(tmp_path, 'pint.json', php='my dir/módulo.php')
    batch, _ = global_pint(tmp_path)

    with fake_run(PINT_VERSION, (0, FIXED, '')) as run, \
            no_fixer_on_path(batch):
        assert php_format(php, platform='windows') == (FIXED, '')

    assert run.call_args_list == [
        call([batch, '--version'], b'', str(proj), 'windows'),
        call([batch, '--no-ansi', '--config=' + str(proj / 'pint.json'),
              '--stdin-filename=' + str(php), '-'], TEXT, str(proj),
             'windows'),
    ]


def test_batch_file_redirected_through_php_is_not_refused(tmp_path):
    proj = tmp_path / 'R&D' / 'proj'
    touch(proj / 'pint.json')
    php = touch(proj / 'a.php')
    batch, script = global_pint(tmp_path, '#!/usr/bin/env php\n<?php\n')
    pint = ['php', '-d', 'display_errors=stderr', script]

    with fake_run(PINT_VERSION, (0, FIXED, '')) as run, \
            no_fixer_on_path(batch):
        assert php_format(php, platform='windows') == (FIXED, '')

    assert run.call_args_list[0] == call(
        pint + ['--version'], b'', str(proj), 'windows')


@pytest.mark.parametrize('platform', ['linux', 'osx'])
def test_batch_file_off_windows_runs_as_given(tmp_path, platform):
    proj = tmp_path / 'R&D' / 'proj'
    touch(proj / 'pint.json')
    php = touch(proj / 'a.php')
    batch, _ = global_pint(tmp_path, '<?php\n')

    with fake_run(PINT_VERSION, (0, FIXED, '')) as run, \
            no_fixer_on_path(batch):
        assert php_format(php, platform=platform) == (FIXED, '')

    assert run.call_args_list[0] == call(
        [batch, '--version'], b'', str(proj), platform)


@pytest.mark.parametrize('options,filename,expected', format_on_save_scenarios)
def test_php_formatter_format_on_save_enabled(options, filename, expected):
    from codeformatter.phpformatter import PhpFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_php_options': options}

    assert PhpFormatter(mocked_formatter).format_on_save_enabled(
        filename) is expected
