import os
import subprocess

import pytest
from unittest.mock import Mock, call, patch

from .scenarios import format_on_save_scenarios


FILE = '/proj/pkg/m.py'
NOT_FOUND = ('ruff not found. Install ruff, or set ruff_path in '
             'codeformatter_python_options to its full path.')


def run(popen, out=b'', err=b'', returncode=0, opts=None, file_name=FILE,
        platform='linux'):
    from codeformatter.pyformatter import PyFormatter
    popen.return_value.communicate.return_value = (out, err)
    popen.return_value.returncode = returncode
    mocked_formatter = Mock()
    mocked_formatter.settings = {
        'codeformatter_python_options': opts or {'syntaxes': 'python'}}
    mocked_formatter.file_name = file_name
    mocked_formatter.platform = platform
    return PyFormatter(mocked_formatter).format(b'x=1\n')


def command(popen):
    return popen.call_args[0][0]


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_runs_ruff_on_the_file(popen):
    result = run(popen, out=b'x = 1\n')

    assert result == (b'x = 1\n', '')
    assert command(popen) == [
        'ruff', 'format', '--force-exclude', '--stdin-filename', FILE, '-']
    popen.return_value.communicate.assert_called_once_with(
        b'x=1\n', timeout=10)
    assert 'startupinfo' not in popen.call_args[1]
    assert popen.call_args[1].get('shell') is not True
    streams = [popen.call_args[1].get(name)
               for name in ('stdin', 'stdout', 'stderr')]
    assert streams == [subprocess.PIPE] * 3


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_drops_forced_color(popen, monkeypatch):
    monkeypatch.setenv('FORCE_COLOR', '1')
    monkeypatch.setenv('CLICOLOR_FORCE', '1')
    monkeypatch.setenv('CODEFORMATTER_TEST_KEEP', 'kept')

    run(popen, out=b'x = 1\n')

    env = popen.call_args[1]['env']
    assert 'FORCE_COLOR' not in env
    assert 'CLICOLOR_FORCE' not in env
    assert env['CODEFORMATTER_TEST_KEEP'] == 'kept'
    assert os.environ['FORCE_COLOR'] == '1'


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_unsaved_buffer(popen):
    run(popen, file_name=None, out=b'x = 1\n')

    assert command(popen) == ['ruff', 'format', '-']


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_passes_paths_as_single_arguments(popen):
    run(popen,
        opts={'ruff_path': 'C:\\Program Files\\ruff\\ruff.exe'},
        file_name='/home/u/my project/módulo.py',
        out=b'x = 1\n')

    assert command(popen) == [
        'C:\\Program Files\\ruff\\ruff.exe', 'format', '--force-exclude',
        '--stdin-filename', '/home/u/my project/módulo.py', '-']


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_empty_ruff_path_uses_ruff(popen):
    run(popen, opts={'ruff_path': ''}, out=b'x = 1\n')

    assert command(popen)[0] == 'ruff'


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_expands_home_in_ruff_path(popen, monkeypatch):
    monkeypatch.setenv('HOME', '/home/u')
    monkeypatch.setenv('USERPROFILE', '/home/u')

    run(popen, opts={'ruff_path': '~/bin/ruff'}, out=b'x = 1\n')

    assert command(popen)[0] == '/home/u/bin/ruff'


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_warning_goes_to_console(popen, capsys):
    result = run(popen, out=b'x = 1\n', err=b'warning: conflict\n')

    assert result == (b'x = 1\n', '')
    assert 'CodeFormatter: warning: conflict' in capsys.readouterr().out


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_multiline_warning_prefixes_every_line(popen, capsys):
    run(popen, out=b'x = 1\n', err=b'warning: conflict\n  detail\n')

    assert capsys.readouterr().out.splitlines() == [
        'CodeFormatter: warning: conflict', 'CodeFormatter:   detail']


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_empty_output_keeps_buffer(popen):
    result = run(popen, out=b'', returncode=0)

    assert result == (b'x=1\n', '')


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_error_returns_stderr(popen):
    result = run(
        popen,
        err=b'error: Failed to parse m.py:1:5: Expected an identifier\n',
        returncode=2)

    assert result == (
        '', 'error: Failed to parse m.py:1:5: Expected an identifier')


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_error_without_stderr(popen):
    result = run(popen, returncode=2)

    assert result == ('', 'ruff exited with code 2')


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_undecodable_stderr(popen):
    result = run(popen, err=b'\xff\xfe bad', returncode=2)

    assert result == ('', '\ufffd\ufffd bad')


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_ruff_not_found(popen):
    popen.side_effect = FileNotFoundError(2, 'No such file or directory')

    result = run(popen)

    assert result == ('', NOT_FOUND)


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_ruff_path_not_executable(popen):
    popen.side_effect = PermissionError(13, 'Permission denied')

    result = run(popen, opts={'ruff_path': '/opt/ruff-dir'})

    assert result == ('', NOT_FOUND)


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_timeout_kills_ruff(popen):
    popen.return_value.communicate.side_effect = [
        subprocess.TimeoutExpired(['ruff'], 10), (b'', b'')]

    result = run(popen)

    assert result == ('', 'ruff did not finish in 10 seconds')
    assert popen.return_value.mock_calls == [
        call.communicate(b'x=1\n', timeout=10), call.kill(),
        call.communicate()]


@patch('codeformatter.pyformatter.subprocess.Popen')
def test_py_formatter_hides_window_on_windows(popen):
    startupinfo = Mock(dwFlags=0)
    with patch.multiple('codeformatter.pyformatter.subprocess', create=True,
                        STARTUPINFO=Mock(return_value=startupinfo),
                        STARTF_USESHOWWINDOW=1, SW_HIDE=0):
        run(popen, platform='windows', out=b'x = 1\n')

    assert popen.call_args[1]['startupinfo'] is startupinfo
    assert startupinfo.dwFlags & 1
    assert startupinfo.wShowWindow == 0


@pytest.mark.parametrize('options,filename,expected', format_on_save_scenarios)
def test_py_formatter_format_on_save_enabled(options, filename, expected):
    from codeformatter.pyformatter import PyFormatter

    mocked_formatter = Mock()
    mocked_formatter.settings = {'codeformatter_python_options': options}

    pf = PyFormatter(mocked_formatter)
    res = pf.format_on_save_enabled(filename)
    assert res is expected
