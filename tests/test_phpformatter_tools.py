import json
import os
import shutil
import tempfile

import pytest
from unittest.mock import Mock


# A Composer project holding vendor/bin/<tool> for the fixers under test.
TOOLS = os.environ.get('CODEFORMATTER_TEST_PHP_TOOLS')
REQUIRE_TOOLS = os.environ.get('CODEFORMATTER_REQUIRE_TOOLS') == '1'
if TOOLS:
    # The tests chdir away, so a relative path would stop resolving.
    TOOLS = os.path.abspath(TOOLS)
elif REQUIRE_TOOLS:
    raise RuntimeError(
        'CODEFORMATTER_TEST_PHP_TOOLS is required '
        '(CODEFORMATTER_REQUIRE_TOOLS=1) but is not set')

pytestmark = pytest.mark.skipif(
    not TOOLS, reason='CODEFORMATTER_TEST_PHP_TOOLS not set')

CONFIG_NAMES = {
    'pint': 'pint.json',
    'phpcbf': 'phpcs.xml',
    'php-cs-fixer': '.php-cs-fixer.dist.php',
}

TEXT = b'<?php\n$a = [ 1,2 ];\n'
BROKEN = b'<?php\nfunction (\n'

PINT_CONFIG = {'preset': 'psr12',
               'rules': {'array_syntax': {'syntax': 'long'}}}
PINT_EXCLUDING = dict(PINT_CONFIG, exclude=['legacy'])

PHPCS_XML = '''<?xml version="1.0"?>
<ruleset name="test">
    <rule ref="Generic.Arrays.DisallowShortArraySyntax"/>
{}</ruleset>
'''
PHPCS_RULESET = PHPCS_XML.format('')
PHPCS_EXCLUDING = PHPCS_XML.format(
    '    <exclude-pattern>*/legacy/*</exclude-pattern>\n')

PHP_CS_FIXER_CONFIG = '''<?php

return (new PhpCsFixer\\Config())
    ->setRules(['array_syntax' => ['syntax' => 'long']])
    ->setFinder(PhpCsFixer\\Finder::create()->in(__DIR__)->exclude('legacy'));
'''


@pytest.fixture(autouse=True)
def clear_caches():
    from codeformatter import phpfixer
    phpfixer._version_cache.clear()
    phpfixer._coverage_cache.clear()
    yield
    phpfixer._version_cache.clear()
    phpfixer._coverage_cache.clear()


def require(tool):
    """Skip the test when vendor/bin/<tool> is not under TOOLS; fail it
    instead when CODEFORMATTER_REQUIRE_TOOLS=1."""
    script = os.path.join(TOOLS, 'vendor', 'bin', tool)
    if os.path.isfile(script):
        return
    message = '{} not installed: {} is missing'.format(tool, script)
    if REQUIRE_TOOLS:
        pytest.fail(message + ' (CODEFORMATTER_REQUIRE_TOOLS=1)')
    pytest.skip(message)


def format_in_project(tmp_path, monkeypatch, tool, config, relative_path,
                      text):
    """Format text as the file relative_path of a project configured for
    tool, from a working directory outside the project.

    The project is a fresh directory under <TOOLS>/cases, so the fixer is
    found in <TOOLS>/vendor/bin as in a real project. The file is written
    to disk too, as PHP-CS-Fixer lists the files on disk.
    """
    require(tool)
    from codeformatter.phpformatter import PhpFormatter
    cases = os.path.join(TOOLS, 'cases')
    os.makedirs(cases, exist_ok=True)
    project = tempfile.mkdtemp(dir=cases)
    try:
        # Bytes, so the files hold the same line endings on Windows.
        with open(os.path.join(project, CONFIG_NAMES[tool]), 'wb') as f:
            f.write(config.encode('utf-8'))
        path = os.path.normpath(os.path.join(project, relative_path))
        os.makedirs(os.path.dirname(path))
        with open(path, 'wb') as f:
            f.write(text)
        monkeypatch.chdir(tmp_path)
        mocked_formatter = Mock()
        mocked_formatter.settings = {'codeformatter_php_options': {}}
        mocked_formatter.file_name = path
        mocked_formatter.platform = 'windows' if os.name == 'nt' else 'linux'
        return PhpFormatter(mocked_formatter).format(text)
    finally:
        shutil.rmtree(project)


def test_pint_applies_project_config(tmp_path, monkeypatch):
    out, err = format_in_project(
        tmp_path, monkeypatch, 'pint', json.dumps(PINT_CONFIG),
        'src/a.php', TEXT)

    assert err == ''
    assert b'array(' in out


def test_pint_leaves_excluded_file_unchanged(tmp_path, monkeypatch):
    result = format_in_project(
        tmp_path, monkeypatch, 'pint', json.dumps(PINT_EXCLUDING),
        'legacy/l.php', TEXT)

    assert result == (TEXT, '')


def test_pint_syntax_error_unchanged(tmp_path, monkeypatch):
    result = format_in_project(
        tmp_path, monkeypatch, 'pint', json.dumps(PINT_CONFIG),
        'src/a.php', BROKEN)

    assert result == (BROKEN, '')


def test_phpcbf_applies_project_ruleset(tmp_path, monkeypatch):
    out, err = format_in_project(
        tmp_path, monkeypatch, 'phpcbf', PHPCS_RULESET, 'src/a.php', TEXT)

    assert err == ''
    assert b'array(' in out


def test_phpcbf_leaves_excluded_file_unchanged(tmp_path, monkeypatch):
    result = format_in_project(
        tmp_path, monkeypatch, 'phpcbf', PHPCS_EXCLUDING, 'legacy/l.php',
        TEXT)

    assert result == (TEXT, '')


def test_phpcbf_syntax_error_unchanged(tmp_path, monkeypatch):
    result = format_in_project(
        tmp_path, monkeypatch, 'phpcbf', PHPCS_RULESET, 'src/a.php', BROKEN)

    assert result == (BROKEN, '')


def test_php_cs_fixer_applies_project_config(tmp_path, monkeypatch):
    out, err = format_in_project(
        tmp_path, monkeypatch, 'php-cs-fixer', PHP_CS_FIXER_CONFIG,
        'src/a.php', TEXT)

    assert err == ''
    assert b'array(' in out


def test_php_cs_fixer_leaves_excluded_file_unchanged(tmp_path, monkeypatch):
    result = format_in_project(
        tmp_path, monkeypatch, 'php-cs-fixer', PHP_CS_FIXER_CONFIG,
        'legacy/l.php', TEXT)

    assert result == (TEXT, '')


def test_php_cs_fixer_syntax_error_unchanged(tmp_path, monkeypatch):
    result = format_in_project(
        tmp_path, monkeypatch, 'php-cs-fixer', PHP_CS_FIXER_CONFIG,
        'src/a.php', BROKEN)

    assert result == (BROKEN, '')
